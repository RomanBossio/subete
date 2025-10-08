<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// --- Auth por token ---
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) { echo json_encode(['ok'=>false,'error'=>'No hay token']); exit; }
$token = substr($auth, 7);

// Sesión
$stmt = $pdo->prepare("SELECT s.id_usuario FROM sesiones s WHERE s.token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session) { echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit; }
$id_usuario = (int)$session['id_usuario'];

// Traer reservas del usuario con datos del viaje
$sql = "SELECT r.ID_Reserva, r.ID_Viaje, r.Estado AS EstadoReserva, r.cantidad,
               v.Origen, v.Destino, v.Fecha_Hora_Salida, v.Estado AS EstadoViaje, v.Precio
        FROM reservas r
        JOIN viajes v ON v.ID_Viaje = r.ID_Viaje
        WHERE r.ID_Usuario = :u
        ORDER BY v.Fecha_Hora_Salida DESC";
$q = $pdo->prepare($sql);
$q->execute([':u' => $id_usuario]);
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTime('now');
$proximas = [];
$historial = [];

foreach ($rows as $r) {
    $salida = new DateTime($r['Fecha_Hora_Salida']);
    $isPast = ($salida < $now);
    $finishedState = in_array($r['EstadoViaje'], ['Completado','Cancelado'], true);

    if ($isPast || $finishedState) $historial[] = $r;
    else $proximas[] = $r;
}

echo json_encode(['ok'=>true, 'proximas'=>$proximas, 'historial'=>$historial]);
