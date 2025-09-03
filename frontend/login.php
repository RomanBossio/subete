<?php $page=''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Súbete</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <div class="card" style="max-width:520px;margin:auto">
      <div class="auth-logo" style="text-align:center;margin-bottom:12px">
        <img src="/subete/frontend/img/hero-carpool.jpg" alt="Logo" style="max-height:60px;object-fit:contain"> <!-- opcional -->
      </div>
      <h2 class="auth-title">Iniciar Sesión</h2>
      <p class="auth-subtitle muted">Ingresa tus credenciales para continuar</p>

      <div id="alert" class="alert mt-2"></div>

      <form id="loginForm" class="section">
        <div class="form-group">
          <label for="email">Correo</label>
          <input type="email" id="email" placeholder="ejemplo@mail.com" required>
        </div>
        <div class="form-group">
          <label for="password">Contraseña</label>
          <input type="password" id="password" placeholder="********" required>
        </div>
        <button type="submit" class="btn primary">Entrar</button>
      </form>

      <p class="auth-subtitle mt-3">¿No tienes cuenta?
        <a href="/subete/frontend/registrar.php">Regístrate aquí</a>
      </p>
    </div>
  </main>

  <!-- mantiene tu JS existente -->
  <script src="/subete/frontend/js/login.js"></script>
</body>
</html>
