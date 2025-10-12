

document.addEventListener("DOMContentLoaded", function () {
    // ========================
    // ✅ TAB SWITCHING 
    // ========================
    const tabs = document.querySelectorAll(".tab-btn");
    const tabContents = document.querySelectorAll(".tab-content");
    tabs.forEach(tab => {
        tab.addEventListener("click", function () {
            tabs.forEach(t => t.classList.remove("active"));
            this.classList.add("active");
            const selectedTab = this.dataset.tab;
            tabContents.forEach(tc => tc.classList.toggle("active", tc.id === "tab-" + selectedTab));
        });
    });

    // ========================
    // ✅ GENERATE LIVE SEARCH + PAGINATION FUNCTION
    // ========================
    function setupTableFeatures(tableSelector, rowsPerPage = 10) {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const tbody = table.querySelector("tbody");
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll("tr"));
        const container = table.parentElement;

        // ✅ Create search box
        const searchBox = document.createElement("input");
        searchBox.type = "text";
        searchBox.placeholder = "Type to search...";
        searchBox.style.marginBottom = "6px";
        searchBox.style.padding = "5px";
        searchBox.style.width = "250px";
        container.insertBefore(searchBox, table);

        // ✅ Create pagination container
        const pagination = document.createElement("div");
        pagination.className = "pagination";
        container.appendChild(pagination);

        let currentPage = 1;
        let filteredRows = [...rows];

        function renderTable() {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            rows.forEach(r => (r.style.display = "none"));
            filteredRows.slice(start, end).forEach(r => (r.style.display = ""));

            renderPagination();
        }

        function renderPagination() {
            pagination.innerHTML = "";
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (totalPages <= 1) return;

            for (let i = 1; i <= totalPages; i++) {
                const pageLink = document.createElement("a");
                pageLink.textContent = i;
                pageLink.href = "#";
                pageLink.className = i === currentPage ? "active" : "";
                pageLink.addEventListener("click", (e) => {
                    e.preventDefault();
                    currentPage = i;
                    renderTable();
                });
                pagination.appendChild(pageLink);
            }
        }

        // ✅ Search filter event
        searchBox.addEventListener("input", function () {
            const query = this.value.toLowerCase();
            filteredRows = rows.filter(row =>
                row.textContent.toLowerCase().includes(query)
            );
            currentPage = 1;
            renderTable();
        });

        renderTable();
    }

    // ✅ Apply to both tables
    setupTableFeatures("#generatedReportsTable", 10);
    setupTableFeatures("#uploadedReportsTable", 10);

    // ========================
    // ✅ GENERATED VIEW SELECT CHANGE
    // ========================
    const generatedViewSelect = document.getElementById("generatedViewSelect");
    if (generatedViewSelect) {
        generatedViewSelect.addEventListener("change", function () {
            const selectedView = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'generated');
            url.searchParams.set('view', selectedView);
            window.location.href = url.toString();
        });
    }

    // ========================
    // ✅ UPLOAD FORM + UPLOAD BUTTON
    // ========================
    const uploadForm = document.getElementById("uploadReportForm");
    if (uploadForm) {
        const messageBox = document.getElementById("upload-message");
        const submitBtn = uploadForm.querySelector("button[type='submit']");
        uploadForm.addEventListener("submit", function (e) {
            e.preventDefault();
            messageBox.style.display = "none";
            messageBox.textContent = "";
            messageBox.className = "";

            const formData = new FormData(this);

            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = "Uploading...";

            fetch(BASE_URL + "/controllers/upload_report.php", {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        messageBox.textContent = data.message;
                        messageBox.className = "message success";
                        messageBox.style.display = "block";
                        uploadForm.reset();

                        // ✅ Switch to uploaded tab
                        tabs.forEach(t => t.classList.remove("active"));
                        const uploadedTabBtn = document.querySelector(".tab-btn[data-tab='uploaded']");
                        const uploadedTabContent = document.getElementById("tab-uploaded");
                        if (uploadedTabBtn && uploadedTabContent) {
                            uploadedTabBtn.classList.add("active");
                            tabContents.forEach(tc => tc.classList.remove("active"));
                            uploadedTabContent.classList.add("active");
                        }
                    } else {
                        messageBox.textContent = data.message;
                        messageBox.className = "message error";
                        messageBox.style.display = "block";
                    }
                })
                .catch(() => {
                    messageBox.textContent = "An error occurred during upload.";
                    messageBox.className = "message error";
                    messageBox.style.display = "block";
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        });
    }

    // ========================
    // ✅ MODAL VIEW + VIEW BUTTON
    // ========================
    const modal = document.getElementById("reportModal");
    const modalBody = document.getElementById("modalBody");
    const closeModal = document.querySelector(".close");

    document.querySelectorAll(".view-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const reportId = this.dataset.id;
            const reportType = this.dataset.type;
            const reportView = this.dataset.view ?? '';

            modal.style.display = "block";
            modalBody.innerHTML = "<p style='padding:10px;'>Loading...</p>";

            fetch(`${BASE_URL}/controllers/view_report.php?type=${reportType}&id=${reportId}&view=${reportView}`)
                .then(res => res.text())
                .then(html => {
                    modalBody.innerHTML = html;
                    modal.querySelector(".modal-content").style.maxWidth = "95%";
                    modal.querySelector(".modal-content").style.overflowX = "auto";
                })
                .catch(() => {
                    modalBody.innerHTML = "<p style='color:red;'>Failed to load report details.</p>";
                });
        });
    });

    closeModal.onclick = function () {
        modal.style.display = "none";
    };
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };

});

