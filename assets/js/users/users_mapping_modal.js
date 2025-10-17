// ================= MODAL CONTROLS =================
async function openManageUserModal(userId) {
    currentUserId = userId;
    try {
        const response = await fetch(
            `${BASE_URL}/api/users/users_modal_api.php?user_id=${userId}`,
            {
                credentials: 'include' // Same as manage_permissions.js
            }
        );
        
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.message || 'Failed to load user data', 'error');
            return;
        }

        const user = data.user;

        // Load dropdowns with current assignments
        await loadAssignmentLists(user);

        // Basic info
        document.getElementById("modalUserName").textContent = user.fname + " " + user.lname;
        document.getElementById("modalUserId").textContent = user.id;
        document.getElementById("modalUserEmail").textContent = user.email;
        document.getElementById("modalUserPhone").textContent = user.phone;

        // Reset password
        document.getElementById("resetPassword").value = "";

        // Verified
        document.getElementById("currentVerify").textContent = user.is_verified ? "Verified" : "Unverified";
        document.getElementById("toggleVerify").value = user.is_verified ? "1" : "0";

        // Admin
        document.getElementById("currentAdmin").textContent = user.is_admin ? "Admin" : "Not Admin";
        document.getElementById("toggleAdmin").value = user.is_admin ? "1" : "0";

        // Current Roles
        updateMultiSelect("currentRolesList", user.roles || []);
        document.getElementById("currentRolesText").textContent = (user.roles && user.roles.length > 0)
            ? user.roles.map(r => r.name).join(", ")
            : "No roles assigned";

        // Current Projects
        updateMultiSelect("currentProjectsList", user.projects || []);
        document.getElementById("currentProjectsText").textContent = (user.projects && user.projects.length > 0)
            ? user.projects.map(p => `${p.project_code} - ${p.project_name}`).join(", ")
            : "No projects assigned";

        // Current Clusters
        updateMultiSelect("currentClustersList", user.clusters || []);
        document.getElementById("currentClustersText").textContent = (user.clusters && user.clusters.length > 0)
            ? user.clusters.map(c => c.cluster_name).join(", ")
            : "No clusters assigned";

        // Current Lab Technician
        updateMultiSelect("currentLabTechsList", user.lab_tech ? [user.lab_tech] : []);
        document.getElementById("currentLabTechsText").textContent = user.lab_tech ? "Lab Technician" : "Not Lab Technician";

        // Show modal
        document.getElementById("manageUserModal").classList.remove("hidden");

    } catch (error) {
        console.error("Error loading user modal:", error);
        showToast('Error loading user data', 'error');
    }
}

function closeManageUserModal() {
    document.getElementById("manageUserModal").classList.add("hidden");
}

function updateMultiSelect(selectId, values) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.innerHTML = "";
    values.forEach(val => {
        const opt = document.createElement("option");
        if (typeof val === "object" && val !== null) {
            opt.textContent = val.name || val.project_name || val.cluster_name;
            opt.value = val.id;
        } else {
            opt.textContent = val;
            opt.value = val;
        }
        select.appendChild(opt);
    });
}

// ================= SAVE CHANGES =================
async function saveUserChanges() {
    if (!currentUserId) return;

    // Lab action
    let labAction = null;
    const labSelectVal = document.getElementById("assignLabTechsSelect").value;
    if (labSelectVal === "assign") labAction = "assign";
    if (labSelectVal === "revoke") labAction = "revoke";

    const payload = {
        user_id: currentUserId,
        password: document.getElementById("resetPassword").value,
        is_verified: document.getElementById("toggleVerify").value,
        is_admin: document.getElementById("toggleAdmin").value,
        role_id: document.getElementById("assignRolesSelect").value,
        project_id: document.getElementById("assignProjectsSelect").value,
        cluster_id: document.getElementById("assignClustersSelect").value,
        lab_action: labAction
    };

    try {
        // Send as JSON (API expects JSON, not FormData)
        const response = await fetch(`${BASE_URL}/api/users/users_modal_update_api.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
            credentials: 'include' // Same as manage_permissions.js
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast("User updated successfully!", 'success');
            closeManageUserModal();
            if (typeof fetchUsers === 'function') {
                fetchUsers();
            }
        } else {
            showToast(data.message || 'Failed to update user', 'error');
        }
    } catch (error) {
        console.error("Error saving user:", error);
        showToast('Error saving user data', 'error');
    }
}

// ================= EVENT HANDLERS =================
document.addEventListener("click", e => {
    if (e.target.classList.contains("mapUserBtn")) {
        openManageUserModal(e.target.dataset.id);
    }
});

document.addEventListener("click", e => {
    if (e.target.id === "saveUserChanges") {
        saveUserChanges();
    }
    if (e.target.id === "closeManageUserModal") {
        closeManageUserModal();
    }
});