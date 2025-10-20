<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/../../config/config.php'; // contiene OPENAI_API_KEY
require __DIR__ . '/../../config/db.php';     // función db()

// === VALIDAR CAMPOS OBLIGATORIOS ===
$required = ['nombre','apellido','documento','email','password'];
foreach ($required as $f) {
    if (!isset($_POST[$f]) || trim((string)$_POST[$f]) === '') {
        echo json_encode(['status'=>'400','message'=>"Falta el campo: $f"]);
        exit;
    }
}

$nombre     = trim($_POST['nombre']);
$apellido   = trim($_POST['apellido']);
$documento  = trim($_POST['documento']);
$email      = strtolower(trim($_POST['email']));
$telefono   = $_POST['telefono'] ?? null;
$password   = $_POST['password'];

// === VALIDACIONES SIMPLES ===
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status'=>'400','message'=>'Email inválido']); exit;
}
if (strlen($password) < 8) {
    echo json_encode(['status'=>'400','message'=>'La contraseña debe tener al menos 8 caracteres']); exit;
}

// === VALIDAR FOTO DEL DNI ===
if (!isset($_FILES['dniFoto']) || $_FILES['dniFoto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status'=>'400','message'=>'No se subió correctamente la foto del DNI']); exit;
}

// === CONVERTIR IMAGEN A BASE64 ===
$imgData = base64_encode(file_get_contents($_FILES['dniFoto']['tmp_name']));

// === PROMPT PARA IA ===
$prompt = "Extrae únicamente el NOMBRE, APELLIDO y NÚMERO DE DOCUMENTO del DNI argentino. 
Responde en formato JSON válido y nada más:
{ \"nombreOCR\": \"\", \"apellidoOCR\": \"\", \"dniOCR\": \"\" }";

// === LLAMADA A OPENAI ===
$ch = curl_init("https://api.openai.com/v1/responses");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . OPENAI_API_KEY,
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        "model" => "gpt-4.1-mini",
        "input" => [
            [
                "role" => "user",
                "content" => [
                    ["type"=>"input_text","text"=>$prompt],
                    ["type"=>"input_image","image_url"=>"data:image/jpeg;base64," . $imgData]
                ]
            ]
        ]
    ])
]);

$response = curl_exec($ch);
if ($response === false) {
    echo json_encode(['status'=>'500','message'=>'Error en la solicitud a OpenAI: '.curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

// === PARSEAR RESPUESTA ===
$data = json_decode($response, true);
$ocrText = $data['output'][0]['content'][0]['text'] ?? null;

if (!$ocrText) {
    echo json_encode(['status'=>'500','message'=>'No se pudo extraer nombre y apellido del DNI']);
    exit;
}

// === DECODIFICAR JSON DE LA IA ===
$ocrData = json_decode($ocrText, true);
if (!isset($ocrData['nombreOCR'], $ocrData['apellidoOCR'], $ocrData['dniOCR'])) {
    echo json_encode(['status'=>'500','message'=>'Respuesta de IA inválida']);
    exit;
}


// === COMPARAR NOMBRE Y APELLIDO ===
if (mb_strtoupper($nombre) !== mb_strtoupper($ocrData['nombreOCR']) ||
    mb_strtoupper($apellido) !== mb_strtoupper($ocrData['apellidoOCR']) ||
    preg_replace('/\D/', '', $documento) !== preg_replace('/\D/', '', $ocrData['dniOCR'])) {

    echo json_encode(['status'=>'400','message'=>'Nombre, apellido o DNI no coincide con el DNI']);
    exit;
}
// === COMPARAR DNI ===
$usuarioDNI  = preg_replace('/\D/', '', $documento);       // solo números
$ocrDNI      = preg_replace('/\D/', '', $ocrData['dniOCR']); // solo números

if ($usuarioDNI !== $ocrDNI) {
    echo json_encode(['status'=>'400','message'=>'El DNI no coincide con la foto']);
    exit;
}

// === REGISTRAR USUARIO ===
try {
    $pdo = db();

    // Email único
    $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE Email = ? LIMIT 1');
    $st->execute([$email]);
    if ($st->fetch()) { echo json_encode(['status'=>'409','message'=>'El email ya está registrado']); exit; }

    // Documento único
    $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE Documento = ? LIMIT 1');
    $st->execute([$documento]);
    if ($st->fetch()) { echo json_encode(['status'=>'409','message'=>'El documento ya está registrado']); exit; }

    // Hash de contraseña
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    $hash = password_hash($password, $algo);

    // Insert
    $sql = 'INSERT INTO usuarios (Nombre, Apellido, Documento, Email, Telefono, PasswordHash)
            VALUES (?, ?, ?, ?, ?, ?)';
    $pdo->prepare($sql)->execute([$nombre, $apellido, $documento, $email, $telefono ?: null, $hash]);

    echo json_encode(['status'=>'201','message'=>'Usuario registrado correctamente']);

} catch (Throwable $e) {
    echo json_encode(['status'=>'500','message'=>'Error del servidor']);
}
