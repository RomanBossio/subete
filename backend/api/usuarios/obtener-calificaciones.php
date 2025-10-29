<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php'; // Ajusta la ruta si es necesario

$pdo = db();
$response = ['ok' => false, 'calificaciones' => []]; // Respuesta base

try {
    // Obtener ID del conductor desde el parámetro GET (ej: ?id_conductor=14)
    $id_conductor = isset($_GET['id_conductor']) ? (int)$_GET['id_conductor'] : 0;

    if ($id_conductor <= 0) {
        throw new Exception('Falta el ID del conductor', 400);
    }

    // Consulta para obtener las calificaciones del conductor
    // Incluimos el nombre del pasajero que calificó
    $query = $pdo->prepare("
        SELECT
            c.Puntuacion,
            c.Comentario,
            c.Fecha,
            p.Nombre AS NombrePasajero
        FROM calificaciones c
        JOIN usuarios p ON c.ID_Pasajero = p.ID_Usuario
        WHERE c.ID_Conductor = :id_conductor
          AND c.Comentario IS NOT NULL AND c.Comentario != '' -- Solo mostrar las que tienen comentario (opcional)
        ORDER BY c.Fecha DESC
        LIMIT 50 -- Limitar a las últimas 50, por ejemplo
    ");

    $query->execute([':id_conductor' => $id_conductor]);
    $calificaciones = $query->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'ok' => true,
        'calificaciones' => $calificaciones
    ];

} catch (Exception $e) {
    $httpCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($httpCode);
    $response['error'] = $e->getMessage();
} catch (Throwable $e) {
    error_log("Error en obtener-calificaciones.php: " . $e->getMessage());
    http_response_code(500);
    $response['error'] = 'Error interno del servidor';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);