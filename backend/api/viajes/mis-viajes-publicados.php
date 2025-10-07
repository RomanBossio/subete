<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// Leer token del header
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token de sesión']);
    exit;
}
$token = substr($auth, 7);

// Verificar sesión
$stmt = $pdo->prepare("SELECT * FROM sesiones WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || (isset($session['expira_en']) && new DateTime() > new DateTime($session['expira_en']))) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']);
    exit;
}

// 🔧 Esta es la línea corregida
$id_usuario = $session['id_usuario'] ?? null;

if (!$id_usuario) {
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida']);
    exit;
}

// Traer viajes publicados por el usuario
$stmt = $pdo->prepare("
    SELECT ID_Viaje, Origen, Destino, Fecha_Hora_Salida, Lugares_Disponibles
    FROM viajes
    WHERE ID_Usuario = ?
    ORDER BY Fecha_Hora_Salida DESC
");
$stmt->execute([$id_usuario]);
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok'=>true, 'viajes'=>$viajes]);
exit;
