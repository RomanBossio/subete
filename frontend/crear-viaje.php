<?php $page='crear'; ?>
<?php
// 🔒 Esto SIEMPRE va primero
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require __DIR__ . '/../backend/config/db.php';
$pdo = db();

// 🔹 Obtener usuario desde token (solo cookie para carga inicial)
$token = $_COOKIE['token'] ?? null;
$usuario = null;
$carnetVerificado = 0;
$carnetVencimiento = null;
$id_usuario_actual = null; // Guardaremos el ID aquí

if ($token) {
    $stmt = $pdo->prepare("SELECT ID_Usuario FROM sesiones WHERE token = ? AND expira_en > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $sesion = $stmt->fetch();

    if ($sesion) {
        $id_usuario_actual = $sesion['ID_Usuario']; // Guardar ID
        $stmt = $pdo->prepare("SELECT carnet_validado, carnet_vencimiento FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id_usuario_actual]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $carnetVerificado = $usuario['carnet_validado'];
            $carnetVencimiento = $usuario['carnet_vencimiento'];
        }
    }
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Publicar viaje</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.3">
  <style>
    /* ===== Estilos para el formulario ===== */
    main.container { display: flex; justify-content: center; align-items: flex-start; padding: 40px 20px; min-height: 80vh; }
    .card { background: rgba(255, 255, 255, 0.95); border-radius: 16px; padding: 30px 25px; width: 100%; max-width: 600px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
    h2 { margin-top: 0; margin-bottom: 20px; color: #1e88e5; text-align: center; }
    form label { display: block; font-weight: 500; margin-bottom: 8px; color: #333; }
    /* Ajuste para que input y select tengan el mismo alto */
    input, textarea, select { width: 100%; height: 40px; /* Alto fijo */ padding: 0 10px; /* Ajuste padding */ border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; margin-bottom: 15px; box-sizing: border-box; /* Incluir padding y border en el tamaño */ }
    /* Altura específica para textarea */
    textarea { height: auto; min-height: 80px; resize: vertical; padding: 10px; }
    /* Corrección para datetime-local */
    input[type="datetime-local"] { padding: 8px 10px; }
    .grid.cols-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    @media(max-width: 600px){ .grid.cols-2 { grid-template-columns: 1fr; } }
    .checkbox-group { display: flex; align-items: center; margin-bottom: 15px; }
    .checkbox-group input { width: auto; height: auto; /* Resetear altura */ margin-right: 10px; }
    .btn.primary { width: 100%; background-color: #1e88e5; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: background 0.3s; font-size: 1rem; }
    .btn.primary:hover { background-color: #1565c0; }
    #msg, #msg-carnet { margin-top: 10px; text-align: center; font-weight: 600; }
    #msg-carnet.error, #msg.error { color: #dc3545; }
    #msg-carnet.success, #msg.success { color: #28a745; }
    /* ===== Fondo con imagen difuminada ===== */
    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: url('/subete/frontend/img/mapa.png') no-repeat center center fixed; background-size: cover; min-height: 100vh; overflow-x: hidden; position: relative; }
    body::before { content: ""; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(6px); z-index: -1; }
    main.container { position: relative; z-index: 1; }
    /* Estilo para sugerencia de precio */
    #sugerencia-precio { font-size: 0.9em; color: #007bff; margin-top: -10px; margin-bottom: 15px; min-height: 1.2em; /* Espacio reservado */ }
  </style>
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">
    <div class="card" id="card-carnet" style="<?= $carnetVerificado ? 'display:none;' : '' ?>">
      <h2>Verifica tu carnet de conducir</h2>
      <p>Para poder publicar viajes, subí tu carnet de conducir vigente.</p>
      <input type="file" id="dniFoto" accept="image/*">
      <button class="btn primary" id="btnSubirCarnet">Subir y verificar</button>
      <div id="msg-carnet"></div>
    </div>

    <div class="card" id="card-viaje" style="<?= $carnetVerificado ? '' : 'display:none;' ?>">
      <h2>Publicar viaje</h2>
      <form id="form-viaje">
        <div class="grid cols-2">
          <label>Origen
            <input name="origen" id="origen" required>
          </label>
          <label>Destino
            <input name="destino" id="destino" required>
          </label>
        </div>

        <div class="grid cols-2">
          <label>Fecha y hora de salida
            <input type="datetime-local" name="fecha_hora_salida" required>
          </label>
          <label>Precio por asiento
            <input type="number" step="50" name="precio" placeholder="Ej: 2000" required>
             <div id="sugerencia-precio"></div>
          </label>
        </div>

       <div class="grid cols-2">
         <label>Tipo de vehículo
           <select id="tipoVehiculo" name="tipoVehiculo" required>
             <option value="">Seleccioná un tipo</option>
             <option value="Auto">Auto</option>
             <option value="Camioneta">Camioneta</option>
             <option value="Camion">Camión</option>
             <option value="Trafic">Tráfic</option>
             <option value="Colectivo">Colectivo</option>
             <option value="Moto">Moto</option>
           </select>
         </label>
         <label>Asientos disponibles
           <input type="number" name="lugares" id="lugares" min="1" required>
         </label>
       </div>

       <div class="checkbox-group">
         <input type="checkbox" id="encomiendas">
         <label for="encomiendas" style="margin-bottom: 0;">Permite encomiendas</label> </div>

        <label>Descripción (Opcional)
          <textarea name="detalles" rows="3" placeholder="Ej: Aire acondicionado, no mascotas..."></textarea>
        </label>

        <button class="btn primary" type="submit">Publicar</button>
      </form>
      <div id="msg"></div>
    </div>
  </main>

<script>
// ---- Carnet JS ----
const btnCarnet = document.getElementById('btnSubirCarnet');
const dniFoto = document.getElementById('dniFoto');
const msgCarnet = document.getElementById('msg-carnet');
btnCarnet.addEventListener('click', async () => {
  if (!dniFoto.files.length) return alert('Seleccioná una imagen');
  const file = dniFoto.files[0];
  const formData = new FormData();
  formData.append('foto', file);

  const token = localStorage.getItem("token"); // Usar localStorage es más estándar
  if (!token) {
      msgCarnet.textContent = 'Error: No se encontró token de sesión.';
      msgCarnet.className = 'error';
      return;
  }
  msgCarnet.textContent = 'Analizando carnet... ⏳';
  msgCarnet.className = '';

  try {
    const res = await fetch('../backend/api/usuarios/subir-carnet.php', {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token },
      body: formData
    });
    const out = await res.json();
    if (out.validado) {
      msgCarnet.textContent = 'Carnet verificado ✅. Válido hasta: ' + out.vencimiento;
      msgCarnet.className = 'success';
      setTimeout(() => window.location.reload(), 1500);
    } else {
      msgCarnet.textContent = out.error || 'Error al verificar carnet';
      msgCarnet.className = 'error';
    }
  } catch(e) {
    console.error(e);
    msgCarnet.textContent = 'Error de conexión al servidor';
    msgCarnet.className = 'error';
  }
});

// ---- Formulario de viaje ----
const f = document.getElementById('form-viaje');
const msg = document.getElementById('msg');

f.addEventListener('submit', async (e) => {
  e.preventDefault();

  if (!origenSetup || !destinoSetup || !origenSetup.getValido()) {
    alert("Por favor seleccioná una ciudad válida en Origen");
    document.getElementById('origen').focus();
    return;
  }
  if (!destinoSetup.getValido()) {
    alert("Por favor seleccioná una ciudad válida en Destino");
    document.getElementById('destino').focus();
    return;
  }

  const formData = new FormData(f);
  const dtInput = formData.get('fecha_hora_salida');
  if (dtInput) {
    // Asegurarse de que tenga segundos
    const fechaCompleta = dtInput.includes(':') ? dtInput + ':00' : dtInput + 'T00:00:00';
    formData.set('fecha_hora_salida', fechaCompleta.replace('T', ' '));
  }
  const data = Object.fromEntries(formData.entries());
  data.permite_encomiendas = document.getElementById('encomiendas').checked ? 1 : 0;

  const token = localStorage.getItem("token");
  if (!token) { msg.textContent = "Debes iniciar sesión para publicar un viaje"; msg.className = 'error'; return; }

  msg.textContent = 'Publicando viaje...';
  msg.className = '';

  try {
    const res = await fetch('../backend/api/viajes/crear-viajes.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify(data)
    });
    const out = await res.json().catch(() => ({}));
    if (res.ok && out.ok) { // Verificar out.ok también
      msg.textContent = 'Viaje publicado con éxito';
      msg.className = 'success';
      f.reset();
      document.getElementById('sugerencia-precio').innerHTML = ''; // Limpiar sugerencia
      document.getElementById('lugares').placeholder = ''; // Limpiar placeholder de asientos
    } else {
      msg.textContent = out.error || 'Error al publicar';
      msg.className = 'error';
    }
  } catch (err) {
    console.error(err);
    msg.textContent = 'Error de conexión al servidor';
    msg.className = 'error';
  }
});

// ---- NUEVO: LÓGICA PARA SUGERIR PRECIO ----
const sugerenciaDiv = document.getElementById('sugerencia-precio');
let directionsService; // Se inicializará en initAutocomplete

async function calcularPrecioSugerido() {
    if (!directionsService || !origenSetup || !destinoSetup) return;

    const origenInput = document.getElementById('origen');
    const destinoInput = document.getElementById('destino');
    const origen = origenInput.value;
    const destino = destinoInput.value;

    if (origen && destino && origenSetup.getValido() && destinoSetup.getValido()) {
        sugerenciaDiv.textContent = 'Calculando precio sugerido...';
        directionsService.route({
            origin: origen,
            destination: destino,
            travelMode: google.maps.TravelMode.DRIVING
        }, (result, status) => {
            if (status === 'OK') {
                const distanciaEnKm = result.routes[0].legs[0].distance.value / 1000;
                const costoPorKm = 180; // Puedes ajustar este valor
                const costoBase = distanciaEnKm * costoPorKm;
                const precioMin = Math.max(500, Math.round((costoBase * 0.8) / 500) * 500);
                const precioMax = Math.round((costoBase * 1.2) / 500) * 500;
                if (precioMin > 0 && precioMax > 0 && precioMax >= precioMin) {
                   sugerenciaDiv.innerHTML = `💡 **Sugerencia:** Entre <strong>$${precioMin.toLocaleString('es-AR')}</strong> y <strong>$${precioMax.toLocaleString('es-AR')}</strong>`;
                } else {
                   sugerenciaDiv.textContent = ''; // Limpiar si el cálculo da inválido
                }
            } else {
                sugerenciaDiv.textContent = ''; // Limpiar si no se pudo calcular
                console.warn('Error Directions API:', status);
            }
        });
    } else {
        sugerenciaDiv.textContent = ''; // Limpiar si los inputs no son válidos
    }
}

// ---- Google Places Autocomplete (Modificado) ----
function setupAutocomplete(input, callback) {
  if (!google || !google.maps || !google.maps.places) {
      console.error("Google Places API no está lista.");
      return { getValido: () => false };
  }
  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ['(cities)'],
    componentRestrictions: { country: 'ar' }
  });
  let valido = false;
  autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace();
    valido = !!place.geometry;
    if (!valido) input.value = '';
    callback();
  });
  input.addEventListener('blur', () => {
    setTimeout(() => {
        // Re-verificar validez después de un pequeño delay
        const place = autocomplete.getPlace();
        // Si hay 'place' y tiene geometría, es válido
        valido = !!(place && place.geometry);

        // Si el input tiene texto PERO no es válido (no se seleccionó de la lista)
        if (input.value && !valido) {
           // console.log("Limpiando input inválido en blur:", input.id);
           // input.value = ''; // Opcional: limpiar si no seleccionó de la lista
        } else if (!input.value) {
            valido = false; // Asegurar que quede inválido si está vacío
        }
        callback();
    }, 200); // Delay para permitir que 'place_changed' se procese
  });
  return { getValido: () => valido };
}

let origenSetup, destinoSetup;

function initAutocomplete() {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.error("Error: Google Maps API no se cargó correctamente.");
        return;
    }
    directionsService = new google.maps.DirectionsService();
    origenSetup = setupAutocomplete(document.getElementById('origen'), calcularPrecioSugerido);
    destinoSetup = setupAutocomplete(document.getElementById('destino'), calcularPrecioSugerido);
}

// ---- Verificar estado del carnet al cargar la página (CORREGIDO) ----
(async () => {
  const token = localStorage.getItem("token"); // Consistentemente usar localStorage
  const cardCarnet = document.getElementById('card-carnet');
  const cardViaje  = document.getElementById('card-viaje');
  const msgCarnet = document.getElementById('msg-carnet'); // Referencia al div de mensajes

  // Si no hay token, asegurar que se muestre el mensaje correcto o redirigir
  if (!token) {
      console.log("No hay token al cargar la página de crear viaje.");
      if (cardCarnet) {
          cardCarnet.innerHTML = '<h2>Error</h2><p>Debes iniciar sesión para publicar un viaje.</p>';
          cardCarnet.style.display = 'block';
      }
      if (cardViaje) cardViaje.style.display = 'none';
      return; // Detener ejecución si no hay token
  }

  // Si hay token, intentar verificar el carnet con la API
  try {
    const res = await fetch('../backend/api/usuarios/check-carnet.php', {
      headers: { 'Authorization': 'Bearer ' + token }
    });

    // Verificar si la respuesta fue exitosa (status 200-299)
    if (!res.ok) {
        // Si hay error de servidor (500) o no autorizado (401), mostrar error genérico
        console.error("Error al llamar a check-carnet.php:", res.status, res.statusText);
        msgCarnet.textContent = 'No se pudo verificar el estado del carnet. Intenta recargar.';
        msgCarnet.className = 'error';
        // Ocultar ambos cards como medida de seguridad o dejar visible el de carnet
        cardCarnet.style.display = 'block';
        cardViaje.style.display = 'none';
        return;
    }

    const data = await res.json();

    // Ahora procesar la respuesta JSON
    if (data.validado) {
      cardCarnet.style.display = 'none';
      cardViaje.style.display = 'block';
      // No mostrar mensaje si ya está validado, es redundante
      // msgCarnet.textContent = 'Ya tienes un carnet válido hasta ' + data.vencimiento;
      // msgCarnet.className = 'success';
    } else {
      cardCarnet.style.display = 'block';
      cardViaje.style.display = 'none';
      if (data.vencimiento) {
        msgCarnet.textContent = 'Tu carnet venció el ' + data.vencimiento + '. Por favor subí uno nuevo.';
        msgCarnet.className = 'error';
      } else if (data.existe === false) { // Usar === false para ser explícito
         msgCarnet.textContent = 'Necesitas subir tu carnet para publicar viajes.';
         msgCarnet.className = '';
      } else {
         // Si existe pero no está validado (pendiente, rechazado, etc.)
         msgCarnet.textContent = 'Tu carnet no pudo ser validado o está pendiente de revisión.';
         msgCarnet.className = 'error';
      }
    }
  } catch(e) {
    console.error("Error de red o JS al verificar carnet:", e);
    msgCarnet.textContent = 'Error de conexión al verificar el carnet.';
    msgCarnet.className = 'error';
    cardCarnet.style.display = 'block'; // Mostrar card de carnet si falla la verificación
    cardViaje.style.display = 'none';
  }
})(); // Fin del bloque (async () => { ... })();

// ---- Limitar asientos según tipo de vehículo ----
const tipoVehiculo = document.getElementById('tipoVehiculo');
const inputAsientos = document.getElementById('lugares');

tipoVehiculo.addEventListener('change', () => {
  let maxAsientos = 4; // Default para Auto

  switch (tipoVehiculo.value) {
    case 'Auto': maxAsientos = 4; break;
    case 'Camioneta': maxAsientos = 6; break;
    case 'Camion': maxAsientos = 2; break;
    case 'Trafic': maxAsientos = 15; break;
    case 'Colectivo': maxAsientos = 60; break;
    case 'Moto': maxAsientos = 1; break;
    default: maxAsientos = 4; // Si no selecciona, default
  }

  inputAsientos.max = maxAsientos;
  if (inputAsientos.value && parseInt(inputAsientos.value) > maxAsientos) {
    inputAsientos.value = maxAsientos;
  }
  inputAsientos.placeholder = `Máximo ${maxAsientos}`;
});

</script>

<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBnDDeYhxpb6H8zyDJA38h7k_Xs-HT5OB4&libraries=places&callback=initAutocomplete">
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>