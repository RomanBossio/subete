<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';
$pdo = db();

try {
  // Agrupa por mes-año; ajustá el formato si usás otra base
  $stmt = $pdo->query("
    SELECT FORMAT(Fecha_Registro, 'yyyy-MM') AS mes, COUNT(*) AS total
    FROM usuarios
    GROUP BY FORMAT(Fecha_Registro, 'yyyy-MM')
    ORDER BY mes ASC
  ");
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
