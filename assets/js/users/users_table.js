// ================= GLOBAL STATE =================
let usersData = [];
let currentUserId = null;

// Live Activity state
let liveActivityRows = new Set();
let liveActivityCount = 0;
const POLL_INTERVAL = 5000;

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', function() {
    fetchUsers();
    loadAssignmentLists();
    pollLiveActivity();
    
    // Event listeners
    document.addEventListener("click", e => {
        if (e.target.classList.contains("manageUserBtn")) {
            openManageUserModal(e.target.dataset.id);
        }
        
        if (e.target.id === "saveUserChanges" || e.target.closest('#saveUserChanges')) {
            saveUserChanges();
        }
        
        if (e.target.id === "closeManageUserModal" || e.target.closest('#closeManageUserModal')) {
            closeManageUserModal();
        }
        
        if (e.target.id === "liveActivityBell" || e.target.closest('#liveActivityBell')) {
            document.getElementById("liveActivityModal").classList.remove("hidden");
        }
    });
});

// ================= FETCH USERS =================
async function fetchUsers() {
    try {
        const response = await fetch(
            `${BASE_URL}/api/users/users_list_api.php`,
            {
                credentials: 'include' // Same as manage_permissions.js
            }
        );
        
        const data = await response.json();
        
        if (data.success) {
            usersData = data.users;
            renderUsersTable(data.users);
        } else {
            showToast(data.message || 'Failed to load users', 'error');
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        showToast('Error loading users', 'error');
    }
}

function renderUsersTable(data) {
    const tbody = document.getElementById("usersBody");
    if (!tbody) return;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 2rem;">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(u => {
        const fullName = `${u.fname} ${u.lname}`;
        const roleName = u.role_name || "No Role";
        const roleColor = roleName === "No Role" ? "#999" : "#333";
        const statusBadge = u.is_verified
            ? '<span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">✓ Verified</span>'
            : '<span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">✗ Pending</span>';
        const labBadge = u.lab_tech
            ? '<span style="background: #17a2b8; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">Lab Tech</span>'
            : '<span style="color: #999;">—</span>';
        const projectsText = u.projects && u.projects.length > 0
            ? u.projects.map(p => p.name).join(', ')
            : '<span style="color: #999;">No projects</span>';

        return `<tr id="user-row-${u.id}">
            <td style="font-weight: 600;">${u.id}</td>
            <td style="font-weight: 500;">${fullName}</td>
            <td>${u.email}</td>
            <td>${u.phone}</td>
            <td style="color: ${roleColor}; font-weight: 500;">${roleName}</td>
            <td style="font-size: 11px;">${projectsText}</td>
            <td>${statusBadge}</td>
            <td>${labBadge}</td>
            <td>
                <button class="manageUserBtn" data-id="${u.id}" type="button" style="background: #007bff; color: white; border: none; padding: 4px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                    <i class="fas fa-user-cog"></i> Manage
                </button>
            </td>
        </tr>`;
    }).join("");
}

// ================= LOAD ASSIGNMENT LISTS =================
async function loadAssignmentLists(user = null) {
    try {
        // ===== Roles =====
        // Use preloaded data to avoid AJAX session issues (like role_permissions.php does)
        console.log("📋 Loading roles from preloaded data...");
        const rolesData = window.PRELOADED_DATA?.roles || [];

        if (rolesData.length > 0) {
            const rolesSelect = document.getElementById("assignRolesSelect");
            if (rolesSelect) {
                rolesSelect.innerHTML = `<option value="">-- Select Role --</option>`;
                console.log("📋 Loading", rolesData.length, "roles into dropdown");

                rolesData.forEach((role, index) => {
                    const opt = document.createElement("option");
                    opt.value = role.id;
                    opt.textContent = role.name;
                    rolesSelect.appendChild(opt);
                    console.log(`📋 ${index + 1}. Added role: "${role.name}" (ID: ${role.id})`);
                });

                console.log("✅ Total options in dropdown:", rolesSelect.options.length);

                if(user && user.role_id) {
                    rolesSelect.value = user.role_id;
                    console.log("📋 Set current role to:", user.role_id);
                }
            }
        } else {
            console.error("❌ No preloaded roles data available!");
        }

        // ===== Projects =====
        console.log("📋 Loading projects from preloaded data...");
        const projectsData = window.PRELOADED_DATA?.projects || [];
        if (projectsData.length > 0) {
            const projectsSelect = document.getElementById("assignProjectsSelect");
            if (projectsSelect) {
                projectsSelect.innerHTML = `<option value="">-- Select Project --</option>`;
                projectsData.forEach(p => {
                    const opt = document.createElement("option");
                    opt.value = p.id;
                    opt.textContent = p.project_code ? `${p.project_code} - ${p.name}` : p.name;
                    projectsSelect.appendChild(opt);
                });
                console.log("✅ Loaded", projectsData.length, "projects");
                if(user && user.projects && user.projects.length > 0) {
                    projectsSelect.value = user.projects[0].id;
                }
            }
        }

        // ===== Clusters =====
        console.log("📋 Loading clusters from preloaded data...");
        const clustersData = window.PRELOADED_DATA?.clusters || [];
        if (clustersData.length > 0) {
            const clustersSelect = document.getElementById("assignClustersSelect");
            if (clustersSelect) {
                clustersSelect.innerHTML = `<option value="">-- Select Cluster --</option>`;
                clustersData.forEach(c => {
                    const opt = document.createElement("option");
                    opt.value = c.id;
                    opt.textContent = c.name;
                    clustersSelect.appendChild(opt);
                });
                console.log("✅ Loaded", clustersData.length, "clusters");
                if(user && user.clusters && user.clusters.length > 0) {
                    clustersSelect.value = user.clusters[0].id;
                }
            }
        }

        // ===== Lab Technician =====
        const labTechSelect = document.getElementById("assignLabTechsSelect");
        if (labTechSelect) {
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
        }

    } catch (err) {
        console.error("Error loading assignment lists:", err);
        showToast('Error loading assignment data', 'error');
    }
}

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
            fetchUsers();
        } else {
            showToast(data.message || 'Failed to update user', 'error');
        }
    } catch (error) {
        console.error("Error saving user:", error);
        showToast('Error saving user data', 'error');
    }
}

// ================= LIVE ACTIVITY =================
async function pollLiveActivity() {
    try {
        const response = await fetch(
            `${BASE_URL}/api/liveactivity/live_activity_api.php`,
            {
                credentials: 'include' // Same as manage_permissions.js
            }
        );
        
        const data = await response.json();
        
        if (data.success && data.activities) {
            const listEl = document.getElementById("liveActivityList");
            if (!listEl) return;
            
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
            const countEl = document.getElementById("liveActivityCount");
            if (countEl) {
                countEl.textContent = liveActivityCount;
            }
        }
    } catch (error) {
        console.error("Live activity error:", error);
    }
    setTimeout(pollLiveActivity, POLL_INTERVAL);
}

function closeLiveActivityModal() {
    document.getElementById("liveActivityModal").classList.add("hidden");
}

// ===== Use the same showToast function from manage_permissions.js =====
// If it's not available, use this fallback
if (typeof showToast === 'undefined') {
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}