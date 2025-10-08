<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../config/db.php'; // desde api/calificaciones hasta config/db.php
$pdo = db();

/* ---------- AUTH ---------- */
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
  echo json_encode(['ok'=>false,'error'=>'No hay token']); exit;
}
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
$id_user = (int)$me['id_usuario'];

/* ---------- BODY ---------- */
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$ID_Viaje   = (int)($in['ID_Viaje'] ?? $in['id_viaje'] ?? 0);
$Puntuacion = (int)($in['Puntuacion'] ?? $in['puntuacion'] ?? 0);
$Comentario = trim((string)($in['Comentario'] ?? ''));

if ($ID_Viaje <= 0 || $Puntuacion < 1 || $Puntuacion > 5) {
  echo json_encode(['ok'=>false,'error'=>'Datos inválidos']); exit;
}

/* ---------- VALIDACIONES ---------- */
/* 1) El usuario debe tener reserva en ese viaje */
$qr = $pdo->prepare("
  SELECT r.ID_Reserva, r.estado AS estado_reserva,
         v.ID_Usuario AS ID_Conductor, v.Estado AS estado_viaje
  FROM reservas r
  JOIN viajes v ON v.ID_Viaje = r.ID_Viaje
  WHERE r.ID_Viaje = ? AND r.ID_Usuario = ?
  ORDER BY r.Fecha_Reserva DESC
  LIMIT 1
");
$qr->execute([$ID_Viaje, $id_user]);
$row = $qr->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo json_encode(['ok'=>false,'error'=>'No tenés una reserva en este viaje']); exit; }

$ID_Conductor   = (int)$row['ID_Conductor'];
$estado_viaje   = $row['estado_viaje'] ?? '';
$estado_reserva = $row['estado_reserva'] ?? '';

/* 2) Solo calificar viajes completados y reservas no canceladas */
if ($estado_viaje !== 'Completado') {
  echo json_encode(['ok'=>false,'error'=>'Solo podés calificar viajes completados']); exit;
}
if ($estado_reserva === 'cancelado') {
  echo json_encode(['ok'=>false,'error'=>'Reserva cancelada: no calificable']); exit;
}

/* 3) Evitar auto-calificación */
if ($ID_Conductor === $id_user) {
  echo json_encode(['ok'=>false,'error'=>'No podés calificarte']); exit;
}

/* ---------- INSERT/UPDATE ---------- */
try {
  $stmt = $pdo->prepare("
    INSERT INTO calificaciones (ID_Viaje, ID_Conductor, ID_Pasajero, Puntuacion, Comentario, Fecha)
    VALUES (:v,:c,:p,:punt,:com, NOW())
    ON DUPLICATE KEY UPDATE
      Puntuacion = VALUES(Puntuacion),
      Comentario = VALUES(Comentario),
      Fecha      = NOW()
  ");
  $stmt->execute([
    ':v'    => $ID_Viaje,
    ':c'    => $ID_Conductor,
    ':p'    => $id_user,
    ':punt' => $Puntuacion,
    ':com'  => $Comentario,
  ]);

  echo json_encode(['ok'=>true,'msg'=>'Calificación registrada']);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>'Error al guardar calificación','detalle'=>$e->getMessage()]);
}
