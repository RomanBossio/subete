<?php
declare(strict_types=1);
header('Content-Type: application/json');

require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// 🔹 Obtener token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['error' => 'Token no proporcionado']);
    exit;
}
$token = trim(substr($authHeader, 7));

$pdo = db();

// 🔹 Validar sesión
$stmt = $pdo->prepare('SELECT ID_Usuario FROM sesiones WHERE token = ? AND expira_en > NOW() LIMIT 1');
$stmt->execute([$token]);
$usuario = $stmt->fetch();
if (!$usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado']);
    exit;
}
$usuario_id = $usuario['ID_Usuario'];

// 🔹 Obtener datos del usuario
$stmt = $pdo->prepare("SELECT Documento, carnet_validado, carnet_vencimiento FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$usuario_id]);
$usr = $stmt->fetch();

// Si ya tiene carnet válido
if ($usr['carnet_validado'] && strtotime($usr['carnet_vencimiento']) >= time()) {
    echo json_encode(['error' => 'Ya tienes un carnet válido hasta ' . $usr['carnet_vencimiento']]);
    exit;
}

// 🔹 Validar archivo
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No se recibió la imagen del carnet']);
    exit;
}

$tmp_path = $_FILES['foto']['tmp_name'];
$base64_image = base64_encode(file_get_contents($tmp_path));

// 🔹 Enviar a IA para extraer datos
$payload = [
    'model' => 'gpt-4.1-mini',
    'input' => [[
        'role' => 'user',
        'content' => [[
            'type' => 'input_text',
            'text' => "Responde *únicamente* con JSON válido y sin texto adicional. Formato exacto:
{ \"nombre\": \"NOMBRE COMPLETO\", \"dni\": \"22222222\", \"vencimiento\": \"YYYY-MM-DD\" }"
        ],[
            'type' => 'input_image',
            'image_url' => "data:image/jpeg;base64,$base64_image"
        ]]
    ]]
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['error' => 'Error al conectar con IA: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

// 🔹 Procesar respuesta de IA
$data = json_decode($response, true);
$ocrText = $data['output'][0]['content'][0]['text'] ?? null;
if (!$ocrText) {
    echo json_encode(['error' => 'No se pudo extraer la información del carnet']);
    exit;
}

// Extraer JSON del texto
$ocrTextTrim = trim($ocrText);
$firstBrace = strpos($ocrTextTrim, '{');
$lastBrace  = strrpos($ocrTextTrim, '}');
$datos = null;
if ($firstBrace !== false && $lastBrace !== false) {
    $possibleJson = substr($ocrTextTrim, $firstBrace, $lastBrace - $firstBrace + 1);
    $datos = json_decode($possibleJson, true);
}
if (!$datos) $datos = json_decode($ocrTextTrim, true);
if (!$datos || !isset($datos['nombre'], $datos['dni'], $datos['vencimiento'])) {
    echo json_encode(['error' => 'La IA devolvió un JSON inválido']);
    exit;
}

// 🔹 Validar vencimiento
if (strtotime($datos['vencimiento']) < time()) {
    echo json_encode(['error' => 'El carnet está vencido']);
    exit;
}

// 🔹 Validar que el DNI del carnet coincida con el Documento del usuario
$documentoUsuario = preg_replace('/\D/', '', $usr['Documento']); // eliminar puntos, guiones, espacios
$dniCarnet = preg_replace('/\D/', '', $datos['dni']);           // idem

if ($dniCarnet !== $documentoUsuario) {
    echo json_encode(['error' => 'El número de documento del carnet no coincide con tu documento registrado']);
    exit;
}

// 🔹 Guardar datos en la BD
try {
    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET carnet_validado = 1,
            carnet_vencimiento = ?,
            carnet_nombre = ?,
            carnet_dni = ?
        WHERE id_usuario = ?
    ");
    $stmt->execute([$datos['vencimiento'], $datos['nombre'], $datos['dni'], $usuario_id]);

    echo json_encode([
        'validado' => true,
        'nombre' => $datos['nombre'],
        'dni' => $datos['dni'],
        'vencimiento' => $datos['vencimiento']
    ]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Error al guardar datos: ' . $e->getMessage()]);
}
