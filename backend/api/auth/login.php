<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php'; // ✅ Ruta corregida

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Faltan el email o la contraseña']);
  exit;
}

$email = trim($input['email']);
$password = trim($input['password']);

try {
  $pdo = db();

  $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE Email = :email');
  $stmt->execute(['email' => $email]);
  $usuario = $stmt->fetch();

  if (!$usuario || !password_verify($password, $usuario['PasswordHash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales inválidas']);
    exit;
  }

  echo json_encode([
    'mensaje' => 'Login exitoso',
    'usuario' => [
      'id'     => $usuario['ID_Usuario'],
      'nombre' => $usuario['Nombre'],
      'email'  => $usuario['Email'],
      'rol'    => $usuario['rol'] ?? 'sin rol'
    ]
  ]);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Error de servidor: ' . $e->getMessage()]);
}
