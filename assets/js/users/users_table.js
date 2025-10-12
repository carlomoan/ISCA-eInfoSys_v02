// ================= GLOBAL STATE =================
let usersData = [];
let currentUserId = null;

// Live Activity state
let liveActivityRows = new Set();
let liveActivityCount = 0;
const POLL_INTERVAL = 5000; // 5 sec

// ================= FETCH USERS =================
async function fetchUsers() {
    try {
        const res = await fetch(BASE_URL + "/api/users/users_list_api.php");
        const data = await res.json();
        if (data.success) {
            usersData = data.users;
            renderUsersTable(usersData);
        } else {
            console.error("Failed to fetch users:", data.message);
        }
    } catch (e) {
        console.error("Error fetching users:", e);
    }
}

function renderUsersTable(data) {
    const tbody = document.getElementById("usersBody");
    tbody.innerHTML = data.map(u => {
        return `<tr id="user-row-${u.id}">
            <td>${u.id}</td>
            <td>${u.fname}</td>
            <td>${u.lname}</td>
            <td>${u.email}</td>
            <td>${u.phone}</td>
            <td style="color: ${u.is_verified ? "green" : "red"}"> ${u.is_verified ? "Active" : "Not Active"} </td>
            <td>${u.role_name || "-"}</td>
            <td>${u.lab_tech ? "Lab Technician" : "Not Lab Technician"}</td>
            <td><button class="manageUserBtn" data-id="${u.id}" type="button">User Access</button></td>
            <td><button class="mapUserBtn" data-id="${u.id}" type="button">User Mapping</button></td>
        </tr>`;
    }).join("");
}

// ================= LOAD ASSIGNMENT LISTS =================
async function loadAssignmentLists(user = null) {
    try {
        // ===== Roles =====
        const rolesRes = await fetch(BASE_URL + "/api/roles/roles_list_api.php");
        const rolesData = await rolesRes.json();
        if (rolesData.success) {
            const rolesSelect = document.getElementById("assignRolesSelect");
            rolesSelect.innerHTML = `<option value="">-- Select Role --</option>`;
            rolesData.roles.forEach(role => {
                const opt = document.createElement("option");
                opt.value = role.id;
                opt.textContent = role.name;
                rolesSelect.appendChild(opt);
            });
            if(user && user.roles && user.roles.length > 0) rolesSelect.value = user.roles[0].id;
        }

        // ===== Projects =====
        const projectsRes = await fetch(BASE_URL + "/api/projects/projects_list_api.php");
        const projectsData = await projectsRes.json();
        if (projectsData.success) {
            const projectsSelect = document.getElementById("assignProjectsSelect");
            projectsSelect.innerHTML = `<option value="">-- Select Project --</option>`;
            projectsData.items.forEach(p => {
                const opt = document.createElement("option");
                opt.value = p.id;
                opt.textContent = `${p.project_code || ""} ${p.name}`.trim();
                projectsSelect.appendChild(opt);
            });
            if(user && user.projects && user.projects.length > 0) projectsSelect.value = user.projects[0].id;
        }

        // ===== Clusters =====
        const clustersRes = await fetch(BASE_URL + "/api/clusters/clusters_list_api.php");
        const clustersData = await clustersRes.json();
        if (clustersData.success) {
            const clustersSelect = document.getElementById("assignClustersSelect");
            clustersSelect.innerHTML = `<option value="">-- Select Cluster --</option>`;
            clustersData.items.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.id;
                opt.textContent = c.name;
                clustersSelect.appendChild(opt);
            });
            if(user && user.clusters && user.clusters.length > 0) clustersSelect.value = user.clusters[0].id;
        }

        // ===== Lab Technician =====
        const labTechSelect = document.getElementById("assignLabTechsSelect");
        labTechSelect.innerHTML = `
            <option value="">-- Select Option --</option>
            <option value="assign">Assign Lab Duty</option>
            <option value="revoke">Revoke Lab Duty</option>
        `;
        if (user && user.lab_tech) {
            labTechSelect.value = "revoke";
        } else {
            labTechSelect.value = "";
        }

    } catch (err) {
        console.error("Error loading assignment lists:", err);
    }
}

// ================= MODAL CONTROLS =================
async function openManageUserModal(userId) {
    currentUserId = userId;
    try {
        const res = await fetch(`${BASE_URL}/api/users/users_modal_api.php?user_id=${userId}`);
        const data = await res.json();
        if (!data.success) {
            alert("Error: " + data.message);
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

    } catch (err) {
        console.error("Error loading user modal:", err);
    }
}

function closeManageUserModal() {
    document.getElementById("manageUserModal").classList.add("hidden");
}

function updateMultiSelect(selectId, values) {
    const select = document.getElementById(selectId);
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
        const res = await fetch(BASE_URL + "/api/users/users_modal_update_api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            alert("User updated successfully!");
            closeManageUserModal();
            fetchUsers();
        } else {
            alert("Error: " + data.message);
        }
    } catch (err) {
        console.error("Error saving user:", err);
    }
}

// ================= EVENT HANDLERS =================
document.addEventListener("click", e => {
    if (e.target.classList.contains("manageUserBtn")) {
        openManageUserModal(e.target.dataset.id);
    }
});

document.getElementById("saveUserChanges").addEventListener("click", saveUserChanges);

// ================= LIVE ACTIVITY =================
async function pollLiveActivity() {
    try {
        const res = await fetch(BASE_URL + "/api/liveactivity/live_activity_api.php");
        const data = await res.json();
        if (data.success && data.activities) {
            const listEl = document.getElementById("liveActivityList");
            listEl.innerHTML = '';
            data.activities.forEach(act => {
                const key = act.table + '-' + act.row_id;
                if (!liveActivityRows.has(key)) {
                    liveActivityRows.add(key);
                    liveActivityCount++;
                }
                const li = document.createElement("li");
                li.textContent = `[${act.created_at}] ${act.user_name} added row ${act.row_id} in ${act.table}`;
                listEl.appendChild(li);
            });
            document.getElementById("liveActivityCount").textContent = liveActivityCount;
        }
    } catch (err) {
        console.error("Live activity error:", err);
    }
    setTimeout(pollLiveActivity, POLL_INTERVAL);
}

// Modal toggle for live activity
document.getElementById("liveActivityBell").addEventListener("click", () => {
    document.getElementById("liveActivityModal").classList.remove("hidden");
});
function closeLiveActivityModal() {
    document.getElementById("liveActivityModal").classList.add("hidden");
}

// ================= INITIAL LOAD =================
document.addEventListener("DOMContentLoaded", () => {
    fetchUsers();
    loadAssignmentLists();
    pollLiveActivity();
});
