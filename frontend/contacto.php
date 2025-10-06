<?php $page=''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Contacto - Súbete</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #000;
      color: #fff;
    }
    main.container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      flex-direction: column;
      text-align: center;
    }
    .card {
      background: #111;
      border-radius: 16px;
      padding: 30px 25px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 6px 25px rgba(0,0,0,0.5);
    }
    input, textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
    }
    button.btn.primary {
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
    button.btn.primary:hover {
      background-color: #1565c0;
    }
    .alert {
      display: none;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 10px;
      font-weight: 600;
    }
    .alert.success { background: rgba(0,200,0,0.2); color: #0c0; }
    .alert.error { background: rgba(255,0,0,0.2); color: #c00; }
  </style>
</head>
<body>
  <main class="container">
    <div class="card">
      <h1>Contacto</h1>
      <p>Escríbenos y te responderemos lo antes posible</p>

      <div id="alert" class="alert"></div>

      <form id="contactForm">
        <input type="text" id="nombre" placeholder="Tu nombre" required>
        <input type="email" id="email" placeholder="Tu correo" required>
        <input type="text" id="asunto" placeholder="Asunto" required>
        <textarea id="mensaje" placeholder="Tu mensaje" rows="5" required></textarea>
        <button type="submit" class="btn primary">Enviar</button>
      </form>
    </div>
  </main>

  <script>
    const form = document.getElementById('contactForm');
    const alertDiv = document.getElementById('alert');

    form.addEventListener('submit', async e => {
      e.preventDefault();
      alertDiv.style.display = 'none';
      alertDiv.textContent = '';

      const data = {
        nombre: document.getElementById('nombre').value,
        email: document.getElementById('email').value,
        asunto: document.getElementById('asunto').value,
        mensaje: document.getElementById('mensaje').value
      };

      try {
        const res = await fetch('../backend/api/auth/contacto.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify(data)
        });
        const out = await res.json();
        if (out.ok) {
          alertDiv.className = 'alert success';
          alertDiv.textContent = out.msg;
          alertDiv.style.display = 'block';
          form.reset();
        } else {
          alertDiv.className = 'alert error';
          alertDiv.textContent = out.error || 'Error al enviar';
          alertDiv.style.display = 'block';
        }
      } catch (err) {
        alertDiv.className = 'alert error';
        alertDiv.textContent = 'Error de conexión';
        alertDiv.style.display = 'block';
      }
    });
  </script>
</body>
</html>
