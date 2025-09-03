<header class="app-header">
  <div class="brand">Súbete</div>
  <nav class="nav">
    <a href="/subete/frontend/home.php"
       class="<?= ($page??'')==='home' ? 'active' : '' ?>">Inicio</a>
    <a href="/subete/frontend/buscar.php"
       class="<?= ($page??'')==='buscar' ? 'active' : '' ?>">Buscar viajes</a>
    <a href="/subete/frontend/crear-viaje.php"
       class="<?= ($page??'')==='crear' ? 'active' : '' ?>">Publicar</a>
    <a href="/subete/frontend/mis-reservas.php"
       class="<?= ($page??'')==='mis' ? 'active' : '' ?>">Mis viajes</a>
    <a href="/subete/frontend/logout.php">Salir</a>
  </nav>
</header>
