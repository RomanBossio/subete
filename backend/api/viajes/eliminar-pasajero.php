<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token']);
    exit;
}
$token = substr($auth, 7);

$stmt = $pdo->prepare("SELECT * FROM sesiones WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || new DateTime() > new DateTime($session['expira_en'])) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']);
    exit;
}

$id_usuario = $session['id_usuario'] ?? null;
if (!$id_usuario) {
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_reserva = $input['id_reserva'] ?? null;

if (!$id_reserva || !is_numeric($id_reserva)) {
    echo json_encode(['ok'=>false,'error'=>'ID de reserva inválido']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.ID_Reserva, r.ID_Viaje, r.cantidad, v.ID_Conductor
    FROM reservas r
    JOIN viajes v ON r.ID_Viaje = v.ID_Viaje
    WHERE r.ID_Reserva = ? AND v.ID_Conductor = ?
");
$stmt->execute([$id_reserva, $id_usuario]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    echo json_encode(['ok'=>false,'error'=>'No tienes permisos para eliminar esta reserva']);
    exit;
}

$id_viaje = $reserva['ID_Viaje'];
$cantidad = (int)$reserva['cantidad'];

$stmt = $pdo->prepare("DELETE FROM reservas WHERE ID_Reserva = ?");
$stmt->execute([$id_reserva]);

$stmt = $pdo->prepare("UPDATE viajes SET Lugares_Disponibles = Lugares_Disponibles + ? WHERE ID_Viaje = ?");
$stmt->execute([$cantidad, $id_viaje]);

echo json_encode(['ok'=>true, 'msg'=>'Pasajero eliminado']);
exit;
