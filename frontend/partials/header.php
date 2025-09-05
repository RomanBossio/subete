<header class="app-header"> 
  <div class="brand">Súbete</div>
  <nav class="nav" id="nav-links">
    <!-- Links agregados dinámicamente -->
  </nav>
</header>

<script>
  // ======== Config rutas ========
  const BASE = "/subete/frontend/";
  const LOGIN = BASE + "login.php";
  const HOME_ADMIN = BASE + "home-admin.php";
  const HOME_USER  = BASE + "home.php";

  // ======== Util: parse seguro ========
  function safeParse(json) {
    try { return JSON.parse(json); } catch { return null; }
  }

  const nav = document.getElementById("nav-links");
  const usuario = safeParse(localStorage.getItem("usuario")) || null;
  const currentPath = window.location.pathname;

  // Páginas públicas (ajustá nombres si tu registro es register.php, etc.)
  const esLogin     = currentPath.endsWith("/login.php");
  const esRegistro  = currentPath.endsWith("/registrar.php") || currentPath.endsWith("/register.php");

  // ======== Guard de acceso ========
  if (!usuario && !esLogin && !esRegistro) {
    window.location.replace(LOGIN);
  } else if (usuario) {
    // Normalizo campos por si cambian de nombre
    const rol     = (usuario.rol || usuario.Rol || "").toLowerCase();
    const nombre  = usuario.nombre || usuario.Nombre || usuario.name || "Usuario";
    const homePath = rol === "admin" ? HOME_ADMIN : HOME_USER;

    // Links comunes
    nav.innerHTML += `
      <a href="${homePath}">Inicio</a>
      <a href="${BASE}buscar.php">Buscar viajes</a>
      <a href="${BASE}crear-viaje.php">Publicar</a>
    `;

    // Si es usuario común
    if (rol === "usuario" || rol === "user") {
      nav.innerHTML += `<a href="${BASE}mis-reservas.php">Mis viajes</a>`;
    }

    // Si es admin
    if (rol === "admin") {
      nav.innerHTML += `<a href="${BASE}panel.php">Panel de control</a>`;
    }

    // Nombre y botón salir
    nav.innerHTML += `
      <span style="margin-left:10px">👋 ${nombre}</span>
      <a href="#" id="btn-salir">Salir</a>
    `;
  }

  // ======== Salir ========
  function cerrarSesion() {
    try {
      localStorage.removeItem("usuario");
      localStorage.removeItem("rol");
      sessionStorage.clear();
    } catch {}
    // replace para que no quede en el historial
    location.replace(LOGIN);
  }

  document.addEventListener("click", (e) => {
    if (e.target && e.target.id === "btn-salir") {
      e.preventDefault();
      cerrarSesion();
    }
  });

  // ======== Anti-cache / botón Atrás ========
  window.addEventListener("pageshow", (e) => {
    if (e.persisted) location.reload();
  });

  window.addEventListener("popstate", () => {
    const u2 = safeParse(localStorage.getItem("usuario"));
    if (!u2) location.replace(LOGIN);
  });
</script>
