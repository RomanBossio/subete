<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// obtenemos el parámetro q del query string
$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// tu username de GeoNames
$username = 'subete';

// construimos la URL de la API GeoNames (solo Argentina, máximo 10 resultados)
$url = 'http://api.geonames.org/searchJSON?name_startsWith=' . urlencode($query)
     . '&country=AR&maxRows=10&lang=es&username=subete'; // aquí puse subete directamente

$response = '';

// intento con file_get_contents si está habilitado
if (ini_get('allow_url_fopen')) {
    $response = @file_get_contents($url);
}

// si no funcionó, intento con cURL
if (!$response) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'subete-app/1.0');
        $response = curl_exec($ch);
        curl_close($ch);
    }
}

// si sigue vacío, devolvemos un array vacío
if (!$response) {
    echo json_encode([]);
    exit;
}

// decodificamos la respuesta
$data = json_decode($response, true);
$results = [];

// procesamos los resultados de GeoNames
if (!empty($data['geonames'])) {
    foreach ($data['geonames'] as $g) {
        $results[] = [
            'name'    => $g['name'] ?? '',
            'admin'   => $g['adminName1'] ?? '',
            'country' => $g['countryName'] ?? 'Argentina'
        ];
    }
}

// devolvemos JSON listo para el frontend
echo json_encode(['data' => $results]);
