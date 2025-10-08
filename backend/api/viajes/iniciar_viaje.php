<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/db.php';

$pdo = db();

// --- Auth por token ---
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok'=>false,'error'=>'No hay token de sesión']); exit;
}
$token = substr($auth, 7);

// sesión + rol
$stmt = $pdo->prepare("SELECT s.id_usuario, u.rol, s.expira_en
                       FROM sesiones s
                       JOIN usuarios u ON u.ID_Usuario = s.id_usuario
                       WHERE s.token = ? LIMIT 1");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || (isset($session['expira_en']) && new DateTime() > new DateTime($session['expira_en']))) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido o expirado']); exit;
}
$id_usuario = (int)$session['id_usuario'];
$rol        = $session['rol'] ?? 'usuario';

// --- Body ---
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$id_viaje = (int)($in['ID_Viaje'] ?? $in['id_viaje'] ?? 0);
if ($id_viaje <= 0) { echo json_encode(['ok'=>false,'error'=>'ID_Viaje requerido']); exit; }

try {
    $pdo->beginTransaction();

    // Lock del viaje
    $q = $pdo->prepare("SELECT * FROM viajes WHERE ID_Viaje = ? FOR UPDATE");
    $q->execute([$id_viaje]);
    $viaje = $q->fetch(PDO::FETCH_ASSOC);
    if (!$viaje) { $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Viaje no encontrado']); exit; }

    // Permiso: conductor o admin
    if ((int)$viaje['ID_Usuario'] !== $id_usuario && $rol !== 'admin') {
        $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit;
    }

    // Debe estar Disponible
    if (($viaje['Estado'] ?? '') !== 'Disponible') {
        $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Solo se puede iniciar un viaje en estado "Disponible"']); exit;
    }

    // Hora actual vs fecha de salida (tu DATETIME(3) puede venir con .000; DateTime lo soporta)
    $now = new DateTime('now');
    $salida = new DateTime($viaje['Fecha_Hora_Salida']);
    if ($now < $salida) {
        $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>'Aún no llega la hora de salida']); exit;
    }

    // Cambiar estado
    $u = $pdo->prepare("UPDATE viajes SET Estado = 'En proceso' WHERE ID_Viaje = ?");
    $u->execute([$id_viaje]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'nuevo_estado'=>'En proceso','ID_Viaje'=>$id_viaje]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'error'=>'Error interno','detalle'=>$e->getMessage()]);
}
