document.addEventListener("DOMContentLoaded", function () {
    const BASE_URL = "/ISCA-eInfoSys_v02";

    // ===== Toast System =====
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        document.body.appendChild(toastContainer);
    }
    function showToast(msg, type = "info", duration = 4000) {
        const t = document.createElement("div");
        t.className = `toast ${type}`;
        t.innerText = msg;
        toastContainer.appendChild(t);
        setTimeout(() => t.classList.add("visible"), 50);
        setTimeout(() => { t.classList.remove("visible"); setTimeout(() => t.remove(), 500); }, duration);
    }

    // ===== DataTable =====
    let table;
    function loadUsersTable() {
        if (!$.fn.DataTable.isDataTable("#usersTable")) {
            table = $("#usersTable").DataTable({
                ajax: BASE_URL + "/api/auth/manage_user_api.php?action=get_users",
                columns: [
                    { data: "fullname" },
                    { data: "email" },
                    { data: "phone" },
                    { data: "role_name" },
                    {
                        data: "projects",
                        render: projects => projects.length
                            ? projects.map(p => `<span class="badge bg-info">${p.project_name}</span>`).join(" ")
                            : "<i>No Project</i>"
                    },
                    {
                        data: "status",
                        render: status => status === "active"
                            ? `<span class="status-active">Active</span>`
                            : `<span class="status-inactive">Inactive</span>`
                    },
                    {
                        data: null,
                        render: row => `
                            <button class="btn-edit btn btn-sm btn-primary" data-id="${row.id}">✏️</button>
                            <button class="btn-toggle btn btn-sm btn-warning" data-id="${row.id}" data-status="${row.status}">
                                ${row.status === "active" ? "Deactivate" : "Activate"}
                            </button>
                            <button class="btn-delete btn btn-sm btn-danger" data-id="${row.id}">🗑️</button>
                        `
                    }
                ],
                destroy: true,
                responsive: true,
                pageLength: 10
            });
        } else {
            table.ajax.reload(null, false);
        }
    }

    loadUsersTable();

    // ===== Event Delegation =====
    $("#usersTable").on("click", ".btn-edit", function () {
        const id = $(this).data("id");
        showToast(`Edit user ID: ${id}`, "info");
        // TODO: Open modal
    });

    $("#usersTable").on("click", ".btn-toggle", function () {
        const id = $(this).data("id");
        const status = $(this).data("status");
        const newStatus = status === "active" ? "inactive" : "active";
        if (!confirm(`Are you sure you want to ${newStatus} this user?`)) return;
        fetch(`${BASE_URL}/api/auth/manage_user_api.php?action=toggle_status`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id, status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            showToast(data.message, data.success ? "success" : "error");
            if (data.success) table.ajax.reload(null, false);
        });
    });

    $("#usersTable").on("click", ".btn-delete", function () {
        const id = $(this).data("id");
        if (!confirm("Are you sure you want to delete this user?")) return;
        fetch(`${BASE_URL}/api/auth/manage_user_api.php?action=delete_user`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        })
        .then(res => res.json())
        .then(data => {
            showToast(data.message, data.success ? "success" : "error");
            if (data.success) table.ajax.reload(null, false);
        });
    });
});
