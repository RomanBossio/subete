<?php
// footer.php
?>

<style>
/* ===== Footer mejorado ===== */
.site-footer{
  margin-top:30px;
  padding:24px;
  border-radius:12px;
  background:#e6fdf8; /* verde agua muy clarito */
  color:#07261f;
  position: relative; /* necesario para posicionar el logo dentro */
}

.footer-grid{
  display:grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap:20px;
  align-items:flex-start;
}

.footer-col h2.brand{
  margin:0;
  font-size:22px;
  font-weight:800;
}

.footer-col .tagline{
  margin-top:4px;
  font-size:14px;
  color:#07261f;
}

.footer-col h3{
  margin-bottom:8px;
  font-size:16px;
  font-weight:700;
}

.footer-links{
  list-style:none;
  padding:0;
  margin:0;
}

.footer-links li{
  margin-bottom:6px;
}

.footer-links a{
  color:#07261f;
  text-decoration:none;
}

.footer-links a:hover{
  text-decoration:underline;
}

/* === Imagen del logo flotando a la derecha === */
.footer-logo-fixed{
  position: absolute;
  top: 50%;              /* lo centra verticalmente */
  right: 40px;           /* mantiene el mismo margen lateral */
  transform: translateY(-50%); /* centra el logo respecto al alto */
  cursor: pointer;       /* indica que es clickeable */
}

.footer-logo-fixed img{
  max-width: 150px;      /* tamaño más grande */
  height: auto;
  opacity: 0.9;
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.footer-logo-fixed img:hover{
  opacity: 1;
  transform: translateY(-50%) scale(1.1); /* mantiene centrado al hacer zoom */
}

/* Copyright al final, centrado */
.footer-bottom{
  text-align:center;
  margin-top:20px;
  font-size:13px;
  color:#07261f;
}

/* Responsive */
@media(max-width:720px){
  .footer-grid{ grid-template-columns:1fr; text-align:center; gap:16px; }
  .footer-logo-fixed{
    position: static;
    margin: 15px auto 0;
    text-align: center;
  }
  .footer-logo-fixed img{
    max-width:120px;
    transform:none;
  }
}
</style>

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
        <li><a href="crear-viaje.php">Publicar viaje</a></li>
        <li><a href="buscar.php">Viajes</a></li>
      </ul>
    </div>

    <!-- Columna 3 -->
    <div class="footer-col">
      <h3>Soporte</h3>
      <ul class="footer-links">
        <li><a href="ayuda.php">Ayuda</a></li>
        <li><a href="contacto.php">Contacto</a></li>
        <li><a href="terminos.php">Términos</a></li>
      </ul>
    </div>
  </div>

  <!-- Logo clickeable que lleva arriba -->
  <div class="footer-logo-fixed" onclick="scrollToTop()" title="Volver arriba">
    <img src="/subete/frontend/img/logo1.png" alt="Logo Súbete">
  </div>

  <!-- Copyright -->
  <div class="footer-bottom">
    © <?= date('Y') ?> Súbete. Todos los derechos reservados.
  </div>
</footer>

<script>
  // Scroll suave hacia arriba
  function scrollToTop() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  }
</script>
