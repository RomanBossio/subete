<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';
$pdo = db();

// --- Auth por token ---
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
  echo json_encode(['ok'=>false,'error'=>'No hay token']); exit;
}
$token = substr($auth, 7);

// sesión
$stmt = $pdo->prepare("SELECT s.id_usuario, s.expira_en
                       FROM sesiones s
                       WHERE s.token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || (isset($session['expira_en']) && new DateTime() > new DateTime($session['expira_en']))) {
  echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit;
}
$id_usuario = (int)$session['id_usuario'];

// --- Body ---
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$id_viaje = (int)($in['ID_Viaje'] ?? $in['id_viaje'] ?? 0);
if ($id_viaje <= 0) { echo json_encode(['ok'=>false,'error'=>'ID_Viaje requerido']); exit; }

try {
  $pdo->beginTransaction();

  // Validar que exista el viaje
  $qv = $pdo->prepare("SELECT ID_Viaje FROM viajes WHERE ID_Viaje = ? LIMIT 1");
  $qv->execute([$id_viaje]);
  if (!$qv->fetchColumn()) {
    $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Viaje no encontrado']); exit;
  }

  // Traer reserva PENDIENTE del usuario (y bloquearla)
  $qr = $pdo->prepare("
    SELECT id_reserva, cantidad
    FROM reservas
    WHERE id_viaje = ? AND id_usuario = ? AND estado = 'pendiente'
    LIMIT 1 FOR UPDATE
  ");
  $qr->execute([$id_viaje, $id_usuario]);
  $reserva = $qr->fetch(PDO::FETCH_ASSOC);
  if (!$reserva) {
    $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'No tenés una reserva pendiente para este viaje']); exit;
  }

  // (Importante) No insertamos en 'pagos' acá para evitar duplicados.
  // El registro en 'pagos' se crea al CONFIRMAR (efectivo_confirmar.php).

  $pdo->commit();
  echo json_encode([
    'ok' => true,
    'mensaje' => 'Pago en efectivo iniciado. El conductor podrá confirmarlo.',
    'id_reserva' => (int)$reserva['id_reserva']
  ]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(['ok'=>false,'error'=>'Error interno','detalle'=>$e->getMessage()]);
}
