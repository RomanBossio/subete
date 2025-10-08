<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// Leer token de headers
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
if (
    !$session ||
    (isset($session['expira_en']) && new DateTime() > new DateTime($session['expira_en']))
) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']);
    exit;
}

$id_usuario = $session['ID_Usuario'] ?? $session['id_usuario'] ?? null;
if (!$id_usuario) {
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida']);
    exit;
}

// Traer reservas del usuario (incluyendo precio y estado del viaje)
$sql = "
    SELECT 
        r.id_reserva,
        r.id_viaje,
        r.fecha_reserva,
        r.estado            AS EstadoReserva,
        r.cantidad,
        v.Origen,
        v.Destino,
        v.Fecha_Hora_Salida,
        v.Precio            AS Precio,
        v.Estado            AS EstadoViaje
    FROM reservas r
    JOIN viajes v ON r.id_viaje = v.ID_Viaje
    WHERE r.id_usuario = ?
    ORDER BY r.fecha_reserva DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Post-procesado: precio_total y flag para efectivo
foreach ($reservas as &$r) {
    // cast seguros
    $precio   = isset($r['Precio']) ? (float)$r['Precio'] : 0.0;
    $cantidad = isset($r['cantidad']) ? (int)$r['cantidad'] : 0;

    $r['precio_total'] = number_format($precio * $cantidad, 2, '.', '');
    $r['canPayCash']   = ($r['EstadoReserva'] === 'pendiente'); // para botón "Pagar en efectivo"
}
unset($r);

echo json_encode(['ok'=>true, 'reservas'=>$reservas]);
exit;
