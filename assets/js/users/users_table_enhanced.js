// Enhanced rendering function with better badges and styling
function renderUsersTable(data) {
    const tbody = document.getElementById("usersBody");
    if (!tbody) return;

    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr class="empty-state">
                <td colspan="9">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p><strong>No Users Found</strong></p>
                        <p>There are no users to display</p>
                    </div>
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = data.map(u => {
        const fullName = `${u.fname} ${u.lname}`;
        const roleName = u.role_name || "No Role";

        // Enhanced status badge
        const statusBadge = u.is_verified
            ? '<span class="status-badge verified"><i class="fas fa-check-circle"></i> Verified</span>'
            : '<span class="status-badge pending"><i class="fas fa-clock"></i> Pending</span>';

        // Enhanced lab badge
        const labBadge = u.lab_tech || u.lab_tech_id
            ? '<span class="lab-badge"><i class="fas fa-flask"></i> Lab Tech</span>'
            : '<span style="color: #9ca3af;">—</span>';

        // Enhanced projects display
        const projectsText = u.projects && u.projects.length > 0
            ? u.projects.map(p => p.name).join(', ')
            : '<span style="color: #9ca3af;">No projects</span>';

        return `<tr id="user-row-${u.id}">
            <td>${u.id}</td>
            <td style="font-weight: 600; color: #111827;">${fullName}</td>
            <td style="color: #6b7280;">${u.email}</td>
            <td style="color: #6b7280;">${u.phone}</td>
            <td style="font-weight: 600; color: #00aced;">${roleName}</td>
            <td style="font-size: 13px; color: #6b7280;">${projectsText}</td>
            <td>${statusBadge}</td>
            <td>${labBadge}</td>
            <td>
                <button class="manageUserBtn" data-id="${u.id}" type="button">
                    <i class="fas fa-user-cog"></i> Manage
                </button>
            </td>
        </tr>`;
    }).join("");
}

// Add this to the existing users_table.js or replace the renderUsersTable function
