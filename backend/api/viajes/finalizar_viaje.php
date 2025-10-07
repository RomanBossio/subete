<?php
require_once('../../config/db.php');
$pdo = db();
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);
$id_viaje = $data['id_viaje'] ?? null;

if (!$id_viaje) {
    echo json_encode(['ok'=>false, 'error'=>'Falta ID del viaje']);
    exit;
}

$stmt = $pdo->prepare("UPDATE viajes SET Estado = 'Completado' WHERE ID_Viaje = ?");
if ($stmt->execute([$id_viaje])) {
    echo json_encode(['ok'=>true, 'msg'=>'Viaje finalizado correctamente']);
} else {
    echo json_encode(['ok'=>false, 'error'=>'No se pudo finalizar el viaje']);
}
