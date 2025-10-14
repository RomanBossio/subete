<?php
declare(strict_types=1);
header('Content-Type: application/json');

// Autenticación vía Bearer token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);

// TODO: validar el token y obtener el ID del conductor
require_once __DIR__ . '/../../config/db.php'; // conexión PDO
require_once __DIR__ . '/../../models/User.php'; // si tenés modelo User

$userId = validarTokenYObtenerUsuario($token); // implementá esta función
if (!$userId) {
    echo json_encode(['ok' => false, 'error' => 'Token inválido']);
    exit;
}

try {
    $pdo = getPDO(); // tu conexión PDO

    // Traemos últimas 20 calificaciones del conductor
    $stmt = $pdo->prepare("
        SELECT c.ID_Calificacion, c.Fecha, c.Puntuacion, c.Comentario,
               u.Nombre, u.Apellido
        FROM calificaciones c
        INNER JOIN usuarios u ON u.ID_Usuario = c.ID_Pasajero
        WHERE c.ID_Conductor = :id_conductor
        ORDER BY c.Fecha DESC
        LIMIT 20
    ");
    $stmt->execute(['id_conductor' => $userId]);
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'notificaciones' => array_map(function($n){
            return [
                'id'        => $n['ID_Calificacion'],
                'fecha'     => $n['Fecha'],
                'nombre'    => $n['Nombre'] . ' ' . $n['Apellido'],
                'estrellas' => (int)$n['Puntuacion'],
                'comentario'=> $n['Comentario']
            ];
        }, $notificaciones)
    ]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
