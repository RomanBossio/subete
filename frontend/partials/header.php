<header class="app-header"> 
  <div class="brand">Súbete</div>
  <nav class="nav" id="nav-links">
    <!-- Links agregados dinámicamente -->
  </nav>
</header>

<script>
  const nav = document.getElementById("nav-links");
  const usuario = JSON.parse(localStorage.getItem("usuario"));
  const currentPage = window.location.pathname;

  const esLogin = currentPage.includes("login.php");
  const esRegistro = currentPage.includes("registrar.php");

  if (!usuario && !esLogin && !esRegistro) {
    // Redirige solo si no es login ni registro
    window.location.href = "login.php";
  } else if (usuario) {
    const rol = usuario.rol;
    const nombre = usuario.nombre;

    const homePath = rol === "admin" ? "home-admin.php" : "home.php";

    // Links comunes
    nav.innerHTML += `
      <a href="/subete/frontend/${homePath}">Inicio</a>
      <a href="/subete/frontend/buscar.php">Buscar viajes</a>
      <a href="/subete/frontend/crear-viaje.php">Publicar</a>
    `;

    // Si es usuario común
    if (rol === "usuario") {
      nav.innerHTML += `
        <a href="/subete/frontend/mis-reservas.php">Mis viajes</a>
      `;
    }

    // Si es admin
    if (rol === "admin") {
      nav.innerHTML += `
        <a href="/subete/frontend/panel.php">Panel de control</a>
      `;
    }

    // Nombre y botón salir
    nav.innerHTML += `
      <span style="margin-left:10px">👋 ${nombre}</span>
      <a href="#" onclick="cerrarSesion()">Salir</a>
    `;
  }

  function cerrarSesion() {
    localStorage.removeItem("usuario");
    localStorage.removeItem("rol");
    window.location.href = "login.php";
  }
</script>
