document.addEventListener("DOMContentLoaded", () => {
    // ==== Tab Switching ====
    const tabButtons = document.querySelectorAll(".tab-btn");
    const tabContents = document.querySelectorAll(".tab-content");

    tabButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            const target = btn.getAttribute("data-tab");

            tabButtons.forEach(b => b.classList.remove("active"));
            tabContents.forEach(c => c.classList.remove("active"));

            btn.classList.add("active");
            document.getElementById(`tab-${target}`).classList.add("active");
        });
    });

    // ==== Add Field Data Form Submission ====
    const fieldForm = document.getElementById("fieldDataForm");
    if (fieldForm) {
        fieldForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(fieldForm);

            try {
                const res = await fetch("ajax/add_field_data.php", {
                    method: "POST",
                    body: formData
                });
                const msg = await res.text();
                document.getElementById("fieldDataMsg").innerHTML = msg;
                if (res.ok) fieldForm.reset();
            } catch (err) {
                document.getElementById("fieldDataMsg").innerHTML = "Failed to save field data.";
            }
        });
    }

    // ==== Add Lab Data Form Submission ====
    const labForm = document.getElementById("labDataForm");
    if (labForm) {
        labForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(labForm);

            try {
                const res = await fetch("ajax/add_lab_data.php", {
                    method: "POST",
                    body: formData
                });
                const msg = await res.text();
                document.getElementById("labDataMsg").innerHTML = msg;
                if (res.ok) labForm.reset();
            } catch (err) {
                document.getElementById("labDataMsg").innerHTML = "Failed to save lab data.";
            }
        });
    }
});
