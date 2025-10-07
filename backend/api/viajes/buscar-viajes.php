<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';
$pdo = db();

// ----------------- helpers -----------------
function str_or_null(string $key): ?string {
    return isset($_GET[$key]) && trim((string)$_GET[$key]) !== '' ? trim((string)$_GET[$key]) : null;
}
function int_or_null(string $key): ?int {
    if (!isset($_GET[$key])) return null;
    $v = trim((string)$_GET[$key]);
    return $v === '' ? null : (int)$v;
}
function float_or_null(string $key): ?float {
    if (!isset($_GET[$key])) return null;
    $v = trim((string)$_GET[$key]);
    return $v === '' ? null : (float)$v;
}

// --------------- parámetros ----------------
$origen    = str_or_null('origen');
$destino   = str_or_null('destino');
$fecha     = str_or_null('fecha');
$asientos  = int_or_null('asientos_min');
$precioMax = float_or_null('precio_max');
$encom     = int_or_null('permite_encomiendas');
$limit     = int_or_null('limit')  ?? 20;
$offset    = int_or_null('offset') ?? 0;
$incCond   = int_or_null('include_conductor') ?? 0;

if ($limit < 1 || $limit > 100) $limit = 20;
if ($offset < 0) $offset = 0;

// --------------- filtros (WHERE) -----------
$where = [
    "v.Estado = 'Disponible'",
    "v.Lugares_Disponibles > 0",
    "v.Fecha_Hora_Salida >= NOW()"  // <-- viajes futuros
];
$bind = [];

if ($origen !== null) {
    $where[] = "v.Origen COLLATE utf8mb4_general_ci LIKE :origen";
    $bind['origen'] = '%'.$origen.'%';
}
if ($destino !== null) {
    $where[] = "v.Destino COLLATE utf8mb4_general_ci LIKE :destino";
    $bind['destino'] = '%'.$destino.'%';
}
if ($fecha !== null) {
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$dt || $dt->format('Y-m-d') !== $fecha) {
        http_response_code(400);
        echo json_encode(['error' => "La fecha debe tener formato YYYY-MM-DD"]);
        exit;
    }
    $where[] = "v.Fecha_Hora_Salida BETWEEN :inicio AND :fin";
    $bind['inicio'] = $fecha . ' 00:00:00';
    $bind['fin']    = $fecha . ' 23:59:59.999';
}
if ($asientos !== null && $asientos > 0) {
    $where[] = "v.Lugares_Disponibles >= :asientos";
    $bind['asientos'] = $asientos;
}
if ($precioMax !== null && $precioMax >= 0) {
    $where[] = "v.Precio <= :precioMax";
    $bind['precioMax'] = $precioMax;
}
if ($encom !== null && ($encom === 0 || $encom === 1)) {
    $where[] = "v.Permite_Encomiendas = :encom";
    $bind['encom'] = $encom;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---------------- total (paginación) -------
$sqlCount = "SELECT COUNT(*) FROM viajes v $whereSql";
$stmt = $pdo->prepare($sqlCount);
foreach ($bind as $k => $v) { $stmt->bindValue(':'.$k, $v); }
$stmt->execute();
$total = (int)$stmt->fetchColumn();

// ---------- SELECT principal (+ join) ------
$select = "SELECT
    v.ID_Viaje, v.ID_Usuario, v.Origen, v.Destino,
    v.Fecha_Hora_Salida, v.Lugares_Disponibles, v.Precio,
    v.Permite_Encomiendas, v.Detalles, v.Estado";

$join = "";
if ($incCond === 1) {
    $select .= ",
        u.Nombre AS Conductor_Nombre,
        u.Apellido AS Conductor_Apellido,
        u.Telefono AS Conductor_Telefono";
    $join = " LEFT JOIN usuarios u ON u.ID_Usuario = v.ID_Usuario ";
}

$sql = "$select
        FROM viajes v
        $join
        $whereSql
        ORDER BY v.Fecha_Hora_Salida ASC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($bind as $k => $v) { $stmt->bindValue(':'.$k, $v); }
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------- respuesta -----------------
echo json_encode([
    'ok'     => true,
    'total'  => $total,
    'limit'  => $limit,
    'offset' => $offset,
    'results'=> $rows
], JSON_UNESCAPED_UNICODE);
