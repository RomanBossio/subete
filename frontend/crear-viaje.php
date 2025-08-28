<?php
session_start();

// Si no está logueado, redirigir
if (!isset($_SESSION['usuario'])) {
  header("Location: login.html");
  exit;
}

$mensaje = "";

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $data = [
    "id_conductor" => $_SESSION['usuario']['id'],
    "origen" => $_POST['origen'],
    "destino" => $_POST['destino'],
    "fecha_hora_salida" => $_POST['fecha'] . " " . $_POST['hora'] . ":00",
    "lugares" => $_POST['lugares'],
    "precio" => $_POST['precio'],
    "permite_encomiendas" => isset($_POST['permite_encomiendas']) ? 1 : 0,
    "detalles" => $_POST['detalles'] ?? ""
  ];

  // Enviar a la API con cURL
  $ch = curl_init("http://localhost/SUBETE/backend/api/viajes/crear-viajes.php");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $res = json_decode($response, true);

  if ($httpCode === 201 && isset($res['ok'])) {
    $mensaje = "✅ Viaje creado con éxito (ID " . $res['id_viaje'] . ")";
  } else {
    $mensaje = "❌ Error: " . ($res['error'] ?? "No se pudo crear el viaje");
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear Viaje - Súbete</title>
  <link rel="stylesheet" href="css/registrar.css">
</head>
<body>
  <!-- Header global -->
  <?php include __DIR__ . "/partials/header.php"; ?>

  <main class="auth-page">
    <div class="auth-container">
      <h2 class="auth-title">Crear un viaje</h2>
      <p class="auth-subtitle">Completa los datos para publicar tu viaje</p>

      <?php if ($mensaje): ?>
        <div class="alert <?= strpos($mensaje, '✅') !== false ? 'success show' : 'error show' ?>">
          <?= $mensaje ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Origen</label>
          <input type="text" name="origen" required>
        </div>
        <div class="form-group">
          <label>Destino</label>
          <input type="text" name="destino" required>
        </div>
        <div class="form-group">
          <label>Fecha</label>
          <input type="date" name="fecha" required>
        </div>
        <div class="form-group">
          <label>Hora</label>
          <input type="time" name="hora" required>
        </div>
        <div class="form-group">
          <label>Asientos disponibles</label>
          <input type="number" name="lugares" min="1" required>
        </div>
        <div class="form-group">
          <label>Precio</label>
          <input type="number" name="precio" min="0" required>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="permite_encomiendas"> Permitir encomi_
