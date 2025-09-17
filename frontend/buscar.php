<?php
declare(strict_types=1);
// session_start();  // <- lo desactivamos por ahora
// if (!isset($_SESSION['user_id'])) {
//   header('Location: /subete/frontend/login.html');
//   exit;
// }
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Súbete · Buscar viajes</title>
  <link rel="stylesheet" href="css/app.css?v=1.0">
</head>
<body>
  <?php $page='buscar'; require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <h1>Buscar viajes</h1>

    <form id="searchForm" class="find-form">
      <div class="grid">
        <div class="col-3">
          <label>Origen</label>
          <input id="origen" list="origenList" placeholder="Córdoba" />
          <datalist id="origenList"></datalist>
        </div>

        <div class="col-3">
          <label>Destino</label>
          <input id="destino" list="destinoList" placeholder="Villa del Rosario" />
          <datalist id="destinoList"></datalist>
        </div>

        <div class="col-2">
          <label>Fecha</label>
          <input id="fecha" type="date" />
        </div>
        <div class="col-2">
          <label>Asientos mínimos</label>
          <input id="asientos" type="number" min="1" placeholder="1" />
        </div>
        <div class="col-2">
          <label>Precio máx</label>
          <input id="precioMax" type="number" min="0" step="50" placeholder="2500" />
        </div>
        <div class="col-2">
          <label>&nbsp;</label>
          <div class="row">
            <input id="encomiendas" type="checkbox" />
            <span>Acepta encomiendas</span>
          </div>
        </div>
        <div class="col-6 row">
          <button class="btn primary" type="submit">Buscar</button>
          <button class="btn" type="button" id="limpiar">Limpiar</button>
          <button class="btn" type="button" id="toggleConductor">Ver datos del conductor</button>
        </div>
      </div>
    </form>

    <div class="badges">
      <span class="badge" id="badgeTotal">0 resultados</span>
      <span class="badge">ordenados por salida</span>
    </div>

    <h2>Resultados</h2>
    <div id="results" class="results"></div>

    <div class="row" style="justify-content:center;gap:8px;margin-top:10px">
      <button class="btn" id="prev">Anterior</button>
      <button class="btn" id="next">Siguiente</button>
    </div>
  </main>

<script>
const API_URL = '/subete/backend/api/viajes/buscar-viajes.php';
let limit = 10, offset = 0, includeConductor = 0;

const results = document.getElementById('results');
const badgeTotal = document.getElementById('badgeTotal');

document.getElementById('searchForm').addEventListener('submit', e => { e.preventDefault(); offset=0; buscar(); });
document.getElementById('limpiar').addEventListener('click', () => { document.getElementById('searchForm').reset(); offset=0; buscar(); });
document.getElementById('toggleConductor').addEventListener('click', () => { includeConductor = includeConductor ? 0 : 1; buscar(); });
document.getElementById('prev').addEventListener('click', () => { offset = Math.max(0, offset-limit); buscar(); });
document.getElementById('next').addEventListener('click', () => { offset += limit; buscar(); });

async function buscar(){
  const q = new URLSearchParams();
  const origen   = document.getElementById('origen').value.trim();
  const destino  = document.getElementById('destino').value.trim();
  const fecha    = document.getElementById('fecha').value;
  const asientos = document.getElementById('asientos').value;
  const precio   = document.getElementById('precioMax').value;
  const encom    = document.getElementById('encomiendas').checked ? 1 : '';

  if (origen)   q.set('origen', origen);
  if (destino)  q.set('destino', destino);
  if (fecha)    q.set('fecha', fecha);
  if (asientos) q.set('asientos_min', asientos);
  if (precio)   q.set('precio_max', precio);
  if (encom !== '') q.set('permite_encomiendas', encom);

  q.set('include_conductor', includeConductor);
  q.set('limit', limit);
  q.set('offset', offset);

  results.innerHTML = '<div class="card">Cargando...</div>';

  try {
    const res = await fetch(`${API_URL}?${q.toString()}`);
    const data = await res.json();
    renderResults(data.results || []);
    badgeTotal.textContent = `${data.total ?? 0} resultado${(data.total ?? 0)===1?'':'s'}`;
    document.getElementById('prev').disabled = offset === 0;
    document.getElementById('next').disabled = (offset+limit) >= (data.total ?? 0);
  } catch (err) {
    results.innerHTML = '<div class="card">No se pudo cargar la búsqueda.</div>';
  }
}

function renderResults(items){
  if (!items.length){
    results.innerHTML = '<div class="card">No se encontraron viajes con esos filtros.</div>';
    return;
  }
  results.innerHTML = items.map(v => {
    const encom = Number(v.Permite_Encomiendas)===1?' · ✔ Encomiendas':'';
    const cond  = v.Conductor_Nombre?` · ${v.Conductor_Nombre} ${v.Conductor_Apellido}`:'';
    const precio = (Number(v.Precio)||0).toLocaleString('es-AR');
    return `
      <article class="card">
        <h3>${v.Origen} → ${v.Destino}</h3>
        <p class="meta">Sale: ${v.Fecha_Hora_Salida}${cond}${encom}</p>
        <p class="meta">Asientos: ${v.Lugares_Disponibles} · Precio: $${precio}</p>
        ${v.Detalles?`<p class="meta">Detalles: ${v.Detalles}</p>`:''}
        <div class="row" style="margin-top:6px;">
          <a class="btn" href="/subete/frontend/detalle-viaje.html?id=${v.ID_Viaje}">Ver detalle</a>
          <button class="btn">Reservar</button>
        </div>
      </article>
    `;
  }).join('');
}

// primera carga
buscar();

async function cargarCiudades(inputId, listId) {
  const input = document.getElementById(inputId);
  const list = document.getElementById(listId);
  input.addEventListener('input', async () => {
    const query = input.value.trim();
    if (query.length<3) return;
    try {
      const res = await fetch(`https://apis.datos.gob.ar/georef/api/localidades?nombre=${encodeURIComponent(query)}&max=10`);
      const data = await res.json();
      list.innerHTML = '';
      if (data.localidades){
        data.localidades.forEach(loc=>{
          const opt = document.createElement('option');
          opt.value = `${loc.nombre} (${loc.provincia.nombre})`;
          list.appendChild(opt);
        });
      }
    } catch(err){
      console.error("Error cargando ciudades:", err);
    }
  });
}

// activar autocompletado
cargarCiudades('origen','origenList');
cargarCiudades('destino','destinoList');

</script>
</body>
</html>
