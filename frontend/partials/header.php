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
  const navLeft = document.getElementById("nav-left");
  const navRight = document.getElementById("nav-right");
  const usuario = JSON.parse(localStorage.getItem("usuario"));
  const currentPage = window.location.pathname;

  const esLogin = currentPage.includes("login.php");
  const esRegistro = currentPage.includes("registrar.php");

  if (!usuario && !esLogin && !esRegistro) {
    window.location.href = "login.php";
  } else if (usuario) {
    const rol = usuario.rol;
    const nombre = usuario.nombre;

    const homePath = rol === "admin" ? "home-admin.php" : "home.php";

    // Links comunes
    navLeft.innerHTML += `
      <a href="/subete/frontend/${homePath}">Inicio</a>
      <a href="/subete/frontend/buscar.php">Buscar viajes</a>
      <a href="/subete/frontend/crear-viaje.php">Publicar</a>
    `;

    // Si es usuario común
    if (rol === "usuario") {
      navLeft.innerHTML += `<a href="/subete/frontend/mis-reservas.php">Mis viajes</a>`;
    }

    // Si es admin
    if (rol === "admin") {
      navLeft.innerHTML += `<a href="/subete/frontend/panel.php">Panel de control</a>`;
    }

    // Usuario + salir
    navRight.innerHTML = `
      <span>👋 ${nombre}</span>
      <a href="#" onclick="cerrarSesion()">Salir</a>
    `;
  }

  function cerrarSesion() {
    localStorage.removeItem("usuario");
    localStorage.removeItem("rol");
    window.location.href = "login.php";
  }
</script>
