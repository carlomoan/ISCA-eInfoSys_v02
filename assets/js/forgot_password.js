document.addEventListener("DOMContentLoaded", () => {
    const forgotForm = document.getElementById("forgotPasswordForm");
    const forgotMsg = document.getElementById("forgotMsg");

    forgotForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        forgotMsg.innerHTML = "";

        const formData = new FormData(forgotForm);

        try {
            const response = await fetch("/api/auth/forgot_password.php", {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                forgotMsg.textContent = "✅ Reset link sent to your email.";
                forgotMsg.style.color = "green";
                forgotForm.reset();
            } else {
                forgotMsg.textContent = "❌ " + result.message;
                forgotMsg.style.color = "red";
            }
        } catch (error) {
            forgotMsg.textContent = "❌ An error occurred. Try again.";
            forgotMsg.style.color = "red";
            console.error("Forgot Error:", error);
        }
    });
});
