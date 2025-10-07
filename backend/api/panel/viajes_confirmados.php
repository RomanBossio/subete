<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
$pdo = db(); // 💥 NECESARIO

header('Content-Type: application/json');

try {
  $total = $pdo->query("SELECT COUNT(*) FROM viajes WHERE Estado = 'Confirmado'")->fetchColumn();
  echo json_encode(['total' => (int)$total]);
} catch (PDOException $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
