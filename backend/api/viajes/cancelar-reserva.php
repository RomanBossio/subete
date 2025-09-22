<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// Leer token de headers
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token de sesión']);
    exit;
}
$token = substr($auth, 7);

// Verificar sesión
$stmt = $pdo->prepare("SELECT * FROM sesiones WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || (isset($session['expira_en']) && new DateTime() > new DateTime($session['expira_en']))) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']);
    exit;
}

$id_usuario = $session['ID_Usuario'] ?? $session['id_usuario'] ?? null;
if (!$id_usuario) {
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida']);
    exit;
}

// Leer id_reserva del body JSON
$input = json_decode(file_get_contents('php://input'), true);
$id_reserva = (int)($input['id_reserva'] ?? 0);
if (!$id_reserva) {
    echo json_encode(['ok'=>false,'error'=>'Falta id_reserva']);
    exit;
}

// Verificar que la reserva pertenece al usuario
$stmt = $pdo->prepare("SELECT * FROM reservas WHERE id_reserva = ? AND id_usuario = ?");
$stmt->execute([$id_reserva, $id_usuario]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reserva) {
    echo json_encode(['ok'=>false,'error'=>'Reserva no encontrada']);
    exit;
}

// Cancelar reserva y devolver asiento en transacción
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM reservas WHERE id_reserva = ?");
    $stmt->execute([$id_reserva]);

    $cantidad = (int)($reserva['cantidad'] ?? 1);
    $id_viaje = (int)($reserva['ID_Viaje'] ?? 0); // <-- usar la columna exacta

    if ($id_viaje === 0) {
        $pdo->rollBack();
        echo json_encode(['ok'=>false,'error'=>'ID_Viaje inválido en la reserva']);
        exit;
    }

    $stmt2 = $pdo->prepare("UPDATE viajes SET Lugares_Disponibles = Lugares_Disponibles + ? WHERE ID_Viaje = ?");
    $stmt2->execute([$cantidad, $id_viaje]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'msg'=>'Reserva cancelada, se liberaron ' . $cantidad . ' asiento/s']);
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'error'=>'Error al cancelar reserva']);
    exit;
}
