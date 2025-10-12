document.addEventListener("DOMContentLoaded", () => {
    const registerForm = document.getElementById("registerForm");
    const registerMsg = document.getElementById("registerMsg");

    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        registerMsg.innerHTML = "";

        const formData = new FormData(registerForm);
        const password = formData.get("password");
        const confirm = formData.get("confirm_password");

        if (password !== confirm) {
            registerMsg.textContent = "❌ Passwords do not match.";
            registerMsg.style.color = "red";
            return;
        }

        try {
            const response = await fetch("/api/auth/register.php", {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                registerMsg.textContent = "✅ Registration successful. Please login.";
                registerMsg.style.color = "green";
                registerForm.reset();
            } else {
                registerMsg.textContent = "❌ " + result.message;
                registerMsg.style.color = "red";
            }
        } catch (error) {
            registerMsg.textContent = "❌ An error occurred. Try again.";
            registerMsg.style.color = "red";
            console.error("Register Error:", error);
        }
    });
});
