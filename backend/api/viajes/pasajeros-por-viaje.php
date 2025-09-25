<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// Leer token
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token']);
    exit;
}
$token = substr($auth, 7);

// Verificar sesión
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

// Leer ID del viaje
$input = json_decode(file_get_contents('php://input'), true);
$id_viaje = $input['id_viaje'] ?? null;

if (!$id_viaje || !is_numeric($id_viaje)) {
    echo json_encode(['ok'=>false,'error'=>'ID de viaje inválido']);
    exit;
}

// Verificar que el viaje le pertenezca al conductor
$stmt = $pdo->prepare("SELECT * FROM viajes WHERE ID_Viaje = ? AND ID_Conductor = ?");
$stmt->execute([$id_viaje, $id_usuario]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$viaje) {
    echo json_encode(['ok'=>false,'error'=>'No tienes permisos sobre este viaje']);
    exit;
}

// 🔧 Obtener pasajeros incluyendo el ID_Reserva (necesario para poder eliminar)
$stmt = $pdo->prepare("
  SELECT r.ID_Reserva AS id_reserva, u.ID_Usuario, u.Nombre, u.Apellido, u.Telefono, r.cantidad
  FROM reservas r
  JOIN usuarios u ON r.ID_Usuario = u.ID_Usuario
  WHERE r.ID_Viaje = ? AND r.Estado = 'pendiente'
");
$stmt->execute([$id_viaje]);
$pasajeros = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok'=>true, 'pasajeros'=>$pasajeros]);
exit;
