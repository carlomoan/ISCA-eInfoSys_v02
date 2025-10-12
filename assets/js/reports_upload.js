document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("uploadReportForm");
    const messageBox = document.getElementById("uploadMessage");

    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(BASE_URL + "/ajax/upload_report.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    messageBox.style.color = "#0b76ef"; // light blue
                    messageBox.textContent = data.message;
                    form.reset();
                } else {
                    messageBox.style.color = "red";
                    messageBox.textContent = data.message;
                }
            })
            .catch(() => {
                messageBox.style.color = "red";
                messageBox.textContent = "Error occurred while uploading.";
            });
        });
    }
});
