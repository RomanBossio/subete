// Esperamos a que todo el DOM esté cargado
document.addEventListener("DOMContentLoaded", () => {

  const loginForm = document.getElementById("loginForm");
  const alertBox = document.getElementById("alert");

  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Obtenemos los valores del formulario
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

    try {
      // Ruta correcta según tu estructura de carpetas
      const response = await fetch("../backend/api/auth/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();

      if (!response.ok) {
        // Mostrar error en la pantalla
        alertBox.textContent = data.error || "Error en el login";
        alertBox.className = "alert error show";
      } else {
        // Login exitoso
        alertBox.textContent = "✅ Bienvenido " + data.usuario.nombre;
        alertBox.className = "alert success show";

        // Guardar datos del usuario en localStorage
        localStorage.setItem("usuario", JSON.stringify(data.usuario));

        // Redirigir al dashboard después de 1 segundo
        setTimeout(() => {
          window.location.href = "home.php"; 
        }, 1000);
      }

    } catch (err) {
      // Error de conexión con el servidor
      alertBox.textContent = "⚠️ Error de conexión con el servidor";
      alertBox.className = "alert error show";
      console.error(err);
    }

  });

});