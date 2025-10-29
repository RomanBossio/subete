<header class="app-header">
  <a href="/subete/frontend/home.php" class="brand">
    <img src="/subete/frontend/img/logo1.png" alt="Súbete" style="height:40px; vertical-align: middle; margin-right:8px;">
    Súbete
  </a>

  <nav class="nav">
    <div class="nav-left" id="nav-left">
      </div>
    <div class="nav-right" id="nav-right">
      </div>
  </nav>

  <style>
    .app-header { display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 1.5rem; background-color: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 1000; }
    .app-header .brand { display: flex; align-items: center; font-weight: bold; font-size: 1.5rem; color: #007bff; text-decoration: none; }
    .app-header .nav { display: flex; align-items: center; gap: 1.5rem; }
    .nav-left, .nav-right { display: flex; align-items: center; gap: 1rem; /* Espacio entre elementos */ }
    .nav-left a, .nav-right a:not(.notification-bell) { text-decoration: none; color: #333; font-weight: 500; transition: color 0.2s; padding: 5px 0; }
    .nav-left a:hover, .nav-right a:not(.notification-bell):hover { color: #007bff; }
    .nav-right .user-name { font-weight: 600; color: #007bff; margin-right: 0.5rem; /* Espacio antes de Salir */ }

    /* --- Estilos Notificaciones --- */
    .notification-container { position: relative; }
    .notification-bell {
        cursor: pointer;
        font-size: 1.5rem;
        color: #555;
        line-height: 1;
        position: relative;
        padding: 5px; /* Área clickeable */
        display: inline-block; /* Para que el badge se posicione bien */
        margin-right: 0.5rem; /* Espacio entre campana y nombre */
    }
    .notification-bell:hover { color: #007bff; }
    .notification-badge {
        position: absolute;
        top: 0px;
        right: 0px;
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 1px 5px;
        font-size: 0.65rem;
        font-weight: bold;
        border: 1px solid white;
        min-width: 16px; /* Asegura forma redonda */
        text-align: center;
        line-height: 14px;
    }
    #notification-panel {
        display: none;
        position: absolute;
        top: calc(100% + 10px); /* Debajo de la campana */
        right: 0; /* Alineado a la derecha del contenedor */
        width: 320px;
        max-height: 400px;
        overflow-y: auto;
        background-color: white;
        border: 1px solid #ccc;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
        z-index: 1001;
        font-size: 14px; /* Tamaño de fuente para items */
    }
    #notification-panel a.notification-item {
        display: block;
        padding: 10px 12px;
        border-bottom: 1px solid #eee;
        text-decoration: none;
        color: #333;
        white-space: normal; /* Permitir saltos de línea */
        line-height: 1.4;
        cursor: pointer;
    }
     #notification-panel a.notification-item:last-child { border-bottom: none; }
    #notification-panel a.notification-item:hover { background-color: #f5f5f5; }
    #notification-panel a.notification-item.unread { background-color: #e8f0fe; font-weight: bold; }
    #notification-panel .no-notifications { padding: 15px; text-align: center; color: #888; font-style: italic; }

     /* Responsive */
    @media(max-width: 720px) {
      .app-header { flex-direction: column; align-items: flex-start; gap: 8px; padding: 0.8rem 1rem; }
      .nav-left, .nav-right { gap: 0.8rem; }
      #notification-panel { right: 0; left: auto; top: 50px; /* Ajustar posición si es necesario */ }
    }
  </style>
</header>

<script>
  const BASE = "/subete/frontend/";
  const LOGIN = BASE + "login.php";
  const PANEL = BASE + "panel.php";
  const HOME_USER  = BASE + "home.php";

  function safeParse(json) {
    try { return JSON.parse(json); } catch { return null; }
  }

  const navLeft = document.getElementById("nav-left");
  const navRight = document.getElementById("nav-right");
  const usuario = safeParse(localStorage.getItem("usuario"));
  const token = localStorage.getItem("token"); // Usamos token de localStorage para JS
  const currentPath = window.location.pathname;

  const esLogin = currentPath.includes("/login.php");
  const esRegistro = currentPath.includes("/registrar.php");

  if (!usuario && !token && !esLogin && !esRegistro) {
    // Si no hay usuario ni token Y no estamos en login/registro, redirigir
    window.location.replace(LOGIN);
  } else if (usuario && token) {
    // Si hay usuario y token, configurar el header
    const rol = (usuario.rol || "").toLowerCase();
    const nombre = usuario.nombre || "Usuario";

    if (rol === "admin") {
      if (!currentPath.includes("/panel.php") && !currentPath.includes("/home-admin.php")) {
          // Permitir también home-admin.php
          window.location.replace(PANEL);
      }
      navRight.innerHTML = `<span class="user-name">👑 ${nombre}</span> <a href="#" id="btn-salir">Salir</a>`;
    } else {
      // Usuario común
      navLeft.innerHTML = `
        <a href="${HOME_USER}">Inicio</a>
        <a href="${BASE}buscar.php">Buscar viajes</a>
        <a href="${BASE}crear-viaje.php">Publicar</a>
        <a href="${BASE}mis-reservas.php">Mis viajes</a>
      `;
      // Insertar elementos en navRight CON la campana
      navRight.innerHTML = `
        <div class="notification-container">
          <a id="notification-bell" class="notification-bell">🔔
            <span id="notification-badge" class="notification-badge" style="display: none;"></span>
          </a>
          <div id="notification-panel"></div>
        </div>
        <span class="user-name">👋 ${nombre}</span>
        <a href="#" id="btn-salir">Salir</a>
      `;
      initializeNotifications(); // Llamar a la función que activa las notificaciones
    }
  } else if (!usuario && token && !esLogin && !esRegistro) {
      // Caso raro: hay token pero no datos de usuario (quizás se borró localStorage)
      console.warn("Token presente pero datos de usuario ausentes. Intentando limpiar y redirigir.");
      cerrarSesion(); // Limpiar y redirigir a login
  }

  function cerrarSesion() {
    localStorage.removeItem("usuario");
    localStorage.removeItem("token"); // Asegurarse de borrar el token también
    localStorage.removeItem("rol");
    sessionStorage.clear();
    // Borrar cookie si la usan para PHP (opcional, depende de tu setup)
    document.cookie = "token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    location.replace(LOGIN);
  }

  document.addEventListener("click", (e) => {
    if (e.target && e.target.id === "btn-salir") {
      e.preventDefault();
      cerrarSesion();
    }
  });

  // --- LÓGICA DE NOTIFICACIONES (dentro de una función) ---
  function initializeNotifications() {
      const bellLink = document.getElementById("notification-bell");
      const badge = document.getElementById("notification-badge");
      const panel = document.getElementById("notification-panel");

      if (!bellLink || !badge || !panel) {
          console.error("Elementos de notificación no encontrados en el DOM.");
          return; // Salir si los elementos no existen
      }

      async function fetchNotifications() {
          if (!token) return;
          try {
              const res = await fetch('/subete/backend/api/utils/notificaciones.php', {
                  headers: { 'Authorization': 'Bearer ' + token }
              });
              // Manejar respuesta no-ok (ej. 401 Unauthorized)
              if (!res.ok) {
                  console.error("Error al obtener notificaciones:", res.status, res.statusText);
                  if (res.status === 401) cerrarSesion(); // Si el token es inválido, cerrar sesión
                  return;
              }
              const data = await res.json();

              if (data.ok) {
                  badge.style.display = data.no_leidas > 0 ? 'block' : 'none';
                  badge.textContent = data.no_leidas > 0 ? data.no_leidas : '';

                  if (data.notificaciones && data.notificaciones.length > 0) {
                      panel.innerHTML = data.notificaciones.map(n => {
                          const link = n.ID_Viaje_Relacionado
                              ? `${BASE}detalle-viaje.php?id=${n.ID_Viaje_Relacionado}`
                              : '#!'; // Usar #! para enlaces no funcionales
                          return `<a href="${link}" class="notification-item ${n.Leida == 0 ? 'unread' : ''}" data-notif-id="${n.ID_Notificacion}">${n.Mensaje}</a>`;
                      }).join('');
                  } else {
                      panel.innerHTML = `<div class="no-notifications">No tienes notificaciones.</div>`;
                  }
              } else {
                  console.error("La API de notificaciones devolvió error:", data.error);
              }
          } catch (e) { console.error("Error de red al obtener notificaciones:", e); }
      }

      async function markAsRead() {
          // Solo marcar si hay badge visible y es > 0
          if (!token || badge.style.display === 'none' || !(parseInt(badge.textContent) > 0)) return;
          try {
              const res = await fetch('/subete/backend/api/utils/marcar-leidas.php', {
                  method: 'POST',
                  headers: { 'Authorization': 'Bearer ' + token }
              });
              const data = await res.json();
              if (data.ok) {
                  badge.style.display = 'none';
                  badge.textContent = '';
                  panel.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
              } else {
                  console.error("Error al marcar como leídas:", data.error);
              }
          } catch (e) { console.error("Error de red al marcar como leídas:", e); }
      }

      bellLink.addEventListener('click', (e) => {
          e.stopPropagation();
          const isVisible = panel.style.display === 'block';
          panel.style.display = isVisible ? 'none' : 'block';
          if (!isVisible && badge.style.display === 'block') {
              setTimeout(markAsRead, 1500); // Marcar después de abrir
          }
      });

      panel.addEventListener('click', e => e.stopPropagation()); // Evitar que clic dentro cierre el panel
      document.addEventListener('click', (e) => {
          // Cerrar si se hace clic fuera del panel Y fuera de la campana
          if (!panel.contains(e.target) && !bellLink.contains(e.target)) {
              panel.style.display = 'none';
          }
      });

      fetchNotifications(); // Carga inicial
      setInterval(fetchNotifications, 60000); // Refrescar cada minuto
  }

</script>