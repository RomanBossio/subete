document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("registerForm");
    const errorDiv = document.getElementById("error");
    const successDiv = document.getElementById("success");

    // Funciones auxiliares para mostrar mensajes
    const showError = (msg) => {
        errorDiv.textContent = msg;
        errorDiv.style.display = "block";
        errorDiv.classList.add("show");
        successDiv.style.display = "none";
        successDiv.classList.remove("show");
    };

    const showSuccess = (msg) => {
        successDiv.textContent = msg;
        successDiv.style.display = "block";
        successDiv.classList.add("show");
        errorDiv.style.display = "none";
        errorDiv.classList.remove("show");
    };

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        // Tomamos los valores del formulario
        const nombre = document.getElementById("registerNombre").value.trim();
        const apellido = document.getElementById("registerApellido").value.trim();
        const email = document.getElementById("registerEmail").value.trim();
        const telefono = document.getElementById("registerTelefono").value.trim();
        const password = document.getElementById("registerPassword").value;

        // Validación básica
        if (!nombre || !apellido || !email || !password) {
            showError("Por favor, completa todos los campos obligatorios.");
            return;
        }

        try {
            const response = await fetch('../backend/api/auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre, apellido, email, telefono, password })
            });

            const data = await response.json();

            if (response.ok) {
                showSuccess(data.message || "Usuario registrado correctamente.");
                form.reset();
            } else {
                showError(data.message || "Ocurrió un error al registrar el usuario.");
            }

        } catch (error) {
            console.error("Error en la conexión:", error);
            showError("No se pudo conectar con el servidor. Revisa la ruta y que XAMPP esté encendido.");
        }
    });
});
