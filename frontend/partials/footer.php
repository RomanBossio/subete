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

  <!-- Copyright -->
  <div class="footer-bottom">
    © <?= date('Y') ?> Súbete. Todos los derechos reservados.
  </div>
</footer>
