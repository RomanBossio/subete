<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Ajusta la ruta según donde tengas config.php
require __DIR__ . '/../../config/config.php'; 
require __DIR__ . '/../../config/db.php'; // si más adelante necesitás DB

$input = json_decode(file_get_contents('php://input'), true);
$mensaje = trim($input['mensaje'] ?? '');

if ($mensaje === '') {
    echo json_encode(['error' => 'Mensaje vacío']);
    exit;
}

$apiKey = OPENAI_API_KEY;

// Prompt para la IA (agregamos "asientos")
$prompt = "
Sos un asistente que interpreta solicitudes de viajes en la app 'Súbete'.
Extraé del mensaje los siguientes datos (si existen): origen, destino, fecha y cantidad de asientos.
Respondé **solo** en formato JSON con las claves: origen, destino, fecha (YYYY-MM-DD) y asientos (número entero).
Si el año no se menciona, usá el año actual.
Ejemplo:
{\"origen\": \"Córdoba\", \"destino\": \"Buenos Aires\", \"fecha\": \"2025-10-30\", \"asientos\": 2}

Mensaje del usuario: \"$mensaje\"
";

// Configurar cURL
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => 'Sos un asistente que entiende lenguaje natural sobre viajes.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3
    ])
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['error' => 'Error al conectar con la API: ' . $error]);
    exit;
}

$data = json_decode($response, true);
$textoRespuesta = $data['choices'][0]['message']['content'] ?? '';

if (!$textoRespuesta) {
    echo json_encode(['error' => 'Respuesta vacía de la IA', 'raw' => $data]);
    exit;
}

// Limpiar posibles backticks y bloque de markdown
$textoRespuesta = preg_replace('/^```json\s*|```$/i', '', $textoRespuesta);
$textoRespuesta = trim($textoRespuesta);

// Decodificar JSON
$parsed = json_decode($textoRespuesta, true);
if (!$parsed) {
    echo json_encode(['error' => 'No se pudo decodificar la respuesta', 'raw' => $textoRespuesta]);
    exit;
}

// ✅ Normalizar los valores (primera letra mayúscula)
foreach (['origen', 'destino'] as $campo) {
    if (!empty($parsed[$campo])) {
        $parsed[$campo] = ucwords(mb_strtolower(trim($parsed[$campo])), " \t\r\n\f\v,");
    }
}

// ✅ Corregir el año si la IA devolvió uno anterior al actual
if (!empty($parsed['fecha'])) {
    $fechaIA = strtotime($parsed['fecha']);
    if ($fechaIA) {
        $anioIA = (int)date('Y', $fechaIA);
        $anioActual = (int)date('Y');
        $mesDia = date('-m-d', $fechaIA);

        if ($anioIA < $anioActual) {
            $parsed['fecha'] = date('Y-m-d', strtotime(($anioActual + (strtotime(date('Y') . $mesDia) < time() ? 1 : 0)) . $mesDia));
        }
    }
}

// ✅ Aseguramos que asientos sea un número entero válido
if (isset($parsed['asientos'])) {
    $parsed['asientos'] = (int)$parsed['asientos'];
    if ($parsed['asientos'] <= 0) unset($parsed['asientos']);
}

// Devolver JSON limpio
echo json_encode($parsed, JSON_UNESCAPED_UNICODE);
