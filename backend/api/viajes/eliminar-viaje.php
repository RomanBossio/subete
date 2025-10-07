<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// Validar token
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

// Obtener id del viaje
$input = json_decode(file_get_contents('php://input'), true);
$id_viaje = $input['id_viaje'] ?? null;
if (!$id_viaje || !is_numeric($id_viaje)) {
    echo json_encode(['ok'=>false,'error'=>'ID de viaje inválido']);
    exit;
}

// Verificar que el usuario sea conductor del viaje
$stmt = $pdo->prepare("SELECT * FROM viajes WHERE ID_Viaje = ? AND ID_Usuario = ?");
$stmt->execute([$id_viaje, $id_usuario]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viaje) {
    echo json_encode(['ok'=>false,'error'=>'No tienes permisos para eliminar este viaje']);
    exit;
}

// Eliminar reservas asociadas
$stmt = $pdo->prepare("DELETE FROM reservas WHERE ID_Viaje = ?");
$stmt->execute([$id_viaje]);

// Eliminar el viaje
$stmt = $pdo->prepare("DELETE FROM viajes WHERE ID_Viaje = ?");
$stmt->execute([$id_viaje]);

echo json_encode(['ok'=>true,'msg'=>'Viaje eliminado correctamente']);
exit;
