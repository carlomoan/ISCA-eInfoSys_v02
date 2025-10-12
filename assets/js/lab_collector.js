// assets/js/lab_collector.js
document.addEventListener('DOMContentLoaded', function () {

    // ===== Toast =====
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        document.body.appendChild(toast);
    }

    window.showToast = (msg, isError = false) => {
        toast.textContent = msg;
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.padding = '10px 20px';
        toast.style.color = '#fff';
        toast.style.borderRadius = '5px';
        toast.style.zIndex = '9999';
        toast.style.backgroundColor = isError ? '#e74c3c' : '#2ecc71';
        toast.style.opacity = '1';
        toast.style.transition = 'opacity 0.5s ease';

        setTimeout(() => {
            toast.style.opacity = '0';
        }, 3000);
    };

    // ===== Form Submit =====
    const form = document.getElementById('lab-data-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(form);

            $.ajax({
                url: '/api/desklab/add_lab_data.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',

                success: function (response) {
                    if (response.success) {
                        showToast(response.message || "Lab data submitted successfully.");
                        form.reset();
                    } else {
                        showToast(response.message || "Failed to submit lab data.", true);
                    }
                },

                error: function (xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    showToast("An unexpected error occurred. Try again.", true);
                }
            });
        });
    }

});
