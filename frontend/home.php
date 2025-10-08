<?php $page='home'; ?>
<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Súbete · Home</title>
  <style>
    /* ---------------- RESET ---------------- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: Arial, sans-serif;
      line-height: 1.5;
      color: #222;
    }
    a {
      text-decoration: none;
      color: inherit;
    }

    /* ---------------- HEADER ORIGINAL ---------------- */
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background-color: #fff; /* color original */
      width: 100%;
    }
    header nav {
      display: flex;
      gap: 1.5rem;
    }

    /* ---------------- HERO FULL WIDTH ---------------- */
    .hero {
      position: relative;
      width: 100vw;
      height: 400px; /* ajustable */
      overflow: hidden;
    }
    .hero img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .hero-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 3rem;
      font-weight: bold;
      color: #fff;
      text-align: center;
      text-shadow: 0 2px 10px rgba(0,0,0,0.5);
      padding: 0 1rem;
    }

    /* ---------------- BOTONES ---------------- */
    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
      text-align: center;
    }
    .actions {
      margin-bottom: 4rem;
    }
    .btn {
      display: inline-block;
      padding: 0.7rem 1.5rem;
      margin: 0.5rem;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn.primary {
      background: #007bff;
      color: #fff;
      border: none;
    }
    .btn.primary:hover {
      background: #0056b3;
    }

    /* ---------------- SECCIÓN 3 TARJETAS ---------------- */
    .cards-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      margin-bottom: 4rem;
    }
    .info-card {
      background: #fff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .info-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .info-card h3 {
      margin-bottom: 1rem;
      font-size: 1.4rem;
    }
    .info-card p {
      font-size: 1rem;
      color: #555;
      line-height: 1.5;
    }

    /* ---------------- ÚLTIMOS VIAJES ---------------- */
    .results {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 4rem;
    }
    .card {
      padding: 1rem;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <?php require __DIR__ . '/partials/header.php'; ?>

  <!-- HERO -->
  <section class="hero">
    <img src="/subete/frontend/img/header.png" alt="Compartir viajes">
    <div class="hero-text">Compartí Viajes. Conectá. Ahorrá.</div>
  </section>

  <main class="container">

    <!-- BOTONES -->
    <div class="actions">
      <a class="btn primary" href="/subete/frontend/buscar.php">Buscar viaje</a>
      <a class="btn" href="/subete/frontend/crear-viaje.php">Publicar viaje</a>
    </div>

    <!-- SECCIÓN 3 TARJETAS -->
    <section class="cards-section">
      <div class="info-card">
        <h3>Encuentra los mejores viajes</h3>
        <p>Nuestra comunidad de usuarios está en todas partes. Vayas donde vayas, encuentra el viaje perfecto con salida y llegada en los puntos más cercanos.</p>
      </div>
      <div class="info-card">
        <h3>Tu viaje, a tu manera</h3>
        <p>Con Súbete tienes control total sobre tus reservas y preferencias. Elige horarios, destinos y compañeros de viaje que se adapten a ti.</p>
      </div>
      <div class="info-card">
        <h3>¡Busca, elige y a viajar!</h3>
        <p>¡Reservar un viaje es más fácil que nunca! Gracias a nuestra sencilla aplicación y a su potente tecnología, podrás reservar un viaje cerca de ti en minutos.</p>
      </div>
    </section>

   <!-- ÚLTIMOS VIAJES -->
<section class="section">
  <h2 class="section-title">Últimos viajes publicados</h2>
  <div id="lastTrips" class="results">
    <div class="card">Cargando...</div>
  </div>
</section>

<style>
  /* Título celeste */
  .section-title {
    font-size: 2rem;
    color: #007bff; /* mismo celeste */
    margin-bottom: 2rem;
    text-align: center;
    font-weight: bold;
  }

  /* Tarjetas con hover celeste */
  .results .card {
    padding: 1rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: background 0.3s, transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
  }

  .results .card:hover {
    background: #e6f0ff; /* celeste claro suave */
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
  }

  .card h3 {
    margin-bottom: 0.5rem;
    color: #007bff; /* opcional: resaltar títulos dentro de las tarjetas también */
  }

  .card p.meta {
    color: #555;
    font-size: 0.95rem;
  }
</style>

    
<!-- SECCIÓN SEGURIDAD -->
<section class="security-section">
  <div class="security-container">
    <div class="security-image">
      <img src="/subete/frontend/img/seguridad.png" alt="Seguridad de la app">
    </div>
    <div class="security-text">
      <h2>Reservas confiables, viajes tranquilos</h2>
      <p>
        En nuestra aplicación nos preocupamos profundamente por la seguridad de nuestros clientes.  
        Revisamos cuidadosamente perfiles y opiniones para que sepas con quién viajas,  
        y garantizamos que todas las reservas se realicen de manera confiable y protegida.
      </p>
    </div>
  </div>
</section>

<style>
  .security-section {
    width: 100%;
    display: flex;
    justify-content: center;
    background-color: #f7f9fc;
    padding: 4rem 0;
  }

  .security-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    width: 100%;
    gap: 3rem;
  }

  .security-image img {
    max-width: 50%;
    height: auto;
    border-radius: 8px;
  }

  .security-text {
    max-width: 45%;
  }

  .security-text h2 {
    font-size: 2.2rem;
    margin-bottom: 1.2rem;
    color: #007bff;
    font-weight: bold;
  }

  .security-text p {
    font-size: 1.2rem;
    color: #333;
    line-height: 1.8;
  }

  @media(max-width: 720px) {
    .security-container {
      flex-direction: column;
      text-align: center;
    }

    .security-image img {
      max-width: 80%;
    }

    .security-text h2 {
      margin-top: 1rem;
    }

    .security-text p {
      font-size: 1.1rem;
    }
  }
</style>




  </main>

  <!-- FOOTER -->
  <?php require __DIR__ . '/partials/footer.php'; ?>

  <script>
  const ENDPOINTS = {
    buscar: '/subete/backend/api/viajes/buscar-viajes.php?limit=4'
  };

  async function fetchJSON(url){
    const res = await fetch(url);
    if(!res.ok) throw new Error('HTTP '+res.status);
    return res.json();
  }

  function tripCard(v){
    const encom = Number(v.Permite_Encomiendas) === 1 ? ' · ✔ Encomiendas' : '';
    const cond  = v.Conductor_Nombre ? ` · ${v.Conductor_Nombre} ${v.Conductor_Apellido}` : '';
    const precio = (Number(v.Precio)||0).toLocaleString('es-AR');
    return `
  <a href="/subete/frontend/detalle-viaje.php?id=${v.ID_Viaje}" class="card-link">
    <article class="card">
      <h3>${v.Origen} → ${v.Destino}</h3>
      <p class="meta">Sale: ${v.Fecha_Hora_Salida}${cond}${encom}</p>
      <p class="meta">Asientos: ${v.Lugares_Disponibles} · Precio: $${precio}</p>
      ${v.Detalles ? `<p class="meta">Detalles: ${v.Detalles}</p>` : ''}
    </article>
  </a>`;
  }

  async function loadLastTrips(){
    const wrap = document.getElementById('lastTrips');
    try{
      const data = await fetchJSON(ENDPOINTS.buscar);
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

  loadLastTrips();
  </script>
</body>
</html>
