<?php $page=''; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Detalle de viaje</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <a href="/subete/frontend/buscar.php" class="btn">← Volver</a>
    <h1 class="mt-3">Detalle de viaje</h1>

    <pre id="debug" class="mt-2" style="background:#f6f8fb;border:1px solid var(--stroke);border-radius:10px;padding:10px;overflow:auto"></pre>
    <div id="view" class="card mt-2"></div>
  </main>

<script>
// Misma ruta que usabas:
const API_URL = '/subete/backend/api/viajes/detalle.php';
const params = new URLSearchParams(location.search);
const id = Number(params.get('id') || 0);
const debug = document.getElementById('debug');
const view  = document.getElementById('view');

if (!id){ view.textContent = 'Falta id'; throw new Error('Falta id'); }

(async () => {
  try {
    debug.textContent = 'Cargando...';
    const res = await fetch(`${API_URL}?id=${id}`);
    const data = await res.json();
    debug.textContent = JSON.stringify(data, null, 2);

    if (!data.ok) { view.textContent = data.error || 'Error'; return; }
    const v = data.viaje;
    const encom = Number(v.Permite_Encomiendas)===1 ? '✔ Acepta encomiendas' : 'No acepta encomiendas';
    const precio = (Number(v.Precio)||0).toLocaleString('es-AR');

    view.innerHTML = `
      <h2>${v.Origen} → ${v.Destino}</h2>
      <p class="muted">Salida: ${v.Fecha_Hora_Salida} · Estado: ${v.Estado}</p>
      <p class="muted">Asientos: ${v.Lugares_Disponibles} · Precio: $${precio} · ${encom}</p>
      ${v.Detalles ? `<p class="mt-2">${v.Detalles}</p>` : ''}

      <div class="card mt-3">
        <h3>Conductor</h3>
        <p class="mt-2">${v.Conductor_Nombre ?? ''} ${v.Conductor_Apellido ?? ''}</p>
        ${v.Conductor_Telefono ? `<p class="muted">Tel: ${v.Conductor_Telefono}</p>` : ''}
      </div>

      <div class="mt-3">
        <button class="btn primary" onclick="alert('Reservar: próximamente')">Reservar</button>
        <a class="btn" href="/subete/frontend/buscar.php">Volver</a>
      </div>
    `;
  } catch (e) {
    debug.textContent = 'Error: ' + e.message;
    view.textContent = 'No se pudo cargar el detalle.';
  }
})();
</script>
</body>
</html>
