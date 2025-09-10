<?php $page='crear'; ?>
<?php
// 🔒 Esto SIEMPRE va primero
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Publicar viaje</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <div class="card">
      <h2>Publicar viaje</h2>
      <form id="form-viaje">
        <div class="grid cols-2">
          <label>Origen<input name="origen" required></label>
          <label>Destino<input name="destino" required></label>
        </div>
        <div class="grid cols-2">
          <label>Fecha<input type="date" name="fecha" required></label>
          <label>Hora<input type="time" name="hora" required></label>
        </div>
        <div class="grid cols-2">
          <label>Precio<input type="number" step="0.01" name="precio" required></label>
          <label>Asientos<input type="number" name="asientos" min="1" max="6" required></label>
        </div>
        <label>Descripción<textarea name="descripcion" rows="3"></textarea></label>
        <button class="btn primary" type="submit">Publicar</button>
      </form>
      <div id="msg" class="muted mt-3"></div>
    </div>
  </main>

  <script>
  const f = document.getElementById('form-viaje');
  const msg = document.getElementById('msg');

  f.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = Object.fromEntries(new FormData(f).entries());

    const usuario = JSON.parse(localStorage.getItem("usuario"));
    if (!usuario || !usuario.id) {
      msg.textContent = "⚠️ No se pudo obtener el ID del conductor. Reingresá sesión.";
      return;
    }

    // Combinar fecha y hora al formato que espera el backend
    const fechaHora = `${formData.fecha} ${formData.hora}:00`;

    const datos = {
      id_conductor: usuario.id,
      origen: formData.origen,
      destino: formData.destino,
      fecha_hora_salida: fechaHora,
      lugares: formData.asientos,
      precio: formData.precio,
      permite_encomiendas: 0,
      detalles: formData.descripcion
    };

    const res = await fetch('../backend/api/viajes/crear-viajes.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datos)
    });

    const out = await res.json().catch(() => ({}));

    if (res.ok && !out.error) {
      msg.textContent = '✅ Viaje publicado con éxito';
      f.reset();
    } else {
      msg.textContent = `❌ ${out.error || 'Error al publicar el viaje'}`;
    }
  });
  </script>
</body>
</html>
