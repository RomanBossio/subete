<?php $page = 'home_admin'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin - Súbete</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <h1 id="titulo">Bienvenido al panel de administración</h1>
    <p>Gestioná viajes, usuarios y más.</p>
  </main>

  <script>
    const usuario = JSON.parse(localStorage.getItem("usuario"));
    if (!usuario || usuario.rol !== "admin") {
      window.location.href = "login.php";
    } else {
      document.getElementById("titulo").textContent = "👑 Bienvenido, " + usuario.nombre;
    }
  </script>
</body>
</html>
