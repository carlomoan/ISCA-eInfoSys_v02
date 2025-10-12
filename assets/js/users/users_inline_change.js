$(document).ready(function () {
    let selectedUserId = null;

    // === OPEN MODAL ===
    $(document).on("click", ".btn-manage-user", function () {
        selectedUserId = $(this).data("id");
        $("#manage_user_id").val(selectedUserId);

        // reset selections
        $("#assignRolesSelect").val("");
        $("#assignProjectsSelect").val("");
        $("#assignClustersSelect").val("");
        $("#lab_duty_select").val("");

        // load fresh lists
        loadAssignmentLists();

        $("#manageUserModal").modal("show");
    });

    // === SAVE CHANGES ===
    $("#saveUserChanges").on("click", function () {
        if (!selectedUserId) {
            alert("⚠ No user selected!");
            return;
        }

        const roleId = $("#assignRolesSelect").val();
        const projectId = $("#assignProjectsSelect").val();
        const clusterId = $("#assignClustersSelect").val();
        const labAction = $("#lab_duty_select").val();

        $.ajax({
            url: BASE_URL + "/api/users/users_manage_api.php",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                user_id: selectedUserId,
                role_id: roleId || null,
                project_id: projectId || null,
                cluster_id: clusterId || null,
                lab_action: labAction || null
            }),
            success: function (res) {
                if (res.success) {
                    alert("✅ " + res.message);
                    $("#manageUserModal").modal("hide");
                    $("#userTable").DataTable().ajax.reload(null, false);
                } else {
                    alert("❌ " + res.message);
                }
            },
            error: function (xhr) {
                alert("❌ Request failed: " + xhr.responseText);
            }
        });
    });

    // === LOAD LISTS (Roles, Projects, Clusters) ===
    async function loadAssignmentLists() {
        try {
            // Roles
            const rolesRes = await fetch(BASE_URL + "/api/roles/roles_list_api.php");
            const rolesData = await rolesRes.json();
            if (rolesData.success) {
                const rolesSelect = $("#assignRolesSelect");
                rolesSelect.html(`<option value="">-- Select Role --</option>`);
                rolesData.roles.forEach(role => {
                    rolesSelect.append(`<option value="${role.id}">${role.name}</option>`);
                });
            }

            // Projects
            const projRes = await fetch(BASE_URL + "/api/projects/projects_list_api.php");
            const projData = await projRes.json();
            if (projData.success) {
                const projSelect = $("#assignProjectsSelect");
                projSelect.html(`<option value="">-- Select Project --</option>`);
                projData.items.forEach(p => {
                    projSelect.append(`<option value="${p.id}">${p.name}</option>`);
                });
            }

            // Clusters
            const clustRes = await fetch(BASE_URL + "/api/clusters/clusters_list_api.php");
            const clustData = await clustRes.json();
            if (clustData.success) {
                const clustSelect = $("#assignClustersSelect");
                clustSelect.html(`<option value="">-- Select Cluster --</option>`);
                clustData.items.forEach(c => {
                    clustSelect.append(`<option value="${c.id}">${c.name}</option>`);
                });
            }
        } catch (err) {
            console.error("Failed to load lists:", err);
        }
    }
});
