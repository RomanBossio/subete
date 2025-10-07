<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

$logfile = __DIR__ . '/debug_reserva.log';
function dbg($msg) {
    global $logfile;
    file_put_contents($logfile, date('Y-m-d H:i:s') . " " . $msg . "\n", FILE_APPEND);
}

// ------------- cargar DB -------------
try {
    require __DIR__ . '/../../config/db.php';
    $pdo = db();
} catch (Throwable $e) {
    dbg("ERROR require db: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno (DB)']);
    exit;
}

// Log: headers + body
$rawBody = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];
dbg("HEADERS: " . json_encode($headers));
dbg("RAW_BODY: " . $rawBody);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// Token
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!$auth || stripos($auth, 'bearer ') !== 0) {
    echo json_encode(['ok' => false, 'error' => 'No hay token de sesión']);
    exit;
}
$token = trim(substr($auth, 7));

// Sesión
try {
    $stmt = $pdo->prepare("SELECT * FROM sesiones WHERE Token = ? LIMIT 1");
    $stmt->execute([$token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    dbg("Error SQL buscar sesión: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno']);
    exit;
}
if (!$session) {
    echo json_encode(['ok' => false, 'error' => 'Token inválido o expirado']);
    exit;
}
if (isset($session['expira_en']) && new DateTime() > new DateTime($session['expira_en'])) {
    echo json_encode(['ok' => false, 'error' => 'Token expirado']);
    exit;
}

$id_usuario = $session['ID_Usuario'] ?? $session['id_usuario'] ?? null;
if (!$id_usuario) {
    echo json_encode(['ok' => false, 'error' => 'Sesión inválida']);
    exit;
}

// Body
$input = json_decode($rawBody, true);
$id_viaje  = isset($input['id_viaje']) ? (int)$input['id_viaje'] : 0;
$cantidad  = isset($input['cantidad']) ? (int)$input['cantidad'] : 1;

if (!$id_viaje) {
    echo json_encode(['ok' => false, 'error' => 'Falta id_viaje']);
    exit;
}
if ($cantidad <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Cantidad inválida']);
    exit;
}

// Verificar viaje
try {
    $stmt = $pdo->prepare("SELECT Lugares_Disponibles FROM viajes WHERE ID_Viaje = ? LIMIT 1");
    $stmt->execute([$id_viaje]);
    $viaje = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    dbg("Error SQL select viaje: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno']);
    exit;
}
if (!$viaje) {
    echo json_encode(['ok' => false, 'error' => 'Viaje no encontrado']);
    exit;
}
if ((int)$viaje['Lugares_Disponibles'] < $cantidad) {
    echo json_encode(['ok' => false, 'error' => 'No hay suficientes asientos disponibles']);
    exit;
}

// Verificar duplicado
try {
    $stmt = $pdo->prepare("SELECT 1 FROM reservas WHERE id_viaje = ? AND id_usuario = ? LIMIT 1");
    $stmt->execute([$id_viaje, $id_usuario]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'Ya reservaste este viaje']);
        exit;
    }
} catch (Throwable $e) {
    dbg("Error SQL verificar reserva previa: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno']);
    exit;
}

// Insertar reserva
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO reservas (id_viaje, id_usuario, cantidad, fecha_reserva, estado) 
                           VALUES (?, ?, ?, NOW(), 'pendiente')");
    $stmt->execute([$id_viaje, $id_usuario, $cantidad]);

    $stmt2 = $pdo->prepare("UPDATE viajes 
                            SET Lugares_Disponibles = Lugares_Disponibles - ? 
                            WHERE ID_Viaje = ? AND Lugares_Disponibles >= ?");
    $stmt2->execute([$cantidad, $id_viaje, $cantidad]);

    if ($stmt2->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar asientos']);
        exit;
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'msg' => "Reserva realizada con éxito ({$cantidad} asiento/s)"]);
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    dbg("Error SQL insertar reserva: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la reserva']);
    exit;
}
