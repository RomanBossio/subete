<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Manejo del método
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Método no permitido']);
  exit;
}

require_once __DIR__ . '/../../config/db.php';

$pdo = db();

// Leer JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['error' => 'JSON inválido']);
  exit;
}

// Validar campos requeridos
$required = ['id_conductor','origen','destino','fecha_hora_salida','lugares','precio'];
foreach ($required as $f) {
  if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
    http_response_code(400);
    echo json_encode(['error' => "Falta el campo: $f"]);
    exit;
  }
}

// Normalización
$id_conductor = (int)$data['id_conductor'];
$origen       = trim((string)$data['origen']);
$destino      = trim((string)$data['destino']);
$fecha        = trim((string)$data['fecha_hora_salida']);
$lugares      = (int)$data['lugares'];
$precio       = (float)$data['precio'];
$permite      = isset($data['permite_encomiendas']) ? (int)$data['permite_encomiendas'] : 0;
$detalles     = isset($data['detalles']) ? trim((string)$data['detalles']) : null;

// Validaciones
if ($id_conductor <= 0 || $lugares <= 0 || $precio <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Valores inválidos']);
  exit;
}

$dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
if (!$dt || $dt->format('Y-m-d H:i:s') !== $fecha) {
  http_response_code(400);
  echo json_encode(['error' => 'La fecha debe tener el formato YYYY-MM-DD HH:MM:SS']);
  exit;
}

// Validar existencia del conductor
$stmt = $pdo->prepare("SELECT ID_Usuario FROM usuarios WHERE ID_Usuario = ? LIMIT 1");
$stmt->execute([$id_conductor]);
if (!$stmt->fetch()) {
  http_response_code(400);
  echo json_encode(['error' => 'El usuario no existe o no es conductor']);
  exit;
}

// Insertar viaje
try {
  $sql = "INSERT INTO viajes
    (ID_Conductor, Origen, Destino, Fecha_Hora_Salida, Lugares_Disponibles, Precio, Permite_Encomiendas, Detalles, Estado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Disponible')";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $id_conductor,
    $origen,
    $destino,
    $fecha,
    $lugares,
    $precio,
    $permite,
    $detalles
  ]);

  http_response_code(201);
  echo json_encode([
    'ok' => true,
    'id_viaje' => (int)$pdo->lastInsertId()
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Error en el servidor', 'detail' => $e->getMessage()]);
}
