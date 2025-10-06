<?php $page=''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Súbete</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <style>
    /* Loader */
    .loader-overlay {
      position: fixed;
      inset: 0;
      background: rgba(255,255,255,0.95);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 999;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.5s ease, visibility 0.5s ease;
    }
    .loader-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .loader {
      width: 70px;
      height: 70px;
      border: 6px solid #ddd;
      border-top-color: var(--primary, #1e88e5);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .loader-text {
      margin-top: 15px;
      font-size: 18px;
      color: #333;
      font-weight: 500;
      transition: opacity 0.4s ease;
    }
    .fade-out {
      opacity: 0 !important;
      transition: opacity 0.8s ease;
    }
  </style>
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

      <p class="mt-2">
        <a href="#" id="forgot-password-link">¿Olvidaste tu contraseña?</a>
      </p>

      <div id="forgot-password-form" style="display:none; margin-top:15px;">
        <input type="email" id="forgot-email" placeholder="Ingresa tu correo" class="input-asientos" />
        <button id="btn-forgot" class="btn secondary">Enviar enlace</button>
        <div id="forgot-msg" class="mt-2"></div>
      </div>

      <p class="auth-subtitle mt-3">¿No tienes cuenta?
        <a href="/subete/frontend/registrar.php">Regístrate aquí</a>
      </p>
    </div>
  </main>

  <!-- Loader -->
  <div class="loader-overlay" id="loader">
    <div class="loader"></div>
    <div class="loader-text" id="loader-text">0%</div>
  </div>

<script>
const loginForm = document.getElementById('loginForm');
const alertDiv = document.getElementById('alert');
const loader = document.getElementById('loader');
const loaderText = document.getElementById('loader-text');

loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertDiv.textContent = '';

  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();

  loader.classList.add('active');
  let progress = 0;
  const interval = setInterval(() => {
    progress += Math.floor(Math.random() * 10) + 5;
    if (progress >= 95) progress = 95;
    loaderText.textContent = `${progress}%`;
  }, 200);

  try {
    const res = await fetch('../backend/api/auth/login.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({email, password})
    });
    const out = await res.json();

    if (res.ok && !out.error) {
      if (out.token) {
        localStorage.setItem('token', out.token);
        localStorage.setItem('usuario', JSON.stringify(out.usuario));
      }

      clearInterval(interval);
      loaderText.textContent = '100%';

      setTimeout(() => {
        const nombre = out.usuario.nombre || 'Usuario';
        loaderText.textContent = `Hola ${nombre}`;
      }, 400);

      setTimeout(() => {
        loader.classList.add('fade-out');
      }, 1000);

      setTimeout(() => {
        window.location.href = '/subete/frontend/home.php';
      }, 1700);

    } else {
      clearInterval(interval);
      loader.classList.remove('active');
      alertDiv.textContent = out.error || 'Error al iniciar sesión';
    }
  } catch (err) {
    clearInterval(interval);
    loader.classList.remove('active');
    console.error(err);
    alertDiv.textContent = 'Error de conexión';
  }
});

// ======= Olvidé contraseña =======
const forgotLink = document.getElementById('forgot-password-link');
const forgotForm = document.getElementById('forgot-password-form');
const btnForgot = document.getElementById('btn-forgot');
const forgotMsg = document.getElementById('forgot-msg');

forgotLink.addEventListener('click', (e) => {
  e.preventDefault();
  forgotForm.style.display = forgotForm.style.display === 'none' ? 'block' : 'none';
});

btnForgot.addEventListener('click', async () => {
  const email = document.getElementById('forgot-email').value.trim();
  forgotMsg.textContent = '';
  if (!email) { forgotMsg.textContent = 'Ingresa un correo válido'; return; }

  try {
    const res = await fetch('../backend/api/auth/forgot-password.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({email})
    });
    const out = await res.json();
    forgotMsg.textContent = out.ok ? 'Revisa tu correo para restablecer la contraseña' : (out.error || 'Error al enviar enlace');
  } catch (e) {
    console.error(e);
    forgotMsg.textContent = 'Error de conexión';
  }
});
</script>

</body>
</html>
