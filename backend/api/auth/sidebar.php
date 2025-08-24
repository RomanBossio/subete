<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// Permitir solo POST (más prolijo para API)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Método no permitido']);
  exit;
}

require __DIR__ . '/../../config/db.php';


// Leer JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || (!isset($data['email']) && !isset($data['id']))) {
  http_response_code(400);
  echo json_encode(['error' => 'Debes enviar "email" o "id"']);
  exit;
}

try {
  $pdo = db();

  // Buscar usuario por email o id
  if (isset($data['email'])) {
    $stmt = $pdo->prepare('SELECT ID_Usuario, Rol FROM usuarios WHERE Email = ? LIMIT 1');
    $stmt->execute([trim((string)$data['email'])]);
  } else {
    $stmt = $pdo->prepare('SELECT ID_Usuario, Rol FROM usuarios WHERE ID_Usuario = ? LIMIT 1');
    $stmt->execute([(int)$data['id']]);
  }

  $u = $stmt->fetch();
  if (!$u) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
  }

  $rol = strtolower((string)$u['Rol']);

  // Menús según rol
  $menu_admin = [
    ['nombre' => 'Inicio',           'ruta' => '/inicio'],
    ['nombre' => 'Usuarios',         'ruta' => '/usuarios'],
    ['nombre' => 'Crear viaje',      'ruta' => '/crear-viaje'],
    ['nombre' => 'Buscar viaje',     'ruta' => '/buscar-viaje'],
    ['nombre' => 'Panel de control', 'ruta' => '/panel'],
    ['nombre' => 'Cerrar sesión',    'ruta' => '/logout'],
  ];

  $menu_usuario = [
    ['nombre' => 'Inicio',        'ruta' => '/inicio'],
    ['nombre' => 'Crear viaje',   'ruta' => '/crear-viaje'],
    ['nombre' => 'Buscar viaje',  'ruta' => '/buscar-viaje'],
    ['nombre' => 'Perfil',        'ruta' => '/perfil'],
    ['nombre' => 'Cerrar sesión', 'ruta' => '/logout'],
  ];

  $menu = $rol === 'admin' ? $menu_admin : $menu_usuario;

  echo json_encode([
    'ok'      => true,
    'usuario' => ['id' => (int)$u['ID_Usuario'], 'rol' => $rol],
    'sidebar' => $menu
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Error del servidor']);
}
