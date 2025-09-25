<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// Leer token
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token']);
    exit;
}
$token = substr($auth, 7);

// Verificar sesión
$stmt = $pdo->prepare("SELECT * FROM sesiones WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || new DateTime() > new DateTime($session['expira_en'])) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']);
    exit;
}

$id_usuario = $session['id_usuario'] ?? null;
if (!$id_usuario) {
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida']);
    exit;
}

// Traer viajes del conductor
$stmt = $pdo->prepare("
  SELECT ID_Viaje, Origen, Destino, Fecha_Hora_Salida, Lugares_Disponibles, Precio, Permite_Encomiendas, Estado
  FROM viajes
  WHERE ID_Conductor = ?
  ORDER BY Fecha_Hora_Salida DESC
");
$stmt->execute([$id_usuario]);
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clasificar entre futuros y pasados
$ahora = new DateTime();
$futuros = [];
$pasados = [];

foreach ($viajes as $v) {
    $fechaViaje = new DateTime($v['Fecha_Hora_Salida']);
    if ($fechaViaje >= $ahora) {
        $futuros[] = $v;
    } else {
        $pasados[] = $v;
    }
}

echo json_encode([
    'ok' => true,
    'viajes_futuros' => $futuros,
    'viajes_pasados' => $pasados
]);
exit;
