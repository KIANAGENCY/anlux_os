// JavaScript de login.

// 👁️ MOSTRAR / OCULTAR CONTRASEÑA
const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");

togglePassword.addEventListener("click", () => {
    const type = passwordInput.type === "password" ? "text" : "password";
    passwordInput.type = type;

    togglePassword.innerHTML =
        type === "password"
            ? '<i class="fas fa-eye"></i>'
            : '<i class="fas fa-eye-slash"></i>';
});

// LOGIN
const form = document.getElementById("loginForm");
const message = document.getElementById("message");

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const data = new URLSearchParams();
    data.append("email", document.getElementById("email").value);
    data.append("password", document.getElementById("password").value);
    data.append("remember", document.getElementById("remember").checked ? "on" : "off");
    data.append("csrf_token", window.ANLUX_CSRF_TOKEN || "");

    try {
        const res = await fetch("actions/login_api.php", {
            method: "POST",
            body: data
        });
        const result = await res.json();

        if (result.success) {
            message.classList.add("hidden");
            location.href = result.redirect;
            return;
        }

        if (result && result.reload) {
            const url = new URL(window.location.href);
            url.searchParams.set("pwd_error", "1");
            window.location.href = url.pathname + url.search;
            return;
        }

        message.textContent = result.message;
        message.className = "mt-4 p-4 bg-red-500 text-white text-center rounded-lg";
        message.classList.remove("hidden");
    } catch (err) {
        message.textContent = "No se pudo conectar. Intenta de nuevo.";
        message.className = "mt-4 p-4 bg-red-500 text-white text-center rounded-lg";
        message.classList.remove("hidden");
        }
});
