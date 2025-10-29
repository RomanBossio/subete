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
    #map { height: 400px; width: 100%; margin-top: 20px; border-radius: 8px; border: 1px solid #ccc; }
    #infoRuta, #infoClima { margin-top: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 6px; padding: 10px 12px; font-size: 15px; color: #333; line-height: 1.5; }
    .status-salida { margin-top: 8px; font-size: 14px; color:#555; }

    /* Estilos Conductor y Rating */
    .conductor-card { background: #f9f9f9; border-radius: 8px; padding: 12px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .conductor-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .conductor-nombre { font-weight: 600; font-size: 1.1rem; color: #333; }
    .stars { display: flex; align-items: center; font-size: 1.1rem; }
    .star { color: #ccc; margin-left: 2px; }
    .star.full { color: #007bff; }
    .star.half { background: linear-gradient(90deg, #007bff 50%, #ccc 50%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .star.empty { color: #ccc; }

    /* --- Estilos para el Modal y Link --- */
    .ver-comentarios-link { font-size: 0.9em; color: #007bff; cursor: pointer; text-decoration: underline; margin-top: 5px; display: inline-block; }
    .ver-comentarios-link:hover { color: #0056b3; }
    .modal-overlay { position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.6); display: none; /* Cambiado a none */ justify-content: center; align-items: center; z-index: 1050; padding: 20px; }
    .modal-content { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); width: 100%; max-width: 500px; position: relative; max-height: 80vh; overflow-y: auto; }
    .modal-close-btn { position: absolute; top: 10px; right: 15px; font-size: 1.8rem; font-weight: bold; color: #aaa; cursor: pointer; line-height: 1; }
    .modal-close-btn:hover { color: #333; }
    .modal-content h2 { margin-top: 0; margin-bottom: 15px; color: #333; text-align: left; font-size: 1.5rem; }
    #modal-body-calificaciones .calificacion-item { border-bottom: 1px solid #eee; padding: 10px 0; margin-bottom: 10px; }
    #modal-body-calificaciones .calificacion-item:last-child { border-bottom: none; margin-bottom: 0; }
    #modal-body-calificaciones .calificacion-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    #modal-body-calificaciones .calificacion-pasajero { font-weight: bold; color: #555; }
    #modal-body-calificaciones .calificacion-fecha { font-size: 0.8em; color: #999; }
    #modal-body-calificaciones .calificacion-comentario { color: #333; line-height: 1.5; margin-top: 8px; word-wrap: break-word; /* Para comentarios largos */ }
    #modal-body-calificaciones .stars { font-size: 1rem; }
    #modal-body-calificaciones .star { margin-left: 1px; }
    #modal-body-calificaciones .loading-text { text-align: center; padding: 20px; color: #777; }
    #modal-body-calificaciones .no-comments-text { text-align: center; padding: 20px; color: #777; font-style: italic; }
    #modal-body-calificaciones .error-text { text-align: center; padding: 20px; color: #dc3545; font-weight: bold; }
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

  <div id="modal-calificaciones" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close-btn">&times;</span>
      <h2>Calificaciones del Conductor</h2>
      <div id="modal-body-calificaciones">
        <p class="loading-text">Cargando...</p>
      </div>
    </div>
  </div>

</main>

<script>
const API_URL = '/subete/backend/api/viajes/detalle.php';
const API_RESERVA = '/subete/backend/api/viajes/reservas.php';
const API_CLIMA = '/subete/backend/api/viajes/clima.php';
const API_CALIFICACIONES = '/subete/backend/api/usuarios/obtener-calificaciones.php'; // Nueva API

const params = new URLSearchParams(location.search);
const id = Number(params.get('id') || 0);
const view  = document.getElementById('view');
const msg   = document.getElementById('msg');
const infoRuta = document.getElementById('infoRuta');
const infoClima = document.getElementById('infoClima');
// 'token' es obtenido desde header.php (si existe) o localStorage abajo

let viajeData = null;
let map, directionsService, directionsRenderer;

function showMsg(text, type='info') {
  msg.textContent = text;
  msg.className = type === 'success' ? 'alert success mt-2' : (type === 'error' ? 'alert error mt-2' : 'mt-2');
  setTimeout(() => msg.textContent = '', 4000);
}

function parseFechaLocal(str) {
  // Asegurarse de que el formato sea reconocible por new Date()
  return new Date(String(str).replace(' ', 'T'));
}

function humanDiff(now, target) {
  const ms = target - now;
  if (ms < 0) return '0 min'; // Evitar tiempo negativo si ya pasó
  const mins = Math.round(ms / 60000);
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  if (h <= 0) return `${m} min`;
  return `${h} h ${m} min`;
}

function getStarRatingHTML(rating) {
  const maxStars = 5;
  const fullStars = Math.floor(rating);
  let html = '';
  for (let i = 0; i < fullStars; i++) html += '<span class="star full">★</span>';
  for (let i = fullStars; i < maxStars; i++) html += '<span class="star empty">★</span>';
  // Considerar half stars si quieres más precisión
  return `<div class="stars" title="${rating.toFixed(1)} / 5">${html}</div>`;
}

async function cargarClima(ciudad, fechaHora) {
  if (!ciudad || !fechaHora) return;
  try {
    const fecha = fechaHora.split(' ')[0];
    const res = await fetch(`${API_CLIMA}?ciudad=${encodeURIComponent(ciudad)}&fecha=${fecha}`);
    const data = await res.json();
    if (data.ok) {
      infoClima.innerHTML = `<h3>🌤️ Clima estimado</h3><p>${data.condicion}, ${data.temp_min}°C a ${data.temp_max}°C.</p>`;
    } else { infoClima.innerHTML = ''; }
  } catch (e) { infoClima.innerHTML = ''; }
}

function initMap() {
  // Verificar si google.maps está cargado
  if (!viajeData || typeof google === 'undefined' || !google.maps) {
      console.warn("Google Maps API no lista o no hay datos del viaje para initMap.");
      return;
  }
  try {
      directionsService = new google.maps.DirectionsService();
      directionsRenderer = new google.maps.DirectionsRenderer();
      map = new google.maps.Map(document.getElementById("map")); // No necesita opciones iniciales si usamos fitBounds
      directionsRenderer.setMap(map);

      directionsService.route({
          origin: viajeData.Origen,
          destination: viajeData.Destino,
          travelMode: google.maps.TravelMode.DRIVING
      }, (result, status) => {
          if (status === "OK") {
              directionsRenderer.setDirections(result);
              const leg = result.routes[0].legs[0];
              infoRuta.innerHTML = `<p><strong>Duración aprox:</strong> ${leg.duration.text}</p><p><strong>Distancia:</strong> ${leg.distance.text}</p>`;
              map.fitBounds(result.routes[0].bounds); // Ajustar zoom automáticamente
          } else {
              console.error("Directions request failed due to " + status);
              infoRuta.innerHTML = '<p>No se pudo calcular la ruta.</p>';
          }
      });
  } catch (e) {
      console.error("Error inicializando mapa:", e);
      infoRuta.innerHTML = '<p>Error al mostrar el mapa.</p>';
  }
}

(async () => {
  if (!id) {
    view.textContent = 'Falta ID del viaje.';
    return;
  }
  try {
    const token = localStorage.getItem('token'); // Obtener token aquí
    const res = await fetch(`${API_URL}?id=${id}`);
    const data = await res.json();

    if (!data.ok) {
      view.textContent = data.error || 'Error al cargar el viaje';
      return;
    }

    const v = data.viaje;
    viajeData = v; // Guardar datos para usar en initMap

    const encom = Number(v.Permite_Encomiendas) === 1 ? '✔ Acepta' : 'No acepta';
    const precio = (Number(v.Precio) || 0).toLocaleString('es-AR');
    let disponibles = Number(v.Lugares_Disponibles) || 0;
    const salida = parseFechaLocal(v.Fecha_Hora_Salida);
    const veinteMinutosEnMs = 20 * 60 * 1000;

    view.innerHTML = `
      <div class="viaje-header"><h2>${v.Origen} → ${v.Destino}</h2></div>
      <div class="viaje-detalles">
        <p><strong>Salida:</strong> ${v.Fecha_Hora_Salida}</p>
        <p><strong>Tipo de vehículo:</strong> ${v.Tipo_Vehiculo ?? 'No especificado'}</p>
        <p><strong>Asientos disponibles:</strong> <span id="lugares-disponibles">${disponibles}</span></p>
        <p><strong>Precio:</strong> $${precio}</p>
        <p><strong>Encomiendas:</strong> ${encom}</p>
        ${v.Detalles ? `<p class="extra">Detalles: ${v.Detalles}</p>` : '' }
        <p id="status-hora" class="status-salida"></p> </div>
      <div class="conductor-card">
        <h3>Conductor</h3>
        <div class="conductor-header">
          <p class="conductor-nombre">${v.Conductor_Nombre ?? ''} ${v.Conductor_Apellido ?? ''}</p>
          ${getStarRatingHTML(Number(v.Conductor_Rating) || 0)}
        </div>
        <a class="ver-comentarios-link" data-conductor-id="${v.ID_Usuario}">Ver comentarios</a>
        ${v.Conductor_Telefono ? `<p class="muted" style="margin-top: 8px;">📞 ${v.Conductor_Telefono}</p>` : ''}
      </div>
      <div class="btn-group">
        <label>Cantidad: <input type="number" id="cantidad" value="1" min="1" max="${disponibles}" class="input-asientos" style="width: 70px;" /></label>
        <button class="btn reservar" id="btn-reservar">Reservar</button>
      </div>`;

    const btnReservar = document.getElementById('btn-reservar');
    const inputCantidad = document.getElementById('cantidad');
    const lugaresSpan = document.getElementById('lugares-disponibles');

    function actualizarEstadoBotonYTexto() {
      const ahora = new Date();
      const elStatus = document.getElementById('status-hora');
      const tiempoRestanteMs = salida.getTime() - ahora.getTime();
      const tiempoAgotado = tiempoRestanteMs <= veinteMinutosEnMs;

      if (elStatus) {
        if (tiempoRestanteMs < 0) {
          elStatus.textContent = 'La hora de salida ya pasó.';
        } else if (tiempoAgotado) {
          elStatus.textContent = 'El tiempo para reservar ha finalizado.';
        } else {
          elStatus.textContent = `Aún no llegó la hora de salida (faltan ${humanDiff(ahora, salida)}).`;
        }
      }
      if (btnReservar) {
          // Deshabilitar si no hay token, no hay disponibles, o tiempo agotado
          btnReservar.disabled = (!token || disponibles <= 0 || tiempoAgotado);
      }
    }

    actualizarEstadoBotonYTexto(); // Llamada inicial
    const intervalId = setInterval(actualizarEstadoBotonYTexto, 30000); // Actualizar cada 30s

    function actualizarDisponibles(nuevos) {
        disponibles = Number(nuevos);
        if (lugaresSpan) lugaresSpan.textContent = disponibles;
        if (inputCantidad) inputCantidad.max = disponibles;
        if (inputCantidad && Number(inputCantidad.value) > disponibles) {
            inputCantidad.value = disponibles;
        }
        actualizarEstadoBotonYTexto(); // Re-evaluar estado del botón
    }

    btnReservar.addEventListener('click', async () => {
      if (!token) { showMsg('Debes iniciar sesión para reservar.', 'error'); return; }
      const cantidad = Number(inputCantidad.value);
      if (cantidad < 1 || cantidad > disponibles) { showMsg('Cantidad inválida o no hay suficientes asientos.', 'error'); return; }

      try {
        btnReservar.disabled = true; // Deshabilitar mientras procesa
        const resReserva = await fetch(API_RESERVA, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
          body: JSON.stringify({ id_viaje: id, cantidad: cantidad })
        });
        const dataReserva = await resReserva.json();

        if (dataReserva.ok) {
          showMsg(dataReserva.msg || 'Reserva realizada con éxito.', 'success');
          actualizarDisponibles(dataReserva.lugares_restantes);
        } else {
          showMsg(dataReserva.error || 'Error al reservar.', 'error');
        }
      } catch (e) {
        showMsg('Error de conexión al reservar.', 'error');
      } finally {
        // Volver a habilitar o deshabilitar según corresponda después de la respuesta
        actualizarEstadoBotonYTexto();
      }
    });

    cargarClima(v.Destino, v.Fecha_Hora_Salida);

    // Intentar inicializar mapa después de cargar datos
    const checkGoogle = setInterval(() => {
        if(typeof google !== 'undefined' && google.maps){
            clearInterval(checkGoogle);
            initMap(); // Llamar a initMap aquí asegura que google.maps está listo
        }
    }, 100); // Chequear cada 100ms

  } catch (e) {
    console.error("Error al cargar datos del viaje:", e);
    view.innerHTML = '<p class="alert error">No se pudo cargar el detalle del viaje.</p>';
  }
})();


// --- Lógica del Modal de Calificaciones ---
const modal = document.getElementById('modal-calificaciones');
const modalBody = document.getElementById('modal-body-calificaciones');
const closeModalBtn = modal.querySelector('.modal-close-btn');

async function cargarYMostrarCalificaciones(idConductor) {
  if (!idConductor || isNaN(idConductor)) {
      console.error("ID de conductor inválido para cargar calificaciones.");
      return;
  }

  modalBody.innerHTML = '<p class="loading-text">Cargando calificaciones...</p>';
  modal.style.display = 'flex';

  try {
    const res = await fetch(`${API_CALIFICACIONES}?id_conductor=${idConductor}`);
    if (!res.ok) {
        throw new Error(`Error ${res.status}: ${res.statusText}`);
    }
    const data = await res.json();

    if (data.ok && data.calificaciones && data.calificaciones.length > 0) {
      modalBody.innerHTML = data.calificaciones.map(calif => `
        <div class="calificacion-item">
          <div class="calificacion-header">
            <span class="calificacion-pasajero">${htmlspecialchars(calif.NombrePasajero) || 'Pasajero'}</span>
            ${getStarRatingHTML(Number(calif.Puntuacion) || 0)}
          </div>
          ${calif.Comentario ? `<p class="calificacion-comentario">${htmlspecialchars(calif.Comentario)}</p>` : ''}
          <span class="calificacion-fecha">${new Date(calif.Fecha).toLocaleDateString('es-AR', { year: 'numeric', month: 'short', day: 'numeric'})}</span>
        </div>
      `).join('');
    } else if (data.ok) {
      modalBody.innerHTML = '<p class="no-comments-text">Este conductor aún no tiene comentarios.</p>';
    } else {
      modalBody.innerHTML = `<p class="error-text">Error: ${data.error || 'Error desconocido'}</p>`;
    }
  } catch (error) {
    console.error("Error al obtener calificaciones:", error);
    modalBody.innerHTML = '<p class="error-text">No se pudieron cargar las calificaciones.</p>';
  }
}

// Función simple para escapar HTML
function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Event listener para abrir el modal (delegación)
document.addEventListener('click', function(event) {
  if (event.target.matches('.ver-comentarios-link')) {
    event.preventDefault();
    const conductorId = event.target.getAttribute('data-conductor-id');
    cargarYMostrarCalificaciones(parseInt(conductorId)); // Asegurar que sea número
  }
});

// Event listener para cerrar el modal
function closeModal() {
  if (modal) modal.style.display = 'none';
}

if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
if(modal) modal.addEventListener('click', function(event) {
  if (event.target === modal) { // Si se hizo clic en el fondo (overlay)
    closeModal();
  }
});
// --- Fin Lógica del Modal ---


// Carga diferida del script de Google Maps al final
const gmapsScript = document.createElement('script');
gmapsScript.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyDOsUtRsZPG_LIRJtxULIBfPmG2XrCnJ4M&loading=async&libraries=places,marker`; // Carga async, incluye marker
gmapsScript.async = true;
gmapsScript.defer = true;
// No necesita callback aquí, el script principal espera a 'google'
document.head.appendChild(gmapsScript);

</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>