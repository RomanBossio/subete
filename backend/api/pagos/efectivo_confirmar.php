<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';
$pdo = db();

// --- logger simple ---
$logfile = __DIR__ . '/efectivo_confirmar.log';
function logx($m) {
  global $logfile;
  @file_put_contents($logfile, date('Y-m-d H:i:s') . ' ' . print_r($m, true) . "\n", FILE_APPEND);
}

try {
  // ---- Auth ----
  $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
  $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
  if (!$auth || stripos($auth, 'bearer ') !== 0) { throw new Exception('No hay token', 401); }
  $token = substr($auth, 7);

  $q = $pdo->prepare("SELECT s.id_usuario, u.rol FROM sesiones s JOIN usuarios u ON u.ID_Usuario = s.id_usuario WHERE s.token = ? AND s.expira_en > NOW() LIMIT 1");
  $q->execute([$token]);
  $me = $q->fetch(PDO::FETCH_ASSOC);
  if (!$me) { throw new Exception('Token inválido o expirado', 401); }
  $id_me = (int)$me['id_usuario']; // ID del usuario logueado (conductor o admin)
  $rol   = $me['rol'] ?? 'usuario';

  // ---- Body ----
  $raw = file_get_contents('php://input');
  $in  = json_decode($raw, true) ?: [];
  $ID_Viaje   = (int)($in['ID_Viaje'] ?? $in['id_viaje'] ?? 0);
  $ID_Usuario = (int)($in['ID_Usuario'] ?? $in['id_usuario'] ?? 0); // ID del pasajero a confirmar

  if ($ID_Viaje <= 0 || $ID_Usuario <= 0) { throw new Exception('Faltan ID_Viaje o ID_Usuario', 400); }

  $pdo->beginTransaction();

  // 1) Verificar viaje y permisos (Incluir Origen/Destino para el mensaje)
  $qv = $pdo->prepare("SELECT ID_Usuario, Precio, Origen, Destino FROM viajes WHERE ID_Viaje = ? LIMIT 1");
  $qv->execute([$ID_Viaje]);
  $viaje = $qv->fetch(PDO::FETCH_ASSOC);
  if (!$viaje) { $pdo->rollBack(); throw new Exception('Viaje no encontrado', 404); }

  $ID_Conductor = (int)$viaje['ID_Usuario'];
  $Precio       = (float)$viaje['Precio'];
  $OrigenViaje  = $viaje['Origen']; // Para el mensaje
  $DestinoViaje = $viaje['Destino']; // Para el mensaje

  // Solo el conductor del viaje o un admin pueden confirmar
  if ($rol !== 'admin' && $ID_Conductor !== $id_me) { $pdo->rollBack(); throw new Exception('No autorizado', 403); }

  // 2) Reserva del pasajero (pendiente)
  $qr = $pdo->prepare("SELECT ID_Reserva, cantidad, estado FROM reservas WHERE ID_Viaje = ? AND ID_Usuario = ? ORDER BY Fecha_Reserva DESC LIMIT 1");
  $qr->execute([$ID_Viaje, $ID_Usuario]);
  $res = $qr->fetch(PDO::FETCH_ASSOC);
  if (!$res) { $pdo->rollBack(); throw new Exception('Reserva no encontrada para este pasajero', 404); }

  if ($res['estado'] === 'pagado') { echo json_encode(['ok'=>false,'error'=>'La reserva ya está pagada']); exit; } // No es un error grave, solo informativo
  if ($res['estado'] !== 'pendiente') { $pdo->rollBack(); throw new Exception('La reserva no está pendiente (estado: '.$res['estado'].')', 409); } // 409 Conflict

  $ID_Reserva = (int)$res['ID_Reserva'];
  $cantidad   = (int)$res['cantidad'];
  $Monto      = $Precio * $cantidad;

  // 3) Buscar o crear pago
  $qp = $pdo->prepare("SELECT ID_Pago FROM pagos WHERE ID_Viaje = ? AND ID_Usuario = ? AND Metodo = 'Efectivo' ORDER BY Fecha DESC LIMIT 1");
  $qp->execute([$ID_Viaje, $ID_Usuario]);
  $pagoExistente = $qp->fetch(PDO::FETCH_ASSOC);

  if ($pagoExistente && ($pagoExistente['Estado'] ?? '') === 'Pendiente') {
      // Si existía uno pendiente, lo actualizamos
      $up = $pdo->prepare("UPDATE pagos SET Estado = 'Completado', Fecha = NOW(), ID_Reserva = ? WHERE ID_Pago = ?");
      $up->execute([$ID_Reserva, (int)$pagoExistente['ID_Pago']]);
  } elseif (!$pagoExistente || ($pagoExistente['Estado'] ?? '') !== 'Completado') {
      // Si no existía, o el último no era 'Completado', creamos uno nuevo ya completado
      $ip = $pdo->prepare("INSERT INTO pagos (ID_Reserva, ID_Usuario, ID_Viaje, Monto, Fecha, Metodo, Estado) VALUES (?, ?, ?, ?, NOW(), 'Efectivo', 'Completado')");
      $ip->execute([$ID_Reserva, $ID_Usuario, $ID_Viaje, $Monto]);
  }
  // Si ya existía uno 'Completado', no hacemos nada con la tabla pagos.

  // 4) Marcar reserva como pagada
  $ur = $pdo->prepare("UPDATE reservas SET estado = 'pagado' WHERE ID_Reserva = ?");
  $ur->execute([$ID_Reserva]);

  $pdo->commit();

  // --- NUEVO: Notificar al PASAJERO ---
  try {
      $mensajeNotif = "Tu pago en efectivo de $" . number_format($Monto, 0, ',', '.') . " para el viaje {$OrigenViaje} → {$DestinoViaje} fue confirmado.";
      $q_notif = $pdo->prepare("INSERT INTO notificaciones (ID_Usuario_Destino, Tipo, Mensaje, ID_Viaje_Relacionado) VALUES (?, 'nueva_reserva', ?, ?)"); // Reutilizamos tipo 'nueva_reserva' o creamos uno nuevo 'pago_confirmado'
      $q_notif->execute([$ID_Usuario, $mensajeNotif, $ID_Viaje]);
  } catch (Throwable $e) {
      logx('Error al crear notificación de pago confirmado: ' . $e->getMessage()); // Loguear pero no fallar
  }
  // --- FIN DEL CÓDIGO NUEVO ---

  echo json_encode(['ok'=>true,'mensaje'=>'Pago en efectivo confirmado','ID_Reserva'=>$ID_Reserva,'Monto'=>$Monto]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $httpCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($httpCode);
    logx('ERROR: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
    echo json_encode(['ok'=>false,'error'=> $e->getMessage()]); // Devolver mensaje de error específico
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    logx('ERROR INESPERADO: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'Error interno del servidor']);
}