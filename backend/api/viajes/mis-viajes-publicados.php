<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// === Token ===
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token de sesión']); exit;
}
$token = substr($auth, 7);

// === Sesión ===
$stmt = $pdo->prepare("SELECT s.id_usuario, s.expira_en
                       FROM sesiones s
                       WHERE s.token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || (isset($session['expira_en']) && new DateTime() > new DateTime($session['expira_en']))) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit;
}
$id_usuario = (int)$session['id_usuario'];

// === Viajes publicados (incluimos Estado) ===
$stmt = $pdo->prepare("
    SELECT
      ID_Viaje,
      Origen,
      Destino,
      Fecha_Hora_Salida,
      Lugares_Disponibles,
      Estado
    FROM viajes
    WHERE ID_Usuario = ?
    ORDER BY Fecha_Hora_Salida DESC
");
$stmt->execute([$id_usuario]);
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Flags para el front ===
$now = new DateTime('now');
foreach ($viajes as &$v) {
    $salida = new DateTime($v['Fecha_Hora_Salida']);
    $estado = (string)$v['Estado'];

    // Puede iniciar si está "Disponible" y ya llegó la hora
    $v['canStart']  = ($estado === 'Disponible' && $now >= $salida);

    // Puede finalizar si está "En proceso"
    $v['canFinish'] = ($estado === 'En proceso');
}
unset($v);

echo json_encode(['ok'=>true,'viajes'=>$viajes]);
