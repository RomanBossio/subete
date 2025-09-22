<?php $page=''; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Detalle de viaje</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.0">
  <link rel="stylesheet" href="/subete/frontend/css/detalle-viaje.css?v=1.0">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container detalle-container">
    <a href="/subete/frontend/buscar.php" class="btn volver">← Volver</a>
    <h1>Detalle de viaje</h1>

    <div id="view" class="viaje-card"></div>
    <div id="msg" class="mt-2"></div>
  </main>

<script>
const API_URL = '/subete/backend/api/viajes/detalle.php';
const API_RESERVA = '/subete/backend/api/viajes/reservas.php';
const params = new URLSearchParams(location.search);
const id = Number(params.get('id') || 0);
const view  = document.getElementById('view');
const msg   = document.getElementById('msg');
const token = localStorage.getItem('token'); // token guardado al hacer login

if (!id){
  view.textContent = 'Falta id';
  throw new Error('Falta id');
}

if (!token) {
  // Mostrar aviso, pero dejar igual la página (el botón quedará deshabilitado luego)
  msg.textContent = 'Debes iniciar sesión para reservar';
  msg.className = 'alert error mt-2';
}

// función util para mostrar mensajes
function showMsg(text, type='info') {
  msg.textContent = text;
  msg.className = type === 'success' ? 'alert success mt-2' : (type === 'error' ? 'alert error mt-2' : 'mt-2');
}

// Ejecutable principal
(async () => {
  try {
    const res = await fetch(`${API_URL}?id=${id}`);
    const data = await res.json();

    if (!data.ok) {
      view.textContent = data.error || 'Error al cargar el viaje';
      return;
    }

    const v = data.viaje;
    const encom = Number(v.Permite_Encomiendas) === 1 ? '✔ Acepta encomiendas' : 'No acepta encomiendas';
    const precio = (Number(v.Precio) || 0).toLocaleString('es-AR');
    let disponibles = Number(v.Lugares_Disponibles) || 0;

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
        ${v.Detalles ? `<p class="extra">${v.Detalles}</p>` : ''}
      </div>

      <div class="conductor-card">
        <h3>Conductor</h3>
        <p>${v.Conductor_Nombre ?? ''} ${v.Conductor_Apellido ?? ''}</p>
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

    // Referencias DOM dinámicas
    const btn = document.getElementById('btn-reservar');
    const inputCantidad = document.getElementById('cantidad');
    const lugaresSpan = document.getElementById('lugares-disponibles');

    // Si no hay asientos, indicarlo y deshabilitar
    if (disponibles <= 0) {
      showMsg('No quedan asientos disponibles', 'error');
      btn.disabled = true;
      inputCantidad.disabled = true;
    }

    // Función para actualizar UI cuando cambian los asientos
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
        // si el valor actual excede el max, reducirlo
        if (Number(inputCantidad.value) > disponibles) inputCantidad.value = disponibles;
      }
    }

    // Handler botón reservar
    btn.addEventListener('click', async () => {
      const tokenLocal = localStorage.getItem('token'); // recargar token por si cambió
      if (!tokenLocal) {
        showMsg('Debes iniciar sesión para reservar', 'error');
        return;
      }

      // Leer cantidad
      const cantidad = Math.max(1, Math.floor(Number(inputCantidad.value) || 1));
      if (cantidad < 1) {
        showMsg('La cantidad debe ser al menos 1', 'error');
        return;
      }
      if (cantidad > disponibles) {
        showMsg('No hay suficientes asientos disponibles', 'error');
        return;
      }

      try {
        // Petición al backend
        const resReserva = await fetch(API_RESERVA, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + tokenLocal
          },
          body: JSON.stringify({ id_viaje: id, cantidad: cantidad })
        });

        // leemos texto crudo para debug y luego intentar JSON
        const text = await resReserva.text();
        console.log('Respuesta cruda de reservas.php:', text);

        // Intentamos parsear JSON
        let dataReserva;
        try {
          dataReserva = JSON.parse(text);
        } catch (err) {
          // Si viene HTML o warning de PHP lo mostramos para que lo puedas ver
          showMsg('Respuesta inválida del servidor: ' + text, 'error');
          console.error('Error parseando JSON de reservas.php', err);
          return;
        }

        // Si el servidor devolvió ok
        if (dataReserva.ok) {
          showMsg(dataReserva.msg || 'Reserva realizada con éxito', 'success');

          // Si el backend devuelve el nuevo contador de lugares lo usamos,
          // si no, lo actualizamos localmente restando la cantidad
          const nuevos = Number(
            dataReserva.lugares_restantes ?? dataReserva.lugaresRestantes ?? dataReserva.restantes ?? dataReserva.lugares_restantes_php ?? NaN
          );

          if (!Number.isNaN(nuevos)) {
            actualizarDisponibles(nuevos);
          } else {
            // fallback: restar localmente
            actualizarDisponibles(Math.max(0, disponibles - cantidad));
          }

          // opcional: deshabilitar botón si ya no quedan asientos
          if (disponibles <= 0) {
            btn.disabled = true;
            inputCantidad.disabled = true;
          }

        } else {
          // error retornado por el backend (ej. token inválido, ya reservó, no hay plazas)
          const errMsg = dataReserva.error || 'Error al reservar';
          showMsg(errMsg, 'error');

          // Si backend indica token expirado o inválido, sugerimos re-login
          if (/token/i.test(errMsg) || /expir/i.test(errMsg) || resReserva.status === 401) {
            showMsg(errMsg + '. Volvé a iniciar sesión.', 'error');
          }
        }

      } catch (e) {
        console.error('Excepción al reservar:', e);
        showMsg('Error al reservar (problema de conexión)', 'error');
      }
    });

  } catch (e) {
    console.error(e);
    view.textContent = 'No se pudo cargar el detalle.';
  }
})();
</script>
</body>
</html>
