<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para consultar datos desde el backend
function api($ruta) {
  $url = "http://localhost/subete/backend/api/panel/$ruta";
  $json = @file_get_contents($url);

  if ($json === false) {
    return ['error' => "No se pudo obtener $ruta"];
  }

  $data = json_decode($json, true);
  return is_array($data) ? $data : ['error' => "Respuesta inválida"];
}

// Consultas al backend
$usuarios    = api("usuarios-totales.php");
$viajes      = api("viajes-publicados.php");
$nuevos      = api("nuevos-usuarios-semana.php");
$confirmados = api("viajes-confirmados.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Panel de Control - Súbete</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
    .card { border: none; border-radius: 1rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .card h5 { font-size: 1.2rem; }
    .dashboard-title { font-weight: 600; font-size: 2rem; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="container py-4">
    <h1 class="dashboard-title text-center">Panel de Control - Súbete 🚗</h1>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">

      <!-- Usuarios registrados -->
      <div class="col">
        <div class="card p-3">
          <h5>👥 Usuarios registrados</h5>
          <p class="display-6 text-center">
            <?= isset($usuarios['total']) ? $usuarios['total'] : "<span class='text-danger'>" . ($usuarios['error'] ?? "Error") . "</span>" ?>
          </p>
        </div>
      </div>

      <!-- Viajes publicados -->
      <div class="col">
        <div class="card p-3">
          <h5>🚘 Viajes publicados</h5>
          <p class="display-6 text-center">
            <?= isset($viajes['total']) ? $viajes['total'] : "<span class='text-danger'>" . ($viajes['error'] ?? "Error") . "</span>" ?>
          </p>
        </div>
      </div>

      <!-- Nuevos usuarios esta semana -->
      <div class="col">
        <div class="card p-3">
          <h5>🧍 Nuevos esta semana</h5>
          <p class="display-6 text-center">
            <?= isset($nuevos['total']) ? $nuevos['total'] : "<span class='text-danger'>" . ($nuevos['error'] ?? "Error") . "</span>" ?>
          </p>
        </div>
      </div>

      <!-- Viajes confirmados -->
      <div class="col">
        <div class="card p-3">
          <h5>✅ Viajes confirmados</h5>
          <p class="display-6 text-center">
            <?= isset($confirmados['total']) ? $confirmados['total'] : "<span class='text-danger'>" . ($confirmados['error'] ?? "Error") . "</span>" ?>
          </p>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
