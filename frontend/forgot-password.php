<form id="forgotForm">
  <input type="email" id="email" placeholder="Tu correo" required>
  <button type="submit" class="btn primary">Enviar enlace</button>
</form>
<div id="alert" class="alert"></div>

<script>
const form = document.getElementById('forgotForm');
const alertDiv = document.getElementById('alert');

form.addEventListener('submit', async e => {
  e.preventDefault();
  alertDiv.textContent = '';
  const email = document.getElementById('email').value.trim();

  try {
    const res = await fetch('../backend/api/auth/forgot-password.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({email})
    });
    const out = await res.json();

    if (out.ok) {
      alertDiv.style.color='green';
      alertDiv.innerHTML = `Revisa tu enlace para restablecer contraseña:<br><a href="${out.link}" target="_blank">${out.link}</a>`;
    } else {
      alertDiv.style.color='#d32f2f';
      alertDiv.textContent = out.error;
    }

  } catch(e) {
    alertDiv.textContent = 'Error de conexión';
    console.error(e);
  }
});
</script>
