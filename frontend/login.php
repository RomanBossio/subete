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
        <img src="/subete/frontend/img/hero-carpool.jpg" alt="Logo" style="max-height:60px;object-fit:contain">
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

<script>
const loginForm = document.getElementById('loginForm');
const alertDiv = document.getElementById('alert');

loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertDiv.textContent = '';

  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();

  try {
    const res = await fetch('../backend/api/auth/login.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({email, password})
    });

    const out = await res.json();

    if (res.ok && !out.error) {
      // Guardar token y datos del usuario en localStorage
      if (out.token) {
        localStorage.setItem('token', out.token);
        localStorage.setItem('usuario', JSON.stringify(out.usuario));
      }
      window.location.href = '/subete/frontend/home.php';
    } else {
      alertDiv.textContent = out.error || 'Error al iniciar sesión';
    }

  } catch (err) {
    console.error(err);
    alertDiv.textContent = 'Error de conexión';
  }
});
</script>

</body>
</html>
