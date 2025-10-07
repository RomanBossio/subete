<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';
$pdo = db(); // 💥 NECESARIO
header('Content-Type: application/json');

try {
    // Cuenta usuarios con fecha de registro en los últimos 7 días (incluye hoy)
    $sql = "
        SELECT COUNT(*) 
        FROM usuarios 
        WHERE Fecha_Registro IS NOT NULL
          AND Fecha_Registro >= (NOW() - INTERVAL 7 DAY)
    ";
    $stmt  = $pdo->query($sql);
    $total = (int)$stmt->fetchColumn();

    echo json_encode(['total' => $total]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
