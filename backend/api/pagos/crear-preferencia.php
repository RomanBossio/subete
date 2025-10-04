<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carga el autoloader de Composer
require __DIR__ . '/../../../vendor/autoload.php';

// Importa las clases necesarias del SDK de Mercado Pago
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

// Establece la cabecera para devolver una respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Configura tu Access Token de PRUEBA
MercadoPagoConfig::setAccessToken("APP_USR-5739111551792530-100108-87ce4c294c7bedfab806f6bf60a69c1b-2724868902"); // Asegúrate que sea el token de PRUEBA

// Lee el cuerpo de la solicitud (body) que viene en formato JSON
$body = json_decode(file_get_contents('php://input'), true);

// Extrae y valida los datos recibidos del frontend
$titulo = $body['titulo'] ?? '';
$precio = isset($body['precio']) ? floatval($body['precio']) : 0;
$cantidad = isset($body['cantidad']) ? intval($body['cantidad']) : 0;

if (empty($titulo) || $precio <= 0 || $cantidad < 1) {
    echo json_encode(['ok' => false, 'error' => 'Datos de reserva inválidos.']);
    exit;
}

try {
    $client = new PreferenceClient();
    
    // Crea la preferencia de pago
    $preference = $client->create([
        // ✅ SOLUCIÓN: No se usa la clase "PreferenceItem", evitando el error fatal.
        "items" => [
            [
                "title" => $titulo,
                "quantity" => $cantidad,
                "unit_price" => $precio
            ]
        ],
        "back_urls" => [
            "success" => "https://www.google.com/search?q=pago-exitoso",
            "failure" => "https://www.google.com/search?q=pago-fallido",
            "pending" => "https://www.google.com/search?q=pago-pendiente"
        ],
        "auto_return" => "approved"
    ]);

    // Si todo sale bien, devuelve el punto de inicio (init_point)
    echo json_encode([
        'ok' => true,
        'init_point' => $preference->init_point
    ]);

} catch (Exception $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    
    if (method_exists($e, 'getApiResponse')) {
        $apiResponse = $e->getApiResponse();
        if ($apiResponse) {
             // ✅ CORRECCIÓN: Se usa json_encode para evitar el warning "Array to string conversion".
             $errorMessage .= " - Response: " . json_encode($apiResponse->getContent());
        }
    }

    echo json_encode([
        'ok' => false,
        'error' => 'Error al crear la preferencia de pago: ' . $errorMessage
    ]);
}