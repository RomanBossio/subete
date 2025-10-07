<?php
require_once('../../config/db.php');
$pdo = db();
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);
$id_viaje = (int)($data['id_viaje'] ?? 0);
$id_usuario = (int)($data['id_usuario'] ?? 0);
$cantidad = (int)($data['cantidad'] ?? 1);

if (!$id_viaje || !$id_usuario) {
    echo json_encode(['ok'=>false, 'error'=>'Faltan datos']);
    exit;
}

// Verificar estado del viaje
$stmt = $pdo->prepare("SELECT Estado, Lugares_Disponibles FROM viajes WHERE ID_Viaje = ?");
$stmt->execute([$id_viaje]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viaje) {
    echo json_encode(['ok'=>false, 'error'=>'Viaje no encontrado']);
    exit;
}

if ($viaje['Estado'] !== 'Disponible') {
    echo json_encode(['ok'=>false, 'error'=>'No se pueden reservar lugares. El viaje ya está en proceso o finalizado.']);
    exit;
}

if ((int)$viaje['Lugares_Disponibles'] < $cantidad) {
    echo json_encode(['ok'=>false, 'error'=>'No hay lugares suficientes.']);
    exit;
}

// Insertar reserva y actualizar asientos
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO reservas (ID_Viaje, ID_Usuario, cantidad, Estado) VALUES (?, ?, ?, 'pendiente')");
    $stmt->execute([$id_viaje, $id_usuario, $cantidad]);

    $stmt = $pdo->prepare("UPDATE viajes SET Lugares_Disponibles = Lugares_Disponibles - ? WHERE ID_Viaje = ?");
    $stmt->execute([$cantidad, $id_viaje]);

    $pdo->commit();
    echo json_encode(['ok'=>true, 'msg'=>'Reserva creada correctamente']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false, 'error'=>'Error al crear la reserva']);
}
