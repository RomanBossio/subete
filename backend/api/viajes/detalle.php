<?php
declare(strict_types=1);
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['error'=>'Método no permitido']); exit; }

require_once __DIR__ . '/../../config/db.php';
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'Falta id']); exit; }

$sql = "SELECT
  v.ID_Viaje, v.ID_Conductor, v.Origen, v.Destino, v.Fecha_Hora_Salida,
  v.Lugares_Disponibles, v.Precio, v.Permite_Encomiendas, v.Detalles, v.Estado,
  u.Nombre AS Conductor_Nombre, u.Apellido AS Conductor_Apellido, u.Telefono AS Conductor_Telefono
FROM viajes v
LEFT JOIN usuarios u ON u.ID_Usuario = v.ID_Conductor
WHERE v.ID_Viaje = ? LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { http_response_code(404); echo json_encode(['error'=>'No encontrado']); exit; }

echo json_encode(['ok'=>true, 'viaje'=>$row], JSON_UNESCAPED_UNICODE);
