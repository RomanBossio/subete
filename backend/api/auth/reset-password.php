<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;
$password = $input['password'] ?? null;

if (!$token || !$password) {
    http_response_code(400);
    echo json_encode(['error'=>'Faltan token o contraseña']);
    exit;
}

try {
    $pdo = db();

    // Buscar token válido
    $stmt = $pdo->prepare('SELECT * FROM reset_password_tokens WHERE token = :token AND usado = 0 AND expira_en >= NOW()');
    $stmt->execute(['token'=>$token]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(400);
        echo json_encode(['error'=>'Token inválido o expirado']);
        exit;
    }

    $id_usuario = $row['id_usuario'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Actualizar contraseña del usuario
    $stmt = $pdo->prepare('UPDATE usuarios SET PasswordHash = :password WHERE ID_Usuario = :id');
    $stmt->execute(['password'=>$passwordHash, 'id'=>$id_usuario]);

    // Marcar token como usado
    $stmt = $pdo->prepare('UPDATE reset_password_tokens SET usado = 1 WHERE token = :token');
    $stmt->execute(['token'=>$token]);

    echo json_encode(['ok'=>true, 'msg'=>'Contraseña cambiada con éxito']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Error de servidor: '.$e->getMessage()]);
}
