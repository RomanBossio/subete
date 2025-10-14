<?php $page=''; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Detalle de viaje</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <link rel="stylesheet" href="/subete/frontend/css/detalle-viaje.css?v=1.0">
  <style>
    #map {
      height: 400px;
      width: 100%;
      margin-top: 20px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    #infoRuta, #infoClima {
      margin-top: 15px;
      background: #f9f9f9;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 10px 12px;
      font-size: 15px;
      color: #333;
      line-height: 1.5;
    }
    .status-salida { margin-top: 8px; font-size: 14px; color:#555; }

    /* ⭐ Estilos para el rating */
    .conductor-card {
      background: #f9f9f9;
      border-radius: 8px;
      padding: 12px;
      margin-top: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .conductor-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .conductor-nombre {
      font-weight: 600;
      font-size: 1.1rem;
      color: #333;
    }
    .stars {
      display: flex;
      align-items: center;
      font-size: 1.1rem;
    }
    .star {
      color: #ccc;
      margin-left: 2px;
    }
    .star.full {
      color: #007bff;
    }
    .star.half {
      background: linear-gradient(90deg, #007bff 50%, #ccc 50%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .star.empty {
      color: #ccc;
    }
  </style>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<main class="container detalle-container">
  <a href="/subete/frontend/buscar.php" class="btn volver">← Volver</a>
  <h1>Detalle de viaje</h1>

  <div id="view" class="viaje-card"></div>
  <div id="infoRuta"></div>
  <div id="infoClima"></div>
  <div id="msg" class="mt-2"></div>
  <div id="map"></div>
</main>

<script>
const API_URL = '/subete/backend/api/viajes/detalle.php';
const API_RESERVA = '/subete/backend/api/viajes/reservas.php';
const API_CLIMA = '/subete/backend/api/viajes/clima.php';

const params = new URLSearchParams(location.search);
const id = Number(params.get('id') || 0);
const view  = document.getElementById('view');
const msg   = document.getElementById('msg');
const infoRuta = document.getElementById('infoRuta');
const infoClima = document.getElementById('infoClima');
const token = localStorage.getItem('token');

let viajeData = null;
let map, directionsService, directionsRenderer;

function showMsg(text, type='info') {
  msg.textContent = text;
  msg.className = type === 'success' ? 'alert success mt-2' : (type === 'error' ? 'alert error mt-2' : 'mt-2');
}

function parseFechaLocal(str) {
  return new Date(String(str).replace(' ', 'T'));
}

function humanDiff(now, target) {
  const ms = target - now;
  const abs = Math.abs(ms);
  const mins = Math.round(abs / 60000);
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  if (h <= 0) return `${m} min`;
  return `${h} h ${m} min`;
}

// ⭐ Función para generar estrellas
function getStarRatingHTML(rating) {
  const maxStars = 5;
  const fullStars = Math.floor(rating);
  const halfStar = rating % 1 >= 0.5;
  let html = '';

  for (let i = 0; i < fullStars; i++) html += '<span class="star full">★</span>';
  if (halfStar) html += '<span class="star half">★</span>';
  for (let i = fullStars + (halfStar ? 1 : 0); i < maxStars; i++) html += '<span class="star empty">★</span>';

  return `<div class="stars" title="${rating.toFixed(1)} / 5">${html}</div>`;
}

async function cargarClima(ciudad, fechaHora) {
  if (!ciudad || !fechaHora) return;
  try {
    const fecha = fechaHora.split(' ')[0];
    const res = await fetch(`${API_CLIMA}?ciudad=${encodeURIComponent(ciudad)}&fecha=${fecha}`);
    const data = await res.json();

    if (data.ok) {
      infoClima.innerHTML = `
        <h3>🌤️ Clima estimado el día del viaje</h3>
        <p><strong>${data.ciudad}</strong> - ${data.fecha}</p>
        <p><strong>Condición:</strong> ${data.condicion}</p>
        <p><strong>Temperatura:</strong> ${data.temp_min}°C a ${data.temp_max}°C</p>
        ${data.precipitacion ? `<p><strong>Prob. de lluvia:</strong> ${data.precipitacion}%</p>` : ''}
      `;
    } else {
      infoClima.innerHTML = `<p>No se pudo obtener el clima del destino.</p>`;
    }
  } catch (e) {
    infoClima.innerHTML = `<p>Error al cargar el clima.</p>`;
  }
}

function initMap() {
  if (!viajeData) return;
  directionsService = new google.maps.DirectionsService();
  directionsRenderer = new google.maps.DirectionsRenderer();
  map = new google.maps.Map(document.getElementById("map"), {
    zoom: 7,
    center: { lat: -34.6037, lng: -58.3816 }
  });
  directionsRenderer.setMap(map);

  const geocoder = new google.maps.Geocoder();

  geocoder.geocode({ address: viajeData.Origen }, (results, status) => {
    if (status === "OK") {
      new google.maps.Marker({
        map: map,
        position: results[0].geometry.location,
        label: "O"
      });
    }
  });

  geocoder.geocode({ address: viajeData.Destino }, (results, status) => {
    if (status === "OK") {
      new google.maps.Marker({
        map: map,
        position: results[0].geometry.location,
        label: "D"
      });
    }
  });

  directionsService.route({
    origin: viajeData.Origen,
    destination: viajeData.Destino,
    travelMode: google.maps.TravelMode.DRIVING
  }, (result, status) => {
    if (status === "OK") {
      directionsRenderer.setDirections(result);

      const leg = result.routes[0].legs[0];
      const distancia = leg.distance.text;
      const duracion = leg.duration.text;

      infoRuta.innerHTML = `
        <p><strong>Duración aproximada:</strong> ${duracion}</p>
        <p><strong>Distancia:</strong> ${distancia}</p>
      `;

      const bounds = new google.maps.LatLngBounds();
      const route = result.routes[0].overview_path;
      route.forEach(point => bounds.extend(point));
      map.fitBounds(bounds);
    }
  });
}

(async () => {
  if (!id) {
    view.textContent = 'Falta id';
    return;
  }

  try {
    const res = await fetch(`${API_URL}?id=${id}`);
    const data = await res.json();

    if (!data.ok) {
      view.textContent = data.error || 'Error al cargar el viaje';
      return;
    }

    const v = data.viaje;
    viajeData = v;

    const encom = Number(v.Permite_Encomiendas) === 1 ? '✔ Acepta encomiendas' : 'No acepta encomiendas';
    const precio = (Number(v.Precio) || 0).toLocaleString('es-AR');
    let disponibles = Number(v.Lugares_Disponibles) || 0;
    const salida = parseFechaLocal(v.Fecha_Hora_Salida);
    const ahora  = new Date();
    const yaPaso = salida.getTime() <= ahora.getTime();
    const diffTxt = humanDiff(ahora, salida);
    const estadoHoraHTML = yaPaso
      ? `La hora de salida ya pasó`
      : `Aún no llegó la hora de salida (faltan ${diffTxt})`;

    view.innerHTML = `
      <div class="viaje-header">
        <h2>${v.Origen} → ${v.Destino}</h2>
        <p class="sub-info">Salida: <strong>${v.Fecha_Hora_Salida}</strong></p>
        <p class="sub-info">Estado: <span class="estado">${v.Estado}</span></p>
      </div>

      <div class="viaje-detalles">
        <p><strong>Asientos disponibles:</strong> <span id="lugares-disponibles">${disponibles}</span></p>
        <p><strong>Precio:</strong> $${precio}</p>
        <p><strong>Encomiendas:</strong> ${encom}</p>
        ${v.Detalles ? `<p class="extra">${v.Detalles}</p>` : '' }
        <p id="status-hora" class="status-salida">${estadoHoraHTML}</p>
      </div>

      <!-- ⭐ Conductor con rating -->
      <div class="conductor-card">
        <h3>Conductor</h3>
        <div class="conductor-header">
          <p class="conductor-nombre">${v.Conductor_Nombre ?? ''} ${v.Conductor_Apellido ?? ''}</p>
          <div class="rating">
            ${getStarRatingHTML(Number(v.Conductor_Rating) || 0)}
          </div>
        </div>
        ${v.Conductor_Telefono ? `<p class="muted">📞 ${v.Conductor_Telefono}</p>` : ''}
      </div>

      <div class="btn-group">
        <label style="display:flex;align-items:center;gap:8px">
          <span class="muted">Cantidad:</span>
          <input type="number" id="cantidad" value="1" min="1" max="${disponibles}" class="input-asientos" style="width:80px;padding:6px;border-radius:6px;border:1px solid var(--stroke)" />
        </label>
        <button class="btn reservar" id="btn-reservar" ${(!token || disponibles <= 0) ? 'disabled' : ''}>Reservar</button>
      </div>
    `;

    // ⏱ Actualización automática del estado
    setInterval(() => {
      const ahora2 = new Date();
      const yaPaso2 = salida.getTime() <= ahora2.getTime();
      const txt = yaPaso2 ? 'La hora de salida ya pasó' : `Aún no llegó la hora de salida (faltan ${humanDiff(ahora2, salida)})`;
      const el = document.getElementById('status-hora');
      if (el) el.textContent = txt;
    }, 60000);

    const btn = document.getElementById('btn-reservar');
    const inputCantidad = document.getElementById('cantidad');
    const lugaresSpan = document.getElementById('lugares-disponibles');

    function actualizarDisponibles(nuevo) {
      disponibles = Number(nuevo) || 0;
      lugaresSpan.textContent = disponibles;
      inputCantidad.max = String(disponibles);
      if (disponibles <= 0) {
        btn.disabled = true;
        inputCantidad.value = 0;
        inputCantidad.disabled = true;
      } else {
        inputCantidad.disabled = false;
        if (Number(inputCantidad.value) > disponibles) inputCantidad.value = disponibles;
      }
    }

    btn.addEventListener('click', async () => {
      const tokenLocal = localStorage.getItem('token');
      if (!tokenLocal) {
        showMsg('Debes iniciar sesión para reservar', 'error');
        return;
      }

      const cantidad = Math.max(1, Math.floor(Number(inputCantidad.value) || 1));
      if (cantidad < 1) { showMsg('La cantidad debe ser al menos 1', 'error'); return; }
      if (cantidad > disponibles) { showMsg('No hay suficientes asientos disponibles', 'error'); return; }

      try {
        const resReserva = await fetch(API_RESERVA, {
          method: 'POST',
          headers: { 'Content-Type':'application/json','Authorization':'Bearer ' + tokenLocal },
          body: JSON.stringify({ id_viaje: id, cantidad: cantidad })
        });
        const dataReserva = await resReserva.json();

        if (dataReserva.ok) {
          showMsg(dataReserva.msg || 'Reserva realizada con éxito','success');
          const nuevos = Number(dataReserva.lugares_restantes ?? dataReserva.lugaresRestantes ?? disponibles - cantidad);
          actualizarDisponibles(nuevos);
        } else {
          showMsg(dataReserva.error || 'Error al reservar','error');
        }

      } catch(e) { showMsg('Error al reservar (problema de conexión)','error'); }
    });

    cargarClima(v.Destino, v.Fecha_Hora_Salida);
    initMap();

  } catch (e) {
    console.error(e);
    view.textContent = 'No se pudo cargar el detalle.';
  }
})();

const gmapsScript = document.createElement('script');
gmapsScript.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyDOsUtRsZPG_LIRJtxULIBfPmG2XrCnJ4M";
gmapsScript.async = true;
gmapsScript.defer = true;
document.head.appendChild(gmapsScript);

</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>