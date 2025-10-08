<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// --- Auth ---
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) { echo json_encode(['ok'=>false,'error'=>'No hay token']); exit; }
$token = substr($auth, 7);

// sesión + rol
$q = $pdo->prepare("SELECT s.id_usuario, u.rol
                    FROM sesiones s JOIN usuarios u ON u.ID_Usuario = s.id_usuario
                    WHERE s.token=? LIMIT 1");
$q->execute([$token]);
$me = $q->fetch(PDO::FETCH_ASSOC);
if (!$me) { echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit; }
$id_me = (int)$me['id_usuario'];
$rol   = $me['rol'] ?? 'usuario';

// --- Body ---
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$id_viaje = (int)($in['id_viaje'] ?? $in['ID_Viaje'] ?? 0);
if ($id_viaje <= 0) { echo json_encode(['ok'=>false,'error'=>'Falta id_viaje']); exit; }

// Verificar que sea el conductor del viaje o admin
$qv = $pdo->prepare("SELECT ID_Usuario FROM viajes WHERE ID_Viaje=? LIMIT 1");
$qv->execute([$id_viaje]);
$conductor = (int)$qv->fetchColumn();
if (!$conductor) { echo json_encode(['ok'=>false,'error'=>'Viaje no encontrado']); exit; }
if ($rol !== 'admin' && $conductor !== $id_me) {
  echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit;
}

// Traer pasajeros
$qp = $pdo->prepare("
  SELECT
    r.ID_Reserva,
    r.ID_Usuario,
    r.cantidad,
    r.Estado    AS EstadoReserva,
    u.Nombre,
    u.Apellido,
    u.Telefono
  FROM reservas r
  JOIN usuarios u ON u.ID_Usuario = r.ID_Usuario
  WHERE r.ID_Viaje = ?
  ORDER BY r.Fecha_Reserva ASC
");
$qp->execute([$id_viaje]);
$pasajeros = $qp->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok'=>true,'pasajeros'=>$pasajeros]);
