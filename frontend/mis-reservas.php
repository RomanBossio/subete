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
    <div id="viajes-publicados" class="results mt-2"></div>
  </main>

<script>
const API_MIS_RESERVAS = '/subete/backend/api/viajes/mis-reservas.php';
const API_CANCELAR = '/subete/backend/api/viajes/cancelar-reserva.php';
const API_MIS_VIAJES = '/subete/backend/api/viajes/mis-viajes-publicados.php';
const API_PASAJEROS = '/subete/backend/api/viajes/pasajeros-por-viaje.php';
const API_ELIMINAR_PASAJERO = '/subete/backend/api/viajes/eliminar-pasajero.php';

const reservasList = document.getElementById('reservas-list');
const viajesPublicadosList = document.getElementById('viajes-publicados');
const msg = document.getElementById('msg');
const token = localStorage.getItem('token');

if (!token) {
  msg.textContent = 'Debes iniciar sesión para ver tus reservas';
  msg.className = 'alert error mt-2';
} else {
  async function cargarReservas() {
    try {
      const res = await fetch(API_MIS_RESERVAS, {
        headers: { 'Authorization': 'Bearer ' + token }
      });
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

      reservasList.innerHTML = data.reservas.map(r => `
        <div class="card" data-id="${r.id_reserva}">
          <h3>${r.Origen} → ${r.Destino}</h3>
          <p class="muted">Fecha salida: ${r.Fecha_Hora_Salida}</p>
          <p>Asientos reservados: ${r.cantidad}</p>
          <p>Estado: <strong>${r.estado}</strong></p>
          <button class="btn primary cancelar">Cancelar</button>
        </div>
      `).join('');

      document.querySelectorAll('.btn.cancelar').forEach(btn => {
        btn.addEventListener('click', async (e) => {
          const card = e.target.closest('.card');
          const id_reserva = card.dataset.id;

          if (!confirm('¿Estás seguro de cancelar esta reserva?')) return;

          try {
            const resCancel = await fetch(API_CANCELAR, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
              },
              body: JSON.stringify({ id_reserva })
            });

            const dataCancel = await resCancel.json();

            if (dataCancel.ok) {
              msg.textContent = dataCancel.msg || 'Reserva cancelada';
              msg.className = 'alert success mt-2';
              card.remove();
            } else {
              msg.textContent = dataCancel.error || 'Error al cancelar';
              msg.className = 'alert error mt-2';
            }
          } catch(err) {
            console.error('Error al cancelar reserva:', err);
            msg.textContent = 'Error al cancelar reserva';
            msg.className = 'alert error mt-2';
          }
        });
      });

    } catch(err) {
      console.error('Error al cargar reservas:', err);
      msg.textContent = 'Error al cargar reservas';
      msg.className = 'alert error mt-2';
    }
  }

  async function cargarViajesPublicados() {
    try {
      const res = await fetch(API_MIS_VIAJES, {
        headers: { 'Authorization': 'Bearer ' + token }
      });
      const data = await res.json();

      if (!data.ok) {
        viajesPublicadosList.innerHTML = '<p class="muted">Error al cargar tus viajes publicados.</p>';
        return;
      }

      if (!data.viajes || data.viajes.length === 0) {
        viajesPublicadosList.innerHTML = '<p class="muted">No publicaste viajes aún.</p>';
        return;
      }

      viajesPublicadosList.innerHTML = '';

      for (const viaje of data.viajes) {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
          <h3>${viaje.Origen} → ${viaje.Destino}</h3>
          <p class="muted">Fecha salida: ${viaje.Fecha_Hora_Salida}</p>
          <p>Lugares disponibles: <strong>${viaje.Lugares_Disponibles}</strong></p>
          <p><strong>Pasajeros:</strong> <span class="pasajeros-loading">Cargando...</span></p>
        `;
        viajesPublicadosList.appendChild(card);

        try {
          const pasajerosRes = await fetch(API_PASAJEROS, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ id_viaje: viaje.ID_Viaje })
          });

          const pasajerosData = await pasajerosRes.json();
          const contenedor = card.querySelector('.pasajeros-loading');

          if (!pasajerosData.ok) {
            contenedor.textContent = 'Error al cargar pasajeros.';
            console.error('Backend error:', pasajerosData.error);
          } else if (!Array.isArray(pasajerosData.pasajeros) || pasajerosData.pasajeros.length === 0) {
            contenedor.textContent = 'Sin pasajeros.';
          } else {
            contenedor.innerHTML = '<ul>' + pasajerosData.pasajeros.map(p =>
              `<li>
                <strong>${p.Nombre} ${p.Apellido}</strong><br>
                <span class="muted">Tel:</span> <a href="tel:${p.Telefono}">${p.Telefono}</a><br>
                <span class="muted">Asientos:</span> ${p.cantidad}<br>
                <button class="btn eliminar-pasajero" data-id="${p.id_reserva}">Eliminar</button>
              </li>`
            ).join('') + '</ul>';
          }

        } catch (err) {
          const contenedor = card.querySelector('.pasajeros-loading');
          contenedor.textContent = 'Error al cargar pasajeros.';
          console.error('Error al cargar pasajeros:', err);
        }
      }

    } catch (err) {
      console.error('Error al cargar viajes publicados:', err);
      viajesPublicadosList.innerHTML = '<p class="muted">Error al cargar tus viajes publicados.</p>';
    }
  }

  viajesPublicadosList.addEventListener('click', async (e) => {
    if (e.target.classList.contains('eliminar-pasajero')) {
      const id_reserva = e.target.dataset.id;

      if (!confirm('¿Eliminar este pasajero?')) return;

      try {
        const res = await fetch(API_ELIMINAR_PASAJERO, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
          },
          body: JSON.stringify({ id_reserva })
        });

        const data = await res.json();
        if (data.ok) {
          e.target.closest('li').remove();
          alert('Pasajero eliminado');
        } else {
          alert(data.error || 'Error al eliminar pasajero');
        }
      } catch (err) {
        console.error('Error al eliminar pasajero:', err);
        alert('Error de conexión');
      }
    }
  });

  cargarReservas();
  cargarViajesPublicados();
}
</script>
</body>
</html>
