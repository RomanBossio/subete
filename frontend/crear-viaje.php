<?php $page='crear'; ?>
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
  <label>Origen
    <input name="origen" id="origen" list="origenList" required>
    <datalist id="origenList"></datalist>
  </label>
  <label>Destino
    <input name="destino" id="destino" list="destinoList" required>
    <datalist id="destinoList"></datalist>
  </label>
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
  f.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const data = Object.fromEntries(new FormData(f).entries());
    const res = await fetch('../backend/api/viajes/crear-viajes.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(data)
    });
    const out = await res.json().catch(()=>({}));
    if(res.ok && !out.error){
      msg.textContent = 'Viaje publicado con éxito';
      f.reset();
    }else{
      msg.textContent = out.error || 'Error al publicar';
    }
  });
  // ------------------ Autocompletado de ciudades ------------------
async function cargarCiudades(inputId, listId) {
  const input = document.getElementById(inputId);
  const list  = document.getElementById(listId);

  input.addEventListener('input', async () => {
    const query = input.value.trim();
    if (query.length < 3) return; // esperamos a 3 letras mín.

    try {
      const res = await fetch(`https://apis.datos.gob.ar/georef/api/localidades?nombre=${encodeURIComponent(query)}&max=10`);
      const data = await res.json();

      list.innerHTML = '';
      if (data.localidades) {
        data.localidades.forEach(loc => {
          const opt = document.createElement('option');
          opt.value = `${loc.nombre} (${loc.provincia.nombre})`;
          list.appendChild(opt);
        });
      }
    } catch (err) {
      console.error("Error cargando ciudades:", err);
    }
  });
}

// activar para origen y destino
cargarCiudades('origen', 'origenList');
cargarCiudades('destino', 'destinoList');

  </script>
</body>
</html>
