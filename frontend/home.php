<?php $page='home'; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Súbete · Home</title>
  <link rel="stylesheet" href="/subete/frontend/css/app.css?v=1.3">
</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="container">

    <!-- HERO con datos reales -->
    <section class="hero">
      <div>
        <h1>Compartí viajes. Ahorrá. Conectá.</h1>
        <p class="lead">
          Súbete te conecta con conductores y pasajeros para compartir rutas y costos. Todo simple, claro y comunitario.
        </p>
        <div class="actions">
          <a class="btn primary" href="/subete/frontend/buscar.php"> Buscar viaje</a>
          <a class="btn" href="/subete/frontend/crear-viaje.php"> Publicar viaje</a>
        </div>
        <div class="stats">
          <div class="stat"><small>Usuarios</small> <span id="usersTotal">—</span></div>
          <div class="stat"><small>Viajes publicados</small> <span id="viajesPublicados">—</span></div>
          <div class="stat"><small>Nuevos esta semana</small> <span id="nuevosSemana">—</span></div>
          <div class="stat"><small>Viajes confirmados</small> <span id="viajesConfirmados">—</span></div>
        </div>
      </div>
      <img src="/subete/frontend/img/hero-carpool.jpg" alt="Compartir viajes">
    </section>

    <!-- Últimos viajes reales -->
    <section class="section">
      <h2 class="section-title">Últimos viajes publicados</h2>
      <div id="lastTrips" class="results">
        <div class="card">Cargando...</div>
      </div>
    </section>

  </main>

  <!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-grid">
    <!-- Columna 1 -->
    <div class="footer-col">
      <h2 class="brand">Súbete</h2>
      <p class="tagline">Tu compañero de viaje para compartir rutas y ahorrar juntos.</p>
    </div>

    <!-- Columna 2 -->
    <div class="footer-col">
      <h3>Enlaces</h3>
      <ul class="footer-links">
        <li><a href="home.php">Inicio</a></li>
        <li><a href="publicar.php">Publicar viaje</a></li>
        <li><a href="viajes.php">Viajes</a></li>
      </ul>
    </div>

    <!-- Columna 3 -->
    <div class="footer-col">
      <h3>Soporte</h3>
      <ul class="footer-links">
        <li><a href="#">Ayuda</a></li>
        <li><a href="#">Contacto</a></li>
        <li><a href="#">Términos</a></li>
      </ul>
    </div>
  </div>

  <!-- Copyright -->
  <div class="footer-bottom">
    © <?= date('Y') ?> Súbete. Todos los derechos reservados.
  </div>
</footer>


  <script>
  // Endpoints reales (ajustá si tus rutas difieren)
  const ENDPOINTS = {
    users: '/subete/backend/api/panel/usuarios_totales.php',
    viajesPublicados: '/subete/backend/api/panel/viajes_publicados.php',
    nuevosSemana: '/subete/backend/api/panel/nuevos_usuarios_semana.php',
    viajesConfirmados: '/subete/backend/api/panel/viajes_confirmados.php',
    buscar: '/subete/backend/api/viajes/buscar-viajes.php',
  };

  async function fetchJSON(url){
    const res = await fetch(url);
    if(!res.ok) throw new Error('HTTP '+res.status);
    return res.json();
  }

  async function loadStats(){
    try{
      const [u, vpub, nsem, vconf] = await Promise.all([
        fetchJSON(ENDPOINTS.users),
        fetchJSON(ENDPOINTS.viajesPublicados),
        fetchJSON(ENDPOINTS.nuevosSemana),
        fetchJSON(ENDPOINTS.viajesConfirmados)
      ]);
      document.getElementById('usersTotal').textContent         = (u.total ?? u.count ?? '0');
      document.getElementById('viajesPublicados').textContent   = (vpub.total ?? vpub.count ?? '0');
      document.getElementById('nuevosSemana').textContent       = (nsem.total ?? nsem.count ?? '0');
      document.getElementById('viajesConfirmados').textContent  = (vconf.total ?? vconf.count ?? '0');
    }catch(e){
      console.error('Stats error:', e);
    }
  }

  function tripCard(v){
    const encom = Number(v.Permite_Encomiendas) === 1 ? ' · ✔ Encomiendas' : '';
    const cond  = v.Conductor_Nombre ? ` · ${v.Conductor_Nombre} ${v.Conductor_Apellido}` : '';
    const precio = (Number(v.Precio)||0).toLocaleString('es-AR');
    return `
  <a href="/subete/frontend/detalle-viaje.html?id=${v.ID_Viaje}" class="card-link">
    <article class="card">
      <h3>${v.Origen} → ${v.Destino}</h3>
      <p class="meta">Sale: ${v.Fecha_Hora_Salida}${cond}${encom}</p>
      <p class="meta">Asientos: ${v.Lugares_Disponibles} · Precio: $${precio}</p>
      ${v.Detalles ? `<p class="meta">Detalles: ${v.Detalles}</p>` : ''}
    </article>
  </a>
`;

  }

  async function loadLastTrips(){
    const wrap = document.getElementById('lastTrips');
    try{
      const data = await fetchJSON(`${ENDPOINTS.buscar}?limit=4&offset=0`);
      const items = Array.isArray(data) ? data : (data.results || data.data || []);
      if(!items.length){
        wrap.innerHTML = '<div class="card">No hay viajes publicados todavía.</div>';
        return;
      }
      wrap.innerHTML = items.map(tripCard).join('');
    }catch(e){
      console.error('Trips error:', e);
      wrap.innerHTML = '<div class="card">No se pudo cargar la lista de viajes.</div>';
    }
  }

  loadStats();
  loadLastTrips();
  </script>
</body>
</html>
