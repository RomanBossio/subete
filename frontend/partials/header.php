<header class="app-header"> 
  <div class="brand">Súbete</div>
  <nav class="nav">
    <div class="nav-left" id="nav-left">
      <!-- Links agregados dinámicamente -->
    </div>
    <div class="nav-right" id="nav-right">
      <!-- Usuario y salir -->
    </div>
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

  const navLeft = document.getElementById("nav-left");
  const navRight = document.getElementById("nav-right");
  const usuario = safeParse(localStorage.getItem("usuario"));
  const currentPath = window.location.pathname;

  // Páginas públicas
  const esLogin        = currentPath.endsWith("/login.php");
  const esRegistro     = currentPath.endsWith("/registrar.php") || currentPath.endsWith("/register.php");
  const esResetPassword = currentPath.endsWith("/reset-password.php");

  // ======== Guard de acceso modificado ========
  if (!usuario && !esLogin && !esRegistro && !esResetPassword) {
    window.location.replace(LOGIN);
  } else if (usuario) {
    // Normalizo campos por si cambian de nombre
    const rol     = (usuario.rol || usuario.Rol || "").toLowerCase();
    const nombre  = usuario.nombre || usuario.Nombre || usuario.name || "Usuario";
    const homePath = rol === "admin" ? HOME_ADMIN : HOME_USER;

    // Links comunes
    navLeft.innerHTML += `
      <a href="${homePath}">Inicio</a>
      <a href="${BASE}buscar.php">Buscar viajes</a>
      <a href="${BASE}crear-viaje.php">Publicar</a>
    `;

    // Si es usuario común
    if (rol === "usuario" || rol === "user") {
      navLeft.innerHTML += `<a href="${BASE}mis-reservas.php">Mis viajes</a>`;
    }

    // Si es admin
    if (rol === "admin") {
      navLeft.innerHTML += `<a href="${BASE}panel.php">Panel de control</a>`;
    }

    // Usuario + salir
    navRight.innerHTML = `
      <span>👋 ${nombre}</span>
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
    if (!u2 && !esLogin && !esRegistro && !esResetPassword) location.replace(LOGIN);
  });
</script>
