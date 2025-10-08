<?php
declare(strict_types=1);
require_once('../../config/db.php');
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

// --- Auth por token (igual patrón que venís usando) ---
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth    = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token de sesión']); exit;
}
$token = substr($auth, 7);

// Traer sesión + rol
$stmt = $pdo->prepare("SELECT s.id_usuario, u.rol
                       FROM sesiones s
                       JOIN usuarios u ON u.ID_Usuario = s.id_usuario
                       WHERE s.token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session) { echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit; }

$id_usuario = (int)$session['id_usuario'];
$rol        = $session['rol'] ?? 'usuario';

// --- Body ---
$data = json_decode(file_get_contents("php://input"), true) ?: [];
$id_viaje = isset($data['ID_Viaje']) ? (int)$data['ID_Viaje'] : (int)($data['id_viaje'] ?? 0);
if ($id_viaje <= 0) { echo json_encode(['ok'=>false, 'error'=>'Falta ID del viaje']); exit; }

try {
    $pdo->beginTransaction();

    // Lock del viaje
    $q = $pdo->prepare("SELECT * FROM viajes WHERE ID_Viaje = ? FOR UPDATE");
    $q->execute([$id_viaje]);
    $viaje = $q->fetch(PDO::FETCH_ASSOC);
    if (!$viaje) { $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Viaje no encontrado']); exit; }

    // Permiso: conductor del viaje o admin
    if ((int)$viaje['ID_Usuario'] !== $id_usuario && $rol !== 'admin') {
        $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit;
    }

    // Debe estar "En proceso"
    if (($viaje['Estado'] ?? '') !== 'En proceso') {
        $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Solo se puede finalizar un viaje en estado "En proceso"']); exit;
    }

    // Cantidad de pasajeros pagados
    $sum = $pdo->prepare("SELECT COALESCE(SUM(cantidad),0) AS cant
                          FROM reservas
                          WHERE ID_Viaje = ? AND Estado = 'pagado'");
    $sum->execute([$id_viaje]);
    $pasajerosPagados = (int)$sum->fetchColumn();

    // Marcar viaje como "Completado"
    $up = $pdo->prepare("UPDATE viajes SET Estado = 'Completado' WHERE ID_Viaje = ?");
    $up->execute([$id_viaje]);

    // Guardar histórico
    $ins = $pdo->prepare("INSERT INTO viajes_finalizados
        (ID_Usuario, origen, destino, fecha_salida, fecha_llegada, cantidad_pasajeros)
        VALUES (:u, :o, :d, :fs, NOW(), :cp)");
    $ins->execute([
        ':u'  => (int)$viaje['ID_Usuario'],
        ':o'  => $viaje['Origen'],
        ':d'  => $viaje['Destino'],
        ':fs' => $viaje['Fecha_Hora_Salida'],
        ':cp' => $pasajerosPagados
    ]);

    $pdo->commit();
    echo json_encode([
        'ok' => true,
        'msg' => 'Viaje finalizado correctamente',
        'nuevo_estado' => 'Completado',
        'ID_Viaje' => $id_viaje,
        'pasajeros_pagados' => $pasajerosPagados
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'error'=>'No se pudo finalizar el viaje','detalle'=>$e->getMessage()]);
}
