<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// (Opcional) CORS muy simple para desarrollo:
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../config/db.php';

// 1) Leer JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  echo json_encode(['status'=>'400','message'=>'JSON inválido']);
  exit;
}

// 2) Campos requeridos
$required = ['nombre', 'apellido', 'email', 'password'];
foreach ($required as $f) {
  if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
    echo json_encode(['status'=>'400','message'=>"Falta el campo: $f"]);
    exit;
  }
}

// 3) Normalización / sanitización básica
$nombre   = trim((string)$data['nombre']);
$apellido = trim((string)$data['apellido']);
$email    = strtolower(trim((string)$data['email']));
$telefono = isset($data['telefono']) ? trim((string)$data['telefono']) : null;
$password = (string)$data['password'];

// 4) Validaciones
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['status'=>'400','message'=>'Email inválido']);
  exit;
}

if (strlen($password) < 8) {
  echo json_encode(['status'=>'400','message'=>'La contraseña debe tener al menos 8 caracteres']);
  exit;
}

// Teléfono: permisivo (números, +, espacios, guiones, paréntesis), 6–20 chars si viene
if ($telefono !== null && $telefono !== '') {
  if (!preg_match('/^[0-9 +()\-]{6,20}$/', $telefono)) {
    echo json_encode(['status'=>'400','message'=>'Teléfono inválido']);
    exit;
  }
}

// (opcional) límites de longitud razonables
if (mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100) {
  echo json_encode(['status'=>'400','message'=>'Nombre/Apellido demasiado largo']);
  exit;
}

try {
  $pdo = db();

  // 5) Email único
  $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE Email = ? LIMIT 1');
  $st->execute([$email]);
  if ($st->fetch()) {
    echo json_encode(['status'=>'409','message'=>'El email ya está registrado']);
    exit;
  }

  // 6) Hash de contraseña (Argon2id si está disponible; fallback a PASSWORD_DEFAULT)
  $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
  $hash = password_hash($password, $algo);

  // 7) Insert
  $sql = 'INSERT INTO usuarios (Nombre, Apellido, Email, Telefono, PasswordHash)
          VALUES (?, ?, ?, ?, ?)';
  $pdo->prepare($sql)->execute([$nombre, $apellido, $email, $telefono ?: null, $hash]);

  echo json_encode(['status'=>'201','message'=>'Usuario registrado correctamente']);
} catch (Throwable $e) {
  // En dev podés loguear $e->getMessage()
  echo json_encode(['status'=>'500','message'=>'Error del servidor']);
}
