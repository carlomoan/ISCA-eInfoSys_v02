function goBack() {
    if (document.referrer) {
        window.location.href = document.referrer; 
    } else {
        window.location.href = 'index.php?page=dashboard'; // fallback
    }
}



document.addEventListener('DOMContentLoaded', function() {
    // Show modal
    const modal = document.getElementById('changePasswordModal');
    if(modal) modal.classList.add('show');

    // Toggle password visibility
    function togglePassword(fieldId, icon) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            field.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    window.togglePassword = togglePassword; // Make it global for inline onclick

    // Form submit with toast
    const form = document.getElementById('change-password-form');
    if(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const formData = new FormData(form);

            // Optional: processing toast
            if(typeof showToast === "function") showToast("Processing...", "info");

            fetch(BASE_URL + '/api/auth/change_password_api.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(r => {
                if(typeof showToast === "function"){
                    showToast(r.message, r.success ? "success" : "error");
                }

                if(r.success){
                    form.reset();
                    setTimeout(() => {
                        window.location.href = BASE_URL + '/logout.php';
                    }, 1500);
                }
            })
            .catch(err => {
                if(typeof showToast === "function") showToast("An error occurred. Try again.", "error");
                console.error(err);
            });
        });
    }
});
