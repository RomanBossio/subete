<?php $page=''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrarse - Súbete</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <a href="/subete/frontend/login.php" class="btn">← Volver al login</a>

    <div class="auth-page mt-3">
      <div id="error" class="alert error" style="display:none;"></div>
      <div id="success" class="alert success" style="display:none;"></div>

      <div class="auth-container card" style="max-width:640px;margin:auto">
        <div class="auth-logo" style="text-align:center;margin-bottom:12px">
          <img src="/subete/frontend/Img/hero-carpool.jpg" alt="Logo Súbete" style="max-height:60px;object-fit:contain">
        </div>

        <h1 class="auth-title">Crear Cuenta</h1>
        <p class="auth-subtitle muted">Registrate para acceder</p>

        <form id="registerForm" class="section">
          <div class="grid cols-2">
            <div class="form-group">
              <label for="registerNombre">Nombre</label>
              <input type="text" id="registerNombre" required />
            </div>
            <div class="form-group">
              <label for="registerApellido">Apellido</label>
              <input type="text" id="registerApellido" required />
            </div>
          </div>

          <div class="form-group">
            <label for="registerEmail">Correo electrónico</label>
            <input type="email" id="registerEmail" required />
          </div>
          <div class="form-group">
            <label for="registerTelefono">Teléfono (opcional)</label>
            <input type="text" id="registerTelefono" />
          </div>
          <div class="form-group">
            <label for="registerPassword">Contraseña</label>
            <input type="password" id="registerPassword" required />
          </div>

          <button type="submit" class="btn primary">
            <i class="fas fa-user-plus"></i> Crear Cuenta
          </button>
        </form>

        <p class="modal-footer mt-3">
          ¿Ya tienes cuenta? <a href="/subete/frontend/login.php">Inicia sesión</a>
        </p>
      </div>
    </div>
  </main>

  <!-- mantiene tu JS existente -->
  <script src="/subete/frontend/js/registrar.js"></script>
</body>
</html>
