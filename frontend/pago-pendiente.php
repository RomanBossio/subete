<?php $page = ''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pago pendiente</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<main class="container">
  <h1 class="section-title">Pago pendiente</h1>
  <p class="mt-2">Tu pago está siendo procesado por MercadoPago. Te notificaremos cuando se confirme.</p>
  <a href="/subete/frontend/mis-reservas.php" class="btn mt-3">Volver a Mis reservas</a>
</main>

</body>
</html>
