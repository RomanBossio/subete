<?php $page=''; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Mis reservas</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <link rel="stylesheet" href="/subete/frontend/css/mis-reservas.css?v=1.0">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container mis-reservas-container">
    <h1>Mis reservas</h1>
    <div id="msg" class="mt-2"></div>
    <div id="reservas-list" class="reservas-list"></div>
  </main>

<script>
const API_MIS_RESERVAS = '/subete/backend/api/viajes/mis-reservas.php';
const API_CANCELAR = '/subete/backend/api/viajes/cancelar-reserva.php';
const reservasList = document.getElementById('reservas-list');
const msg = document.getElementById('msg');
const token = localStorage.getItem('token');

if (!token) {
  msg.textContent = 'Debes iniciar sesión para ver tus reservas';
  msg.className = 'alert error mt-2';
} else {

  async function cargarReservas() {
    try {
      const res = await fetch(API_MIS_RESERVAS, {
        headers: {
          'Authorization': 'Bearer ' + token
        }
      });
      const data = await res.json();

      if (!data.ok) {
        msg.textContent = data.error || 'Error al cargar reservas';
        msg.className = 'alert error mt-2';
        return;
      }

      if (!data.reservas || data.reservas.length === 0) {
        reservasList.innerHTML = '<p>No tienes reservas.</p>';
        return;
      }

      reservasList.innerHTML = data.reservas.map(r => `
        <div class="reserva-card" data-id="${r.id_reserva}">
          <h3>${r.Origen} → ${r.Destino}</h3>
          <p>Fecha salida: ${r.Fecha_Hora_Salida}</p>
          <p>Asientos reservados: ${r.cantidad}</p>
          <p>Estado: ${r.estado}</p>
          <button class="btn cancelar">Cancelar</button>
        </div>
      `).join('');

      // Asignar evento a botones
      document.querySelectorAll('.btn.cancelar').forEach(btn => {
        btn.addEventListener('click', async (e) => {
          const card = e.target.closest('.reserva-card');
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
              card.remove(); // elimina del listado inmediatamente
              // Si querés actualizar los viajes publicados para reflejar asientos:
              // recargarViajesPublicados();
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

  cargarReservas();

  // Función opcional si querés actualizar viajes publicados
  // async function recargarViajesPublicados() {
  //   // fetch y actualizar DOM de viajes disponibles
  // }
}
</script>
</body>
</html>
