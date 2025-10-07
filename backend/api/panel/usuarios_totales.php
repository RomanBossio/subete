<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

$pdo = db(); // 👈 Esta línea no puede faltar
header('Content-Type: application/json');

try {
  $total = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
  echo json_encode(['total' => (int)$total]);
} catch (PDOException $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
?>
