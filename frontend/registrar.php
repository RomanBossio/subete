<?php $page=''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrarse - Súbete</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    /* ===== Fondo con imagen difuminada ===== */
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: url('/subete/frontend/img/auto.png') no-repeat center center fixed;
      background-size: cover;
      height: 100vh;
      overflow-x: hidden;
    }

    /* Capa difuminada encima del fondo */
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(6px);
      z-index: -1;
    }

    /* Centrado del contenido */
    main.container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      flex-direction: column;
      text-align: center;
    }

    /* Botón volver */
    a.btn {
      background: rgba(255,255,255,0.85);
      color: #1e88e5;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
      transition: background 0.3s;
      margin-bottom: 15px;
    }

    a.btn:hover {
      background: white;
    }

    /* Tarjeta del registro */
    .card {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(8px);
      border-radius: 16px;
      padding: 30px 25px;
      box-shadow: 0 6px 25px rgba(0,0,0,0.3);
      width: 90%;
      max-width: 520px;
    }

    .auth-logo img {
      max-height: 60px;
      object-fit: contain;
    }

    .auth-title {
      font-size: 1.6rem;
      color: #333;
      margin-bottom: 10px;
    }

    .auth-subtitle {
      color: #555;
      font-size: 0.95rem;
      margin-bottom: 20px;
    }

    .form-group {
      text-align: left;
      margin-bottom: 15px;
    }

    label {
      display: block;
      font-weight: 500;
      margin-bottom: 5px;
      color: #333;
    }

    input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
    }

    .btn.primary {
      width: 100%;
      background-color: #1e88e5;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
    }

    .btn.primary:hover {
      background-color: #1565c0;
    }

    a {
      color: #1e88e5;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    .alert {
      display: none;
      margin-bottom: 10px;
      padding: 8px;
      border-radius: 6px;
      font-weight: 600;
    }

    .alert.error { background: rgba(255, 0, 0, 0.1); color: #c00; }
    .alert.success { background: rgba(0, 200, 0, 0.1); color: #060; }

    .modal-footer {
      color: #333;
    }

    .grid.cols-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    @media (max-width: 600px) {
      .grid.cols-2 {
        grid-template-columns: 1fr;
      }
    }
    /* --- Centrado vertical completo del contenido --- */
main.container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center; /* centra verticalmente */
  align-items: center;     /* centra horizontalmente */
}

  </style>
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <a href="/subete/frontend/login.php" class="btn">← Volver al login</a>

    <div class="auth-page mt-3">
      <div id="error" class="alert error"></div>
      <div id="success" class="alert success"></div>

      <div class="auth-container card">
        <div class="auth-logo" style="text-align:center;margin-bottom:12px">
          <img src="/subete/frontend/img/hero-carpool.jpg" alt="Logo Súbete">
        </div>

        <h1 class="auth-title">Crear Cuenta</h1>
        <p class="auth-subtitle muted">Regístrate para acceder</p>

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

  <script src="/subete/frontend/js/registrar.js"></script>
</body>
</html>
