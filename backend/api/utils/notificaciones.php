<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();
$response = ['ok' => false, 'no_leidas' => 0, 'notificaciones' => []]; // Respuesta por defecto

try {
    $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$auth || stripos($auth, 'bearer ') !== 0) {
        throw new Exception('Token no proporcionado', 401);
    }
    $token = trim(substr($auth, 7));
    if (empty($token)) {
        throw new Exception('Token vacío', 401);
    }

    $stmt = $pdo->prepare("SELECT id_usuario FROM sesiones WHERE token = ? AND expira_en > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $session = $stmt->fetch();
    if (!$session) {
        throw new Exception('Token inválido o expirado', 401);
    }
    $id_usuario = (int)$session['id_usuario'];

    // Contar no leídas
    $q_count = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE ID_Usuario_Destino = ? AND Leida = 0");
    $q_count->execute([$id_usuario]);
    $no_leidas = (int)$q_count->fetchColumn();

    // Traer últimas 20 notificaciones
    $q_notif = $pdo->prepare("
        SELECT ID_Notificacion, Mensaje, Leida, Fecha_Creacion, ID_Viaje_Relacionado
        FROM notificaciones
        WHERE ID_Usuario_Destino = ?
        ORDER BY Fecha_Creacion DESC
        LIMIT 20
    ");
    $q_notif->execute([$id_usuario]);
    $notificaciones = $q_notif->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'ok' => true,
        'no_leidas' => $no_leidas,
        'notificaciones' => $notificaciones
    ];

} catch (Exception $e) {
    // Si el código es 401 (no autorizado), devolver ese código HTTP
    if ($e->getCode() === 401) {
        http_response_code(401);
    }
    // Devolver siempre la estructura base para evitar errores JS
    $response['error'] = $e->getMessage();
} catch (Throwable $e) {
    error_log("Error en notificaciones.php: " . $e->getMessage()); // Loguear errores inesperados
    http_response_code(500); // Error interno del servidor
    $response['error'] = 'Error interno del servidor';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);