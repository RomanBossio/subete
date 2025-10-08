<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
$pdo = db();

try {
  // Contar viajes publicados (Estado = 'Disponible')
  $stmt = $pdo->query("SELECT COUNT(*) AS total FROM viajes WHERE Estado = 'Disponible'");
  $total = $stmt->fetch(PDO::FETCH_ASSOC);

  echo json_encode(['total' => (int)$total['total']]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Error al consultar viajes publicados: ' . $e->getMessage()]);
}
