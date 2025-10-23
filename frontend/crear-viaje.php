<?php $page='crear'; ?>
<?php
// 🔒 Esto SIEMPRE va primero
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require __DIR__ . '/../backend/config/db.php';
$pdo = db();

// 🔹 Obtener usuario desde token (adaptar a tu sistema de login)
$token = $_COOKIE['token'] ?? null;
$usuario = null;
$carnetVerificado = 0;
$carnetVencimiento = null;

if ($token) {
    // Buscar ID_Usuario desde la tabla sesiones
    $stmt = $pdo->prepare("SELECT ID_Usuario FROM sesiones WHERE token = ? AND expira_en > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $sesion = $stmt->fetch();

    if ($sesion) {
        $stmt = $pdo->prepare("SELECT carnet_validado, carnet_vencimiento FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$sesion['ID_Usuario']]);
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
    main.container {
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 40px 20px;
      min-height: 80vh;
    }

    .card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 30px 25px;
      width: 100%;
      max-width: 600px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    h2 {
      margin-top: 0;
      margin-bottom: 20px;
      color: #1e88e5;
      text-align: center;
    }

    form label {
      display: block;
      font-weight: 500;
      margin-bottom: 8px;
      color: #333;
    }

    input, textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      margin-bottom: 15px;
    }

    textarea {
      resize: vertical;
    }

    .grid.cols-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    @media(max-width: 600px){
      .grid.cols-2 {
        grid-template-columns: 1fr;
      }
    }

    .checkbox-group {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .checkbox-group input {
      width: auto;
      margin-right: 10px;
    }

    .btn.primary {
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
    }

    .btn.primary:hover {
      background-color: #1565c0;
    }

    #msg, #msg-carnet {
      margin-top: 10px;
      text-align: center;
      font-weight: 600;
      color: #1e88e5;
    }
    /* ===== Fondo con imagen difuminada ===== */
body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background: url('/subete/frontend/img/mapa.png') no-repeat center center fixed;
  background-size: cover;
  height: 100vh;
  overflow-x: hidden;
  position: relative;
}
body::before {
  content: "";
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(6px);
  z-index: -1;
}
main.container {
  position: relative;
  z-index: 1;
}
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
          <label>Precio
            <input type="number" step="0.01" name="precio" required>
          </label>
        </div>

        <div class="grid cols-2">
          <label>Asientos
            <input type="number" name="lugares" min="1" max="6" required>
          </label>
          <div class="checkbox-group">
            <input type="checkbox" id="encomiendas">
            <label for="encomiendas">Permite encomiendas</label>
          </div>
        </div>

        <label>Descripción
          <textarea name="detalles" rows="3" placeholder="Opcional"></textarea>
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

  const token = localStorage.getItem("token");
  msgCarnet.textContent = 'Analizando carnet... ⏳';

  try {
    const res = await fetch('../backend/api/usuarios/subir-carnet.php', {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token },
      body: formData
    });
    const out = await res.json();
    if (out.validado) {
      msgCarnet.textContent = 'Carnet verificado ✅';
      document.getElementById('card-carnet').style.display = 'none';
      document.getElementById('card-viaje').style.display = 'block';
    } else {
      msgCarnet.textContent = out.error || 'Error al verificar carnet';
    }
  } catch(e) {
    console.error(e);
    msgCarnet.textContent = 'Error de conexión al servidor';
  }
});

// ---- Formulario de viaje ----
const f = document.getElementById('form-viaje');
const msg = document.getElementById('msg');

f.addEventListener('submit', async (e) => {
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

  const formData = new FormData(f);
  const dtInput = formData.get('fecha_hora_salida');
  if (dtInput) formData.set('fecha_hora_salida', dtInput.replace('T', ' ') + ':00');
  const data = Object.fromEntries(formData.entries());
  data.permite_encomiendas = document.getElementById('encomiendas').checked ? 1 : 0;

  const token = localStorage.getItem("token");
  if (!token) { msg.textContent = "Debes iniciar sesión para publicar un viaje"; return; }

  try {
    const res = await fetch('../backend/api/viajes/crear-viajes.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify(data)
    });
    const out = await res.json().catch(() => ({}));
    if (res.ok && !out.error) {
      msg.textContent = 'Viaje publicado con éxito';
      f.reset();
    } else {
      msg.textContent = out.error || 'Error al publicar';
    }
  } catch (err) {
    console.error(err);
    msg.textContent = 'Error de conexión al servidor';
  }
});

// ---- Google Places Autocomplete ----
function setupAutocomplete(input) {
  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ['(cities)'],
    componentRestrictions: { country: 'ar' }
  });

  let valido = false;
  autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace();
    if (!place.geometry) { valido = false; input.value=''; } else { valido = true; }
  });
  input.addEventListener('blur', () => { if (!valido) input.value=''; });

  return { autocomplete, getValido: () => valido };
}

let origenSetup, destinoSetup;
function initAutocomplete() {
  origenSetup = setupAutocomplete(document.getElementById('origen'));
  destinoSetup = setupAutocomplete(document.getElementById('destino'));
}
// ---- Verificar estado del carnet al cargar la página ----
(async () => {
  const token = localStorage.getItem("token");
  if (!token) return;

  try {
    const res = await fetch('../backend/api/usuarios/check-carnet.php', {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    const data = await res.json();

    const cardCarnet = document.getElementById('card-carnet');
    const cardViaje  = document.getElementById('card-viaje');

    if (data.validado) {
      cardCarnet.style.display = 'none';
      cardViaje.style.display = 'block';
      msgCarnet.textContent = 'Ya tienes un carnet válido hasta ' + data.vencimiento;
    } else {
      cardCarnet.style.display = 'block';
      cardViaje.style.display = 'none';
      if (data.vencimiento) {
        msgCarnet.textContent = 'Tu carnet venció el ' + data.vencimiento + '. Por favor subí uno nuevo.';
      }
    }
  } catch(e) {
    console.error(e);
  }
})();


</script>

<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBnDDeYhxpb6H8zyDJA38h7k_Xs-HT5OB4&libraries=places&callback=initAutocomplete">
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
