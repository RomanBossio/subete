<?php $page=''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer Contraseña - Súbete</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <style>
    body { background: #f9f9f9; }
    .container { display: flex; justify-content: center; margin-top: 60px; }
    .card { background: #fff; padding: 25px; border-radius: 8px; max-width: 520px; width: 100%; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .auth-title { margin-bottom: 5px; font-size: 24px; text-align: center; }
    .auth-subtitle { text-align: center; color: #666; margin-bottom: 15px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; }
    .form-group input { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; }
    .btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; }
    .btn.primary { background: #1e88e5; color: #fff; width: 100%; }
    .alert { margin-top: 10px; color: #d32f2f; text-align: center; }
  </style>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<main class="container">
  <div class="card">
    <h2 class="auth-title">Restablecer contraseña</h2>
    <p class="auth-subtitle muted">Ingresa tu nueva contraseña</p>

    <div id="alert" class="alert"></div>

    <form id="resetForm">
      <div class="form-group">
        <label for="new-password">Nueva contraseña</label>
        <input type="password" id="new-password" placeholder="********" required>
      </div>
      <div class="form-group">
        <label for="confirm-password">Confirmar contraseña</label>
        <input type="password" id="confirm-password" placeholder="********" required>
      </div>
      <button type="submit" class="btn primary">Cambiar contraseña</button>
    </form>
  </div>
</main>

<script>
const urlParams = new URLSearchParams(location.search);
const token = urlParams.get('token');
const resetForm = document.getElementById('resetForm');
const alertDiv = document.getElementById('alert');

// ✅ Permitir acceso sin login, solo con token
if (!token) {
  alertDiv.textContent = 'Token inválido o faltante.';
  resetForm.style.display = 'none';
}

resetForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertDiv.textContent = '';

  const password = document.getElementById('new-password').value.trim();
  const confirm = document.getElementById('confirm-password').value.trim();

  if (password.length < 6) {
    alertDiv.textContent = 'La contraseña debe tener al menos 6 caracteres';
    return;
  }
  if (password !== confirm) {
    alertDiv.textContent = 'Las contraseñas no coinciden';
    return;
  }

  try {
    const res = await fetch('../backend/api/auth/reset-password.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ token, password })
    });

    const out = await res.json();

    if (out.ok) {
      alertDiv.style.color = 'green';
      alertDiv.textContent = 'Hola, tu contraseña ha sido cambiada con éxito. Redirigiendo al login...';
      setTimeout(() => { window.location.href = '/subete/frontend/login.php'; }, 2000);
    } else {
      alertDiv.style.color = '#d32f2f';
      alertDiv.textContent = out.error || 'Error al cambiar la contraseña';
    }
  } catch(e) {
    console.error(e);
    alertDiv.textContent = 'Error de conexión';
  }
});
</script>
</body>
</html>
