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

try {
    // ---------- INICIO TRANSACCIÓN ----------
    $pdo->beginTransaction();

    // Traemos el viaje con LOCK para evitar carreras y poder validar estado/owner/cupos
    $qViaje = $pdo->prepare("
        SELECT ID_Viaje, ID_Usuario AS id_conductor, Lugares_Disponibles, Estado, Fecha_Hora_Salida
        FROM viajes
        WHERE ID_Viaje = ?
        FOR UPDATE
    ");
    $qViaje->execute([$id_viaje]);
    $viaje = $qViaje->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'Viaje no encontrado']);
        exit;
    }

    // (1) No reservar mi propio viaje
    if ((int)$viaje['id_conductor'] === (int)$id_usuario) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'No podés reservar tu propio viaje']);
        exit;
    }

    // (2) Estado debe ser Disponible
    if (($viaje['Estado'] ?? '') !== 'Disponible') {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'El viaje no está disponible para reservar']);
        exit;
    }

 
     $ahora = new DateTime('now');
     if ($ahora > new DateTime($viaje['Fecha_Hora_Salida'])) {
         $pdo->rollBack();
         echo json_encode(['ok'=>false,'error'=>'La hora de salida ya pasó']); exit;
     }

    // (3) Verificar duplicado ACTIVO (pendiente o pagado)
    $qDup = $pdo->prepare("
        SELECT 1
        FROM reservas
        WHERE id_viaje = ? AND id_usuario = ? AND estado IN ('pendiente','pagado')
        LIMIT 1
    ");
    $qDup->execute([$id_viaje, $id_usuario]);
    if ($qDup->fetch()) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'Ya tenés una reserva activa para este viaje']);
        exit;
    }

    // (4) Verificar cupos
    $disponibles = (int)$viaje['Lugares_Disponibles'];
    if ($disponibles < $cantidad) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'No hay suficientes asientos disponibles']);
        exit;
    }

    // Insertar reserva
    $stmt = $pdo->prepare("
        INSERT INTO reservas (id_viaje, id_usuario, cantidad, fecha_reserva, estado)
        VALUES (?, ?, ?, NOW(), 'pendiente')
    ");
    $stmt->execute([$id_viaje, $id_usuario, $cantidad]);

    // Descontar cupos (seguros bajo el mismo lock)
    $stmt2 = $pdo->prepare("
        UPDATE viajes
        SET Lugares_Disponibles = Lugares_Disponibles - ?
        WHERE ID_Viaje = ?
    ");
    $stmt2->execute([$cantidad, $id_viaje]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'msg' => "Reserva realizada con éxito ({$cantidad} asiento/s)"]);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    dbg("Error SQL insertar reserva: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la reserva']);
    exit;
}
