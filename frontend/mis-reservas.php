<?php $page=''; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Mis reservas</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<main class="container">
  <h1 class="section-title">Mis reservas</h1>
  <div id="msg" class="mt-2"></div>
  <div id="reservas-list" class="results mt-3"></div>

  <h2 class="section-title mt-4">Viajes que publicaste</h2>
  <div id="viajes-futuros" class="results mt-2"></div>

  <h3 class="section-title mt-4">Historial de viajes</h3>
  <div id="viajes-pasados" class="results mt-2"></div>
  <div class="pagination mt-3">
    <button id="prev-page" class="btn" disabled>Anterior</button>
    <button id="next-page" class="btn">Siguiente</button>
  </div>
</main>
<style>
  /* =========================
   Estilos para tarjetas de viajes
========================= */
.results {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.card {
  background-color: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transition: transform 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

.card h3 {
  font-size: 1.2rem;
  margin-bottom: 4px;
  color: #007bff;
}

.card p {
  margin: 2px 0;
  font-size: 0.95rem;
  color: #555;
}

.card strong {
  color: #222;
}

.card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.card-actions .btn {
  flex: 1;
  padding: 6px 12px;
  font-size: 0.9rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
}

.card-actions .btn:hover {
  opacity: 0.9;
}

.badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #fff;
}

.badge.success { background-color: #28a745; }
.badge.info    { background-color: #17a2b8; }
.badge.warning { background-color: #ffc107; color: #222; }
.badge.error   { background-color: #dc3545; }

.calificar-box {
  margin-top: 8px;
  border-top: 1px solid #eee;
  padding-top: 8px;
}

.calificar-box button {
  font-size: 0.85rem;
  padding: 4px 10px;
}

.calificar-form {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.calificar-form input[type="text"],
.calificar-form select {
  padding: 6px 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 0.9rem;
  width: 100%;
}

.calificar-msg {
  font-size: 0.85rem;
}

ul {
  padding-left: 16px;
  margin: 4px 0 0 0;
}

ul li {
  margin-bottom: 6px;
  font-size: 0.9rem;
}

.muted {
  color: #777;
  font-size: 0.85rem;
}

.section-title {
  font-size: 1.4rem;
  color: #0a0b0dff;
  margin-bottom: 8px;
}

/* Pagination buttons */
.pagination .btn {
  padding: 6px 12px;
  border-radius: 6px;
  background-color: #007bff;
  color: #fff;
  border: none;
  cursor: pointer;
}

.pagination .btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}
/* =========================
   Lista de pasajeros
========================= */
.card ul {
  list-style-type: none; /* quitar los bullets */
  padding-left: 0;
  margin-top: 4px;
}

.card ul li {
  font-family: 'Roboto', 'Arial', sans-serif; /* fuente más moderna */
  font-size: 0.95rem;
  color: #333;
  line-height: 1.4;
  padding: 4px 0;
  border-bottom: 1px solid #eee;
}

.card ul li:last-child {
  border-bottom: none;
}

.card ul li strong {
  color: #007bff; /* resalta nombres */
  font-weight: 600;
}

</style>
<script>
/* =========================
   CONFIG & ENDPOINTS
========================= */

const API_RESERVAS   = '/subete/backend/api/viajes/mis-reservas.php';
const API_CANCELAR   = '/subete/backend/api/viajes/cancelar-reserva.php';
const API_VIAJES     = '/subete/backend/api/viajes/mis-viajes-publicados.php';
const API_PASAJEROS  = '/subete/backend/api/viajes/pasajeros-por-viaje.php';
const API_ELIMINAR_PASAJERO = '/subete/backend/api/viajes/eliminar-pasajero.php';
const API_ELIMINAR_VIAJE    = '/subete/backend/api/viajes/eliminar-viaje.php';
const API_PAGO      = '/subete/backend/api/pagos/crear-preferencia.php'; // MercadoPago
const API_INICIAR   = '/subete/backend/api/viajes/iniciar_viaje.php';
const API_FINALIZAR = '/subete/backend/api/viajes/finalizar_viaje.php';
// Efectivo:
const API_EFECTIVO_INICIAR   = '/subete/backend/api/pagos/efectivo_iniciar.php';
const API_EFECTIVO_CONFIRMAR = '/subete/backend/api/pagos/efectivo_confirmar.php';
// Calificaciones:
const API_CALIFICAR = '/subete/backend/api/calificaciones/crear.php';

/* =========================
   UTIL: parseo de fecha local
========================= */
function parseFechaLocal(str) {
  const m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,3}))?)?$/);
  if (!m) return new Date(NaN);
  const [ , Y, Mo, D, H, Mi, S='0', Ms='0' ] = m;
  return new Date(+Y, +Mo - 1, +D, +H, +Mi, +S, +(String(Ms).padEnd(3,'0')));
}

/* =========================
   ELEMENTOS
========================= */
const msg = document.getElementById('msg');
const reservasList = document.getElementById('reservas-list');
const viajesFuturos = document.getElementById('viajes-futuros');
const viajesPasados = document.getElementById('viajes-pasados');
const prevBtn = document.getElementById('prev-page');
const nextBtn = document.getElementById('next-page');

let historialPagina = 0;
const historialPorPagina = 6;
let historialViajes = [];

/* =========================
   INICIO
========================= */
if (!token) {
  msg.textContent = 'Debes iniciar sesión para ver tus reservas';
  msg.className = 'alert error mt-2';
} else {
  cargarReservas();
  cargarViajesPublicados();
}

/* =========================
   RESERVAS (PASAJERO)
========================= */
function getCashFlagKey(idReserva) { return `cashPending:${idReserva}`; }

async function cargarReservas() {
  try {
    const res = await fetch(API_RESERVAS, { headers: { 'Authorization': 'Bearer ' + token } });
    const data = await res.json();

    if (!data.ok) {
      msg.textContent = data.error || 'Error al cargar reservas';
      msg.className = 'alert error mt-2';
      return;
    }

    if (!data.reservas || data.reservas.length === 0) {
      reservasList.innerHTML = '<p class="muted">No tienes reservas.</p>';
      return;
    }

    reservasList.innerHTML = data.reservas.map(r => {
      const estadoReserva = r.EstadoReserva || r.estado || '';
      const precioTotal = r.precio_total
        ? r.precio_total
        : ((parseFloat(r.Precio || 0) * parseInt(r.cantidad || 0)).toFixed(2));
      const idViaje   = r.id_viaje || r.ID_Viaje || r.Id_Viaje;
      const idReserva = r.id_reserva || r.ID_Reserva;

      if (estadoReserva === 'pagado') {
        localStorage.removeItem(getCashFlagKey(idReserva));
      }
      const cashPending = localStorage.getItem(getCashFlagKey(idReserva)) === '1';

      let estadoVisual = '';
      if (estadoReserva === 'pagado') {
        estadoVisual = `<p class="badge success">✅ Pagado en efectivo</p>`;
      } else if (cashPending) {
        estadoVisual = `<p class="badge info">💵 Pago en efectivo iniciado — esperando confirmación del conductor</p>`;
      }

      // ¿el viaje ya pasó? (UI; el backend valida “Completado” al guardar)
      const fecha = new Date(String(r.Fecha_Hora_Salida).replace(' ', 'T'));
      const yaPaso = !isNaN(fecha) && (fecha.getTime() < Date.now());

      // Bloque Calificar
      const bloqueCalificar = (yaPaso && estadoReserva !== 'cancelado') ? `
        <div class="calificar-box" data-viaje="${idViaje}">
          <button class="btn outline abrir-calificar">Calificar / Editar calificación</button>
          <form class="calificar-form" style="display:none; gap:8px; margin-top:8px;">
            <label>Puntuación:
              <select name="p" required>
                <option value="">—</option>
                <option value="5">★★★★★ (5)</option>
                <option value="4">★★★★☆ (4)</option>
                <option value="3">★★★☆☆ (3)</option>
                <option value="2">★★☆☆☆ (2)</option>
                <option value="1">★☆☆☆☆ (1)</option>
              </select>
            </label>
            <input type="text" name="c" placeholder="Comentario (opcional)" maxlength="300" style="flex:1; min-width:220px;">
            <button class="btn primary enviar-calificacion" type="submit">Guardar</button>
          </form>
          <div class="calificar-msg muted" style="margin-top:6px;"></div>
        </div>
      ` : '';

      return `
      <div class="card" data-id="${idReserva}">
        <h3>${r.Origen} → ${r.Destino}</h3>
        <p class="muted">Fecha salida: ${r.Fecha_Hora_Salida}</p>
        <p>Asientos reservados: ${r.cantidad}</p>
        <p>Precio: $${precioTotal}</p>
        <p>Conductor: <strong>${r.Conductor_Nombre} ${r.Conductor_Apellido}</strong></p>
        <p>Estado: <strong>${estadoReserva}</strong></p>
        <div class="card-status">${estadoVisual}</div>
        <div class="row card-actions">
          <button class="btn cancelar">Cancelar</button>
          ${estadoReserva === 'pendiente' && !cashPending ? `
            <button class="btn pagar"
              data-precio="${precioTotal}"
              data-cantidad="${r.cantidad}"
              data-titulo="${r.Origen} → ${r.Destino}">
              Pagar con MP
            </button>
            <button class="btn primary pagar-efectivo"
              data-viaje="${idViaje}"
              data-reserva="${idReserva}">
              Pagar en efectivo
            </button>
          ` : ''}
        </div>
        ${bloqueCalificar}
      </div>`;
    }).join('');

    // Cancelar reserva
    document.querySelectorAll('.btn.cancelar').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const card = e.target.closest('.card');
        const id_reserva = card.dataset.id;
        if (!confirm('¿Estás seguro de cancelar esta reserva?')) return;

        try {
          const resCancel = await fetch(API_CANCELAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ id_reserva })
          });
          const dataCancel = await resCancel.json();
          if (dataCancel.ok) {
            localStorage.removeItem(getCashFlagKey(id_reserva));
            card.remove();
          } else {
            alert(dataCancel.error || 'Error al cancelar');
          }
        } catch {
          alert('Error al cancelar reserva');
        }
      });
    });

    // Mercado Pago (opcional)
    document.querySelectorAll('.btn.pagar').forEach(btn => {
      btn.addEventListener('click', async () => {
        const titulo = btn.dataset.titulo;
        const precio = parseFloat(btn.dataset.precio);
        const cantidad = parseInt(btn.dataset.cantidad);
        if (!titulo || isNaN(precio) || isNaN(cantidad)) { alert("Datos de reserva inválidos."); return; }
        try {
          const resPago = await fetch(API_PAGO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ titulo, precio, cantidad })
          });
          const dataPago = await resPago.json();
          if (dataPago.ok && dataPago.init_point) window.location.href = dataPago.init_point;
          else alert(dataPago.error || 'Error al iniciar el pago');
        } catch { alert('Error al conectar con el servidor'); }
      });
    });

    // Pagar en efectivo
    document.querySelectorAll('.btn.pagar-efectivo').forEach(btn => {
      btn.addEventListener('click', async () => {
        const idViaje = parseInt(btn.dataset.viaje, 10);
        const card = btn.closest('.card');
        const idReserva = card?.dataset?.id;
        if (!idViaje || !idReserva) { alert('Faltan datos de la reserva.'); return; }
        btn.disabled = true;
        try {
          const r = await fetch(API_EFECTIVO_INICIAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ ID_Viaje: idViaje })
          });
          const data = await r.json();
          if (!data.ok) throw new Error(data.error || 'No se pudo iniciar el pago en efectivo');

          localStorage.setItem(getCashFlagKey(idReserva), '1');

          const statusBox = card.querySelector('.card-status');
          const actions = card.querySelector('.card-actions');
          if (statusBox) {
            statusBox.innerHTML = `<p class="badge info">💵 Pago en efectivo iniciado — esperando confirmación del conductor</p>`;
          }
          if (actions) {
            actions.querySelectorAll('.pagar, .pagar-efectivo').forEach(el => el.remove());
          }
        } catch (e) {
          alert(e.message);
          btn.disabled = false;
        }
      });
    });

  } catch (err) {
    msg.textContent = 'Error al cargar reservas';
    msg.className = 'alert error mt-2';
  }
}

/* =========================
   VIAJES PUBLICADOS (CONDUCTOR)
========================= */
async function cargarViajesPublicados() {
  try {
    const res = await fetch(API_VIAJES, { headers: { 'Authorization': 'Bearer ' + token } });
    const data = await res.json();
    if (!data.ok) {
      viajesFuturos.innerHTML = '<p class="muted">Error al cargar tus viajes publicados.</p>';
      return;
    }

    const ahora = new Date();
    viajesFuturos.innerHTML = '';
    viajesPasados.innerHTML = '';
    historialViajes = [];

    for (const viaje of data.viajes) {
      const fechaViaje = parseFechaLocal(viaje.Fecha_Hora_Salida);
      const puedeIniciar   = !!viaje.canStart;
      const puedeFinalizar = !!viaje.canFinish;

      let accionesHTML = '';
      if (puedeIniciar)   accionesHTML += `<button class="btn iniciar-viaje" data-id="${viaje.ID_Viaje}">Iniciar</button>`;
      if (puedeFinalizar) accionesHTML += `<button class="btn primary finalizar-viaje" data-id="${viaje.ID_Viaje}">Finalizar</button>`;
      if (fechaViaje.getTime() > ahora.getTime()) accionesHTML += `<button class="btn eliminar-viaje" data-id="${viaje.ID_Viaje}">Eliminar viaje</button>`;

      const card = document.createElement('div');
      card.className = 'card';
      card.innerHTML = `
        <h3>${viaje.Origen} → ${viaje.Destino}</h3>
        <p class="muted">Fecha salida: ${viaje.Fecha_Hora_Salida}</p>
        <p>Lugares disponibles: <strong>${viaje.Lugares_Disponibles}</strong></p>
        <p>Estado: <strong>${viaje.Estado}</strong></p>
        <div class="acciones">${accionesHTML || '<span class="muted">Sin acciones</span>'}</div>
        <p><strong>Pasajeros:</strong> <span class="pasajeros-loading">Cargando...</span></p>
      `;

      let esHistorial = (viaje.Estado === 'Completado' || viaje.Estado === 'Cancelado');
      if (viaje.canStart || viaje.canFinish) esHistorial = false;

      if (esHistorial) {
        historialViajes.push(viaje);
      } else {
        viajesFuturos.appendChild(card);
      }

      try {
        const pasajerosRes = await fetch(API_PASAJEROS, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
          body: JSON.stringify({ id_viaje: viaje.ID_Viaje })
        });
        const pasajerosData = await pasajerosRes.json();
        const contenedor = card.querySelector('.pasajeros-loading');
        if (!pasajerosData.ok) {
          contenedor.textContent = 'Error al cargar pasajeros.';
        } else if (!Array.isArray(pasajerosData.pasajeros) || pasajerosData.pasajeros.length === 0) {
          contenedor.textContent = 'Sin pasajeros.';
        } else {
          contenedor.innerHTML = '<ul>' + pasajerosData.pasajeros.map(p => `
            <li>
              <strong>${p.Nombre} ${p.Apellido}</strong><br>
              <span class="muted">Tel:</span> <a href="tel:${p.Telefono}">${p.Telefono}</a><br>
              <span class="muted">Asientos:</span> ${p.cantidad}<br>
              <span class="muted">Reserva:</span> ${p.EstadoReserva}<br>
              ${
                p.EstadoReserva === 'pendiente'
                  ? `<button class="btn success cobrar-efectivo"
                       data-viaje="${viaje.ID_Viaje}"
                       data-usuario="${p.ID_Usuario}">
                       Cobrado en efectivo
                     </button>`
                  : ''
              }
              ${(fechaViaje.getTime() > ahora.getTime()) ? `<button class="btn eliminar-pasajero" data-id="${p.ID_Reserva}">Eliminar</button>` : ''}
            </li>`).join('') + '</ul>';
        }
      } catch { /* noop */ }
    }

    mostrarHistorial();
  } catch (err) {
    viajesFuturos.innerHTML = '<p class="muted">Error al cargar tus viajes publicados.</p>';
  }
}

/* =========================
   HISTORIAL (paginación)
========================= */
function mostrarHistorial() {
  viajesPasados.innerHTML = '';
  const inicio = historialPagina * historialPorPagina;
  const fin = inicio + historialPorPagina;
  const mostrar = historialViajes.slice(inicio, fin);

  mostrar.forEach(viaje => {
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML = `
      <h3>${viaje.Origen} → ${viaje.Destino}</h3>
      <p class="muted">Fecha salida: ${viaje.Fecha_Hora_Salida}</p>
      <p>Lugares disponibles: <strong>${viaje.Lugares_Disponibles}</strong></p>
      <p><strong>Pasajeros:</strong> <span class="pasajeros-loading">Cargando...</span></p>
    `;
    viajesPasados.appendChild(card);

    fetch(API_PASAJEROS, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({ id_viaje: viaje.ID_Viaje })
    }).then(res => res.json()).then(pasajerosData => {
      const contenedor = card.querySelector('.pasajeros-loading');
      if (!pasajerosData.ok) contenedor.textContent = 'Error al cargar pasajeros.';
      else if (!Array.isArray(pasajerosData.pasajeros) || pasajerosData.pasajeros.length === 0) contenedor.textContent = 'Sin pasajeros.';
      else {
        contenedor.innerHTML = '<ul>' + pasajerosData.pasajeros.map(p =>
          `<li>
            <strong>${p.Nombre} ${p.Apellido}</strong><br>
            <span class="muted">Tel:</span> <a href="tel:${p.Telefono}">${p.Telefono}</a><br>
            <span class="muted">Asientos:</span> ${p.cantidad}<br>
          </li>`).join('') + '</ul>';
      }
    });
  });

  prevBtn.disabled = historialPagina === 0;
  nextBtn.disabled = (historialPagina + 1) * historialPorPagina >= historialViajes.length;
}

prevBtn.addEventListener('click', () => {
  if (historialPagina > 0) { historialPagina--; mostrarHistorial(); }
});
nextBtn.addEventListener('click', () => {
  if ((historialPagina + 1) * historialPorPagina < historialViajes.length) { historialPagina++; mostrarHistorial(); }
});

/* =========================
   LISTENERS GLOBALES
========================= */
// Iniciar viaje
document.addEventListener('click', async (e) => {
  if (e.target.classList.contains('iniciar-viaje')) {
    const id_viaje = e.target.dataset.id;
    e.target.disabled = true;
    try {
      const r = await fetch(API_INICIAR, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ ID_Viaje: id_viaje })
      });
      const data = await r.json();
      if (!data.ok) throw new Error(data.error || 'No se pudo iniciar el viaje');
      msg.textContent = 'Viaje iniciado';
      msg.className = 'alert success mt-2';
      await cargarViajesPublicados();
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'alert error mt-2';
      e.target.disabled = false;
    }
  }
});

// Finalizar viaje
document.addEventListener('click', async (e) => {
  if (e.target.classList.contains('finalizar-viaje')) {
    const id_viaje = e.target.dataset.id;
    e.target.disabled = true;
    try {
      const r = await fetch(API_FINALIZAR, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ ID_Viaje: id_viaje })
      });
      const data = await r.json();
      if (!data.ok) throw new Error(data.error || 'No se pudo finalizar el viaje');
      msg.textContent = 'Viaje finalizado';
      msg.className = 'alert success mt-2';
      await cargarViajesPublicados();
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'alert error mt-2';
      e.target.disabled = false;
    }
  }
});

// Conductor confirma cobro en efectivo
document.addEventListener('click', async (e) => {
  if (e.target.classList.contains('cobrar-efectivo')) {
    const id_viaje   = parseInt(e.target.dataset.viaje, 10);
    const id_usuario = parseInt(e.target.dataset.usuario, 10);
    if (!id_viaje || !id_usuario) { alert('Faltan datos para confirmar.'); return; }
    e.target.disabled = true;
    try {
      const r = await fetch(API_EFECTIVO_CONFIRMAR, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ ID_Viaje: id_viaje, ID_Usuario: id_usuario })
      });
      const data = await r.json();
      if (!data.ok) throw new Error(data.error || 'No se pudo confirmar el cobro');
      msg.textContent = 'Cobro en efectivo confirmado';
      msg.className = 'alert success mt-2';
      await cargarViajesPublicados();
    } catch (err) {
      alert(err.message);
      e.target.disabled = false;
    }
  }
});

// Eliminar pasajero
document.addEventListener('click', async (e) => {
  if (e.target.classList.contains('eliminar-pasajero')) {
    const id_reserva = e.target.dataset.id;
    if (!confirm('¿Eliminar este pasajero?')) return;
    try {
      const res = await fetch(API_ELIMINAR_PASAJERO, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ id_reserva })
      });
      const data = await res.json();
      if (data.ok) e.target.closest('li').remove();
      else alert(data.error || 'Error al eliminar pasajero');
    } catch { alert('Error de conexión'); }
  }
});

// Eliminar viaje
document.addEventListener('click', async (e) => {
  if (e.target.classList.contains('eliminar-viaje')) {
    const id_viaje = e.target.dataset.id;
    if (!confirm('¿Eliminar este viaje?')) return;
    try {
      const res = await fetch(API_ELIMINAR_VIAJE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ id_viaje })
      });
      const data = await res.json();
      if (data.ok) {
        alert('Viaje eliminado');
        cargarViajesPublicados();
      } else alert(data.error || 'Error al eliminar viaje');
    } catch { alert('Error de conexión'); }
  }
});

/* =========================
   CALIFICACIONES — listeners
========================= */
// Abrir/cerrar mini-form de calificación
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('abrir-calificar')) {
    const box = e.target.closest('.calificar-box');
    const form = box.querySelector('.calificar-form');
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
  }
});

// Enviar calificación
document.addEventListener('submit', async (e) => {
  if (!e.target.classList.contains('calificar-form')) return;
  e.preventDefault();
  const form = e.target;
  const box = form.closest('.calificar-box');
  const idViaje = parseInt(box.dataset.viaje, 10);
  const punt = parseInt(form.querySelector('[name="p"]').value, 10);
  const com  = form.querySelector('[name="c"]').value.trim();
  const msgEl = box.querySelector('.calificar-msg');

  if (!idViaje || !(punt >=1 && punt <=5)) {
    msgEl.textContent = 'Datos inválidos';
    msgEl.className = 'calificar-msg alert error';
    return;
  }
  try {
    const r = await fetch(API_CALIFICAR, {
      method: 'POST',
      headers: { 'Content-Type':'application/json','Authorization':'Bearer '+ token },
      body: JSON.stringify({ ID_Viaje: idViaje, Puntuacion: punt, Comentario: com })
    });
    const data = await r.json();
    if (data.ok) {
      msgEl.textContent = data.msg || 'Calificación guardada';
      msgEl.className = 'calificar-msg alert success';
      form.style.display = 'none';
    } else {
      msgEl.textContent = data.error || 'No se pudo guardar';
      msgEl.className = 'calificar-msg alert error';
    }
  } catch {
    msgEl.textContent = 'Error de conexión';
    msgEl.className = 'calificar-msg alert error';
  }
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
