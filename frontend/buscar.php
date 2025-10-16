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
  <style>
    /* ==== Estilo de card para resultados ==== */
    .results .card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 20px 18px;
      margin-bottom: 15px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .results .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .results .card h3 {
      margin-top: 0;
      margin-bottom: 10px;
      color: #1e88e5;
    }

    .results .card .meta {
      margin: 4px 0;
      font-size: 0.95rem;
      color: #333;
    }

    .results .card .btn {
      margin-top: 8px;
      background-color: #1e88e5;
      color: white;
      padding: 8px 12px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      display: inline-block;
      transition: background 0.3s;
    }

    .results .card .btn:hover {
      background-color: #1565c0;
    }

    /* ==== Estilo de card para el formulario de búsqueda ==== */
    .find-form {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 25px 20px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      margin-bottom: 20px;
    }

    .find-form h2 {
      margin-top: 0;
      margin-bottom: 20px;
      color: #1e88e5;
      text-align: center;
    }

    .find-form label {
      display: block;
      font-weight: 500;
      margin-bottom: 6px;
      color: #333;
    }

    .find-form input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      margin-bottom: 12px;
    }

    .find-form .checkbox-group {
      display: flex;
      align-items: center;
      margin-bottom: 12px;
    }

    .find-form .checkbox-group input {
      width: auto;
      margin-right: 8px;
    }

    .find-form .btn.primary {
      width: 100%;
      background-color: #1e88e5;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
      font-size: 1rem;
      margin-bottom: 6px;
    }

    .find-form .btn.primary:hover {
      background-color: #1565c0;
    }

    .find-form .btn {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #1e88e5;
      background: white;
      color: #1e88e5;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.3s, color 0.3s;
      margin-bottom: 6px;
    }

    .find-form .btn:hover {
      background-color: #1e88e5;
      color: white;
    }

    .find-form .grid.cols-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    @media(max-width: 600px){
      .find-form .grid.cols-2 {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <?php $page='buscar'; require __DIR__ . '/partials/header.php'; ?>

  <main class="container">

    <form id="searchForm" class="find-form">
      <h2>Buscar viajes</h2>
      <div class="grid">
        <div class="col-3">
          <label>Origen</label>
          <input id="origen" placeholder="Córdoba" />
        </div>

        <div class="col-3">
          <label>Destino</label>
          <input id="destino" placeholder="Villa del Rosario" />
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
          <button class="btn" type="button" id="vozBuscar">🎤 Buscar por voz</button>
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

document.getElementById('limpiar').addEventListener('click', () => { 
  document.getElementById('searchForm').reset(); 
  offset=0; 
  buscar(); 
});

document.getElementById('toggleConductor').addEventListener('click', () => { 
  includeConductor = includeConductor ? 0 : 1; 
  buscar(); 
});

document.getElementById('prev').addEventListener('click', () => { 
  offset = Math.max(0, offset-limit); 
  buscar(); 
});

document.getElementById('next').addEventListener('click', () => { 
  offset += limit; 
  buscar(); 
});

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
          <a class="btn" href="/subete/frontend/detalle-viaje.php?id=${v.ID_Viaje}">Ver detalle</a>
        </div>
      </article>
    `;
  }).join('');
}

// primera carga
buscar();

// ---- Google Places Autocomplete ----
function setupAutocomplete(input) {
  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ['(cities)'],
    componentRestrictions: { country: 'ar' }
  });

  let valido = false;

  autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace();
    if (!place.geometry) {
      valido = false;
      input.value = '';
    } else {
      valido = true;
    }
  });

  input.addEventListener('blur', () => {
    if (!valido) input.value = '';
  });

  return { autocomplete, getValido: () => valido };
}

let origenSetup, destinoSetup;

function initAutocomplete() {
  origenSetup = setupAutocomplete(document.getElementById('origen'));
  destinoSetup = setupAutocomplete(document.getElementById('destino'));
}

// Validación al enviar formulario
document.getElementById('searchForm').addEventListener('submit', e => {
  e.preventDefault();

  if (!origenSetup.getValido()) {
    alert("Por favor seleccioná una ciudad válida en Origen");
    document.getElementById('origen').focus();
    return;
  }

  if (!destinoSetup.getValido()) {
    alert("Por favor seleccioná una ciudad válida en Destino");
    document.getElementById('destino').focus();
    return;
  }

  offset = 0;
  buscar();
});

</script>

<!-- Cargar Google Maps con Places API -->
<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBnDDeYhxpb6H8zyDJA38h7k_Xs-HT5OB4&libraries=places&callback=initAutocomplete">
</script>

<script>
// ==== BÚSQUEDA POR VOZ CON IA ====
const vozBtn = document.getElementById('vozBuscar');

if (vozBtn && 'webkitSpeechRecognition' in window) {
  const reconocimiento = new webkitSpeechRecognition();
  reconocimiento.lang = 'es-ES';
  reconocimiento.continuous = false;
  reconocimiento.interimResults = false;

  reconocimiento.onstart = () => {
    vozBtn.textContent = '🎙 Escuchando...';
    vozBtn.disabled = true;
  };

  reconocimiento.onresult = async (event) => {
    const texto = event.results[0][0].transcript;
    console.log('🎤 Texto reconocido:', texto);
    vozBtn.textContent = '🧠 Procesando...';

    try {
      const res = await fetch('/subete/backend/api/viajes/ia-buscar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mensaje: texto })
      });
      const data = await res.json();
      console.log('🧠 Respuesta IA:', data);

      if (data.origen) document.getElementById('origen').value = data.origen;
      if (data.destino) document.getElementById('destino').value = data.destino;
      if (data.fecha) document.getElementById('fecha').value = data.fecha;
      if (data.asientos) document.getElementById('asientos').value = data.asientos; // 👈 NUEVO

      buscar(); // ejecuta búsqueda automáticamente
    } catch (err) {
      console.error(err);
      alert('Error al procesar la búsqueda por voz.');
    } finally {
      vozBtn.textContent = '🎤 Buscar por voz';
      vozBtn.disabled = false;
    }
  };

  reconocimiento.onerror = (e) => {
    console.error('Error en reconocimiento de voz:', e);
    alert('No se pudo reconocer la voz. Intentá de nuevo.');
    vozBtn.textContent = '🎤 Buscar por voz';
    vozBtn.disabled = false;
  };

  vozBtn.addEventListener('click', () => {
    reconocimiento.start();
  });
} else if (vozBtn) {
  vozBtn.textContent = '🎤 No soportado';
  vozBtn.disabled = true;
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
