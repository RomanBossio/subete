<?php
header('Content-Type: application/json; charset=utf-8');

// Verificar que venga una ciudad y una fecha por GET
$ciudad = $_GET['ciudad'] ?? '';
$fecha = $_GET['fecha'] ?? '';

if (!$ciudad || !$fecha) {
    echo json_encode(["ok" => false, "error" => "Faltan parámetros (ciudad o fecha)."]);
    exit;
}

// Configuración
$apiKey = "CE65UJWZNYUTNGA5QA7UJT6NN";
$unitGroup = "metric";

// URL de la API
$apiUrl = "https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/"
        . urlencode($ciudad)
        . "/$fecha?unitGroup=$unitGroup&key=$apiKey&contentType=json&lang=es";

// Llamada a la API
$jsonData = @file_get_contents($apiUrl);

if ($jsonData === FALSE) {
    echo json_encode(["ok" => false, "error" => "No se pudo conectar con la API del clima."]);
    exit;
}

// Decodificar respuesta
$data = json_decode($jsonData, true);

// Verificar estructura
if (!isset($data['days'][0])) {
    echo json_encode(["ok" => false, "error" => "No se encontraron datos de clima."]);
    exit;
}

$dia = $data['days'][0];

echo json_encode([
    "ok" => true,
    "ciudad" => $data['resolvedAddress'] ?? $ciudad,
    "fecha" => $dia['datetime'],
    "condicion" => $dia['conditions'],
    "temp_max" => $dia['tempmax'],
    "temp_min" => $dia['tempmin'],
    "precipitacion" => $dia['precipprob'] ?? null
]);
