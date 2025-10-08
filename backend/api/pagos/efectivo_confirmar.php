<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';
$pdo = db();

// --- logger simple ---
$logfile = __DIR__ . '/efectivo_confirmar.log';
function logx($m) {
  global $logfile;
  @file_put_contents($logfile, date('Y-m-d H:i:s') . ' ' . $m . "\n", FILE_APPEND);
}

try {
  // ---- Auth ----
  $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
  $auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!$auth || stripos($auth, 'bearer ') !== 0) { echo json_encode(['ok'=>false,'error'=>'No hay token']); exit; }
  $token = substr($auth, 7);

  $q = $pdo->prepare("
    SELECT s.id_usuario, u.rol
    FROM sesiones s
    JOIN usuarios u ON u.ID_Usuario = s.id_usuario
    WHERE s.token = ? LIMIT 1
  ");
  $q->execute([$token]);
  $me = $q->fetch(PDO::FETCH_ASSOC);
  if (!$me) { echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit; }
  $id_me = (int)$me['id_usuario'];
  $rol   = $me['rol'] ?? 'usuario';

  // ---- Body ----
  $raw = file_get_contents('php://input');
  $in  = json_decode($raw, true) ?: [];
  $ID_Viaje   = (int)($in['ID_Viaje'] ?? $in['id_viaje'] ?? 0);
  $ID_Usuario = (int)($in['ID_Usuario'] ?? $in['id_usuario'] ?? 0); // pasajero

  if ($ID_Viaje <= 0 || $ID_Usuario <= 0) {
    echo json_encode(['ok'=>false,'error'=>'Faltan ID_Viaje e ID_Usuario']); exit;
  }

  $pdo->beginTransaction();

  // 1) Verificar viaje y permisos
  $qv = $pdo->prepare("SELECT ID_Usuario, Precio FROM viajes WHERE ID_Viaje = ? LIMIT 1");
  $qv->execute([$ID_Viaje]);
  $viaje = $qv->fetch(PDO::FETCH_ASSOC);
  if (!$viaje) { $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Viaje no encontrado']); exit; }

  $ID_Conductor = (int)$viaje['ID_Usuario'];
  $Precio       = (float)$viaje['Precio'];
  if ($rol !== 'admin' && $ID_Conductor !== $id_me) {
    $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit;
  }

  // 2) Reserva del pasajero (pendiente)
  $qr = $pdo->prepare("
    SELECT ID_Reserva, cantidad, estado
    FROM reservas
    WHERE ID_Viaje = ? AND ID_Usuario = ?
    ORDER BY Fecha_Reserva DESC
    LIMIT 1
  ");
  $qr->execute([$ID_Viaje, $ID_Usuario]);
  $res = $qr->fetch(PDO::FETCH_ASSOC);
  if (!$res) { $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Reserva no encontrada']); exit; }

  if ($res['estado'] === 'pagado') {
    $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'La reserva ya está pagada']); exit;
  }
  if ($res['estado'] !== 'pendiente') {
    $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'La reserva no está pendiente']); exit;
  }

  $ID_Reserva = (int)$res['ID_Reserva'];
  $cantidad   = (int)$res['cantidad'];
  $Monto      = $Precio * $cantidad;

  // 3) ¿Hay pago en efectivo PENDIENTE existente?
  $qp = $pdo->prepare("
    SELECT ID_Pago
    FROM pagos
    WHERE ID_Viaje = ? AND ID_Usuario = ? AND Metodo = 'Efectivo' AND Estado = 'Pendiente'
    ORDER BY Fecha DESC
    LIMIT 1
  ");
  $qp->execute([$ID_Viaje, $ID_Usuario]);
  $pagoPend = $qp->fetch(PDO::FETCH_ASSOC);

  if ($pagoPend) {
    // Actualizar a Completado y vincular reserva
    $up = $pdo->prepare("
      UPDATE pagos
      SET Estado = 'Completado', Fecha = NOW(), ID_Reserva = ?
      WHERE ID_Pago = ?
    ");
    $up->execute([$ID_Reserva, (int)$pagoPend['ID_Pago']]);
  } else {
    // Crear pago ya Completado (si no se inició)
    $ip = $pdo->prepare("
      INSERT INTO pagos (ID_Reserva, ID_Usuario, ID_Viaje, Monto, Fecha, Metodo, Estado)
      VALUES (?, ?, ?, ?, NOW(), 'Efectivo', 'Completado')
    ");
    $ip->execute([$ID_Reserva, $ID_Usuario, $ID_Viaje, $Monto]);
  }

  // 4) Marcar reserva como pagada
  $ur = $pdo->prepare("UPDATE reservas SET estado = 'pagado' WHERE ID_Reserva = ?");
  $ur->execute([$ID_Reserva]);

  $pdo->commit();
  echo json_encode(['ok'=>true,'mensaje'=>'Pago en efectivo confirmado','ID_Reserva'=>$ID_Reserva,'Monto'=>$Monto]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  logx('ERROR: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error interno','detalle'=>$e->getMessage()]);
}
