<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();
$response = ['ok' => false];

// Permitir solo método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['error'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

try {
    $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$auth || stripos($auth, 'bearer ') !== 0) { throw new Exception('Token no proporcionado', 401); }
    $token = trim(substr($auth, 7));
    if (empty($token)) { throw new Exception('Token vacío', 401); }

    $stmt = $pdo->prepare("SELECT id_usuario FROM sesiones WHERE token = ? AND expira_en > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $session = $stmt->fetch();
    if (!$session) { throw new Exception('Token inválido o expirado', 401); }
    $id_usuario = (int)$session['id_usuario'];

    // Marcar todas las no leídas como leídas
    $q_update = $pdo->prepare("UPDATE notificaciones SET Leida = 1 WHERE ID_Usuario_Destino = ? AND Leida = 0");
    $q_update->execute([$id_usuario]);
    $count = $q_update->rowCount(); // Ver cuántas filas se actualizaron

    $response = ['ok' => true, 'msg' => $count . ' notificaciones marcadas como leídas.'];

} catch (Exception $e) {
    if ($e->getCode() === 401) { http_response_code(401); }
    $response['error'] = $e->getMessage();
} catch (Throwable $e) {
    error_log("Error en marcar-leidas.php: " . $e->getMessage());
    http_response_code(500);
    $response['error'] = 'Error interno del servidor';
}

echo json_encode($response);