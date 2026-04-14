/**
 * Login Controller - Hospital HIS
 */

const loginForm = document.getElementById("loginForm");
const loginBtn = document.getElementById("loginBtn");

loginForm.addEventListener("submit", async (e) => {
  e.preventDefault();

  const username = document.getElementById("username").value.trim();
  const password = document.getElementById("password").value.trim();

  // Activar estado de carga en el botón
  loginBtn.classList.add("btn-loading");

  try {
    const response = await fetch("../api/auth/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ username, password })
    });

    const data = await response.json();

    if (!data.ok) {
      UI.toast.show(data.message || "Credenciales incorrectas", "error");
      loginBtn.classList.remove("btn-loading");
      return;
    }

    UI.toast.show("¡Bienvenido! Iniciando sesión...", "success");

    setTimeout(() => {
      window.location.href = "./dashboard.html";
    }, 1000);

  } catch (error) {
    UI.toast.show("Error de conexión con el servidor", "error");
    loginBtn.classList.remove("btn-loading");
    console.error(error);
  }
});
