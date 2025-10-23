<?php
declare(strict_types=1);
header('Content-Type: application/json');

require __DIR__ . '/../../config/db.php';
$pdo = db();

// 🔹 Obtener token del usuario (puede venir de header Authorization: Bearer <token>)
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['error' => 'Token no proporcionado']);
    exit;
}
$token = substr($authHeader, 7);

$stmt = $pdo->prepare("SELECT ID_Usuario FROM sesiones WHERE token = ? AND expira_en > NOW() LIMIT 1");
$stmt->execute([$token]);
$sesion = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sesion) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado']);
    exit;
}

$stmt = $pdo->prepare("SELECT carnet_validado, carnet_vencimiento FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$sesion['ID_Usuario']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$validado = 0;
if (($usuario['carnet_validado'] ?? 0) == 1 && $usuario['carnet_vencimiento'] && strtotime($usuario['carnet_vencimiento']) > time()) {
    $validado = 1;
}

echo json_encode([
    'validado' => $validado,
    'vencimiento' => $usuario['carnet_vencimiento'] ?? null
]);
