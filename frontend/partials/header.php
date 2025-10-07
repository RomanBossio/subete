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
  const BASE = "/subete/frontend/";
  const LOGIN = BASE + "login.php";
  const PANEL = BASE + "panel.php"; // 👑 Página principal del admin
  const HOME_USER  = BASE + "home.php";

  function safeParse(json) {
    try { return JSON.parse(json); } catch { return null; }
  }

  const navLeft = document.getElementById("nav-left");
  const navRight = document.getElementById("nav-right");
  const usuario = safeParse(localStorage.getItem("usuario"));
  const currentPath = window.location.pathname;

  const esLogin         = currentPath.endsWith("/login.php");
  const esRegistro      = currentPath.endsWith("/registrar.php") || currentPath.endsWith("/register.php");
  const esResetPassword = currentPath.endsWith("/reset-password.php");

  // ======== Protección de acceso ========
  if (!usuario && !esLogin && !esRegistro && !esResetPassword) {
    window.location.replace(LOGIN);
  } else if (usuario) {
    const rol     = (usuario.rol || usuario.Rol || "").toLowerCase();
    const nombre  = usuario.nombre || usuario.Nombre || usuario.name || "Usuario";

    // 👑 Si es admin
    if (rol === "admin") {
      // Si no está en el panel, redirigirlo
      if (!currentPath.endsWith("/panel.php")) {
        window.location.replace(PANEL);
      }

      // Header solo con nombre y salir
      navLeft.innerHTML = "";
      navRight.innerHTML = `
        <span>👑 ${nombre}</span>
        <a href="#" id="btn-salir">Salir</a>
      `;
    }

    // 👤 Si es usuario común
    else {
      navLeft.innerHTML = `
        <a href="${HOME_USER}">Inicio</a>
        <a href="${BASE}buscar.php">Buscar viajes</a>
        <a href="${BASE}crear-viaje.php">Publicar</a>
        <a href="${BASE}mis-reservas.php">Mis viajes</a>
      `;
      navRight.innerHTML = `
        <span>👋 ${nombre}</span>
        <a href="#" id="btn-salir">Salir</a>
      `;
    }
  }

  // ======== Cerrar sesión ========
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

  // ======== Anti-cache / volver atrás ========
  window.addEventListener("pageshow", (e) => {
    if (e.persisted) location.reload();
  });
</script>

