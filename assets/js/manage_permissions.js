/**
 * ================================================================
 * Modern Permission Management JavaScript
 * ================================================================
 * Handles both role and user-level permission management
 * with project-scoping support
 * ================================================================
 */

// ===== Global State =====
let currentRoleId = null;
let currentRoleName = null;
let currentProjectId = 0; // 0 = global
let currentUserId = null;
let currentUserProjectId = 0;

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', function() {
    initMainTabs();
    initRoleTabs();
    initPermissionTabs();
    initProjectFilters();
    initUserSelector();
    initEventListeners();
    
    // Load first role by default
    const firstRoleTab = document.querySelector('.role-tab');
    if (firstRoleTab) {
        firstRoleTab.click();
    }
});

// ===== Main Tab Switching =====
function initMainTabs() {
    document.querySelectorAll('.main-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Update tab buttons
            document.querySelectorAll('.main-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`tab-${targetTab}`).classList.add('active');
        });
    });
}

// ===== Role Tab Management =====
function initRoleTabs() {
    document.querySelectorAll('.role-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            currentRoleId = parseInt(this.dataset.roleId);
            currentRoleName = this.dataset.roleName;
            
            // Update active tab
            document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Load permissions for this role
            loadRolePermissions();
        });
    });
}

// ===== Permission Tab Management (User Permissions) =====
function initPermissionTabs() {
    document.querySelectorAll('.perm-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Update tab buttons
            document.querySelectorAll('.perm-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.perm-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`perm-tab-${targetTab}`).classList.add('active');
        });
    });
}

// ===== Project Filter Management =====
function initProjectFilters() {
    // Role project filter
    const roleProjectFilter = document.getElementById('roleProjectFilter');
    if (roleProjectFilter) {
        roleProjectFilter.addEventListener('change', function() {
            currentProjectId = parseInt(this.value);
            updateProjectInfo('role');
            if (currentRoleId) {
                loadRolePermissions();
            }
        });
    }
    
    // User project filter
    const userProjectFilter = document.getElementById('userProjectFilter');
    if (userProjectFilter) {
        userProjectFilter.addEventListener('change', function() {
            currentUserProjectId = parseInt(this.value);
            if (currentUserId) {
                loadUserPermissions();
            }
        });
    }
}

function updateProjectInfo(type) {
    const projectId = type === 'role' ? currentProjectId : currentUserProjectId;
    const infoElement = document.getElementById(type === 'role' ? 'roleProjectInfo' : 'userProjectInfo');
    
    if (!infoElement) return;
    
    if (projectId === 0) {
        infoElement.textContent = 'Showing global permissions that apply to all projects';
    } else {
        const project = PROJECTS.find(p => p.project_id == projectId);
        if (project) {
            infoElement.textContent = `Showing permissions for: ${project.project_name}`;
        }
    }
}

// ===== User Selector =====
function initUserSelector() {
    const userSelector = document.getElementById('userSelector');
    if (userSelector) {
        userSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            currentUserId = parseInt(this.value);
            
            if (currentUserId) {
                // Update user info card
                document.getElementById('selectedUserName').textContent = this.options[this.selectedIndex].text.split('(')[0].trim();
                document.getElementById('selectedUserEmail').textContent = selectedOption.dataset.email;
                document.getElementById('selectedUserRole').textContent = selectedOption.dataset.role;
                
                document.getElementById('userInfoCard').style.display = 'block';
                
                // Load user permissions
                loadUserPermissions();
            } else {
                document.getElementById('userInfoCard').style.display = 'none';
            }
        });
    }
}

// ===== Event Listeners =====
function initEventListeners() {
    // Role permission buttons
    document.getElementById('roleSelectAllBtn')?.addEventListener('click', () => selectAllPermissions('role', true));
    document.getElementById('roleDeselectAllBtn')?.addEventListener('click', () => selectAllPermissions('role', false));
    document.getElementById('roleSavePermissionsBtn')?.addEventListener('click', saveRolePermissions);
    
    // Role permission search
    document.getElementById('rolePermissionSearch')?.addEventListener('input', function(e) {
        searchPermissions(e.target.value, 'role');
    });
    
    // Sync permissions button
    document.getElementById('syncPermissionsBtn')?.addEventListener('click', syncPermissions);
    
    // Audit log button
    document.getElementById('viewAuditLogBtn')?.addEventListener('click', openAuditLog);
    
    // Grant permission button
    document.getElementById('grantPermissionBtn')?.addEventListener('click', grantUserPermission);
}

// ===== Load Role Permissions =====
async function loadRolePermissions() {
    try {
        showLoading('role');
        
        const response = await fetch(
            `${BASE_URL}/api/permissions/manage_role_permissions.php?action=get_role_permissions&role_id=${currentRoleId}&project_id=${currentProjectId}`
        );
        
        const data = await response.json();
        
        if (data.success) {
            renderRolePermissions(data.permissions);
            updateRoleStats(data.count);
        } else {
            showToast(data.message || 'Failed to load permissions', 'error');
        }
    } catch (error) {
        console.error('Error loading role permissions:', error);
        showToast('Error loading permissions', 'error');
    } finally {
        hideLoading('role');
    }
}

function renderRolePermissions(assignedPermissions) {
    const container = document.getElementById('rolePermissionsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    // Get assigned permission IDs
    const assignedIds = new Set(assignedPermissions.map(p => p.id));
    
    // Render by category
    for (const [category, permissions] of Object.entries(PERMISSIONS_BY_CATEGORY)) {
        const categoryDiv = document.createElement('div');
        categoryDiv.className = 'permission-category';
        categoryDiv.dataset.category = category;
        
        categoryDiv.innerHTML = `
            <div class="category-header">
                <div class="category-icon">
                    <i class="fas fa-${getCategoryIcon(category)}"></i>
                </div>
                <div class="category-title">${category}</div>
                <div class="category-count">${permissions.length}</div>
            </div>
            <div class="permission-items"></div>
        `;
        
        const itemsContainer = categoryDiv.querySelector('.permission-items');
        
        permissions.forEach(perm => {
            const isChecked = assignedIds.has(perm.id);
            const itemDiv = document.createElement('div');
            itemDiv.className = `permission-item ${isChecked ? 'checked' : ''}`;
            itemDiv.dataset.permissionId = perm.id;
            
            itemDiv.innerHTML = `
                <input type="checkbox" 
                       class="perm-checkbox" 
                       value="${perm.id}" 
                       ${isChecked ? 'checked' : ''}
                       id="perm-${perm.id}">
                <label for="perm-${perm.id}" class="permission-label">
                    <div class="permission-name">${escapeHtml(perm.description || perm.name)}</div>
                    <div class="permission-desc">${escapeHtml(perm.name)}</div>
                </label>
            `;
            
            // Add click handler for the whole item
            itemDiv.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('checked', checkbox.checked);
                }
            });
            
            // Update checked state on checkbox change
            itemDiv.querySelector('input').addEventListener('change', function() {
                itemDiv.classList.toggle('checked', this.checked);
            });
            
            itemsContainer.appendChild(itemDiv);
        });
        
        container.appendChild(categoryDiv);
    }
    
    // Update role name
    document.getElementById('currentRoleName').textContent = currentRoleName;
}

function updateRoleStats(count) {
    const projectName = currentProjectId === 0 ? 'All Projects' : 
        (PROJECTS.find(p => p.project_id == currentProjectId)?.project_name || 'Unknown');
    
    document.getElementById('currentRoleStats').textContent = 
        `${count} permissions assigned for ${projectName}`;
}

// ===== Save Role Permissions =====
async function saveRolePermissions() {
    try {
        const checkedBoxes = document.querySelectorAll('#rolePermissionsContainer .perm-checkbox:checked');
        const permissionIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
        
        const button = document.getElementById('roleSavePermissionsBtn');
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner"></span> Saving...';
        
        const formData = new FormData();
        formData.append('action', 'save_role_permissions');
        formData.append('role_id', currentRoleId);
        formData.append('project_id', currentProjectId);
        formData.append('permission_ids', JSON.stringify(permissionIds));
        
        const response = await fetch(`${BASE_URL}/api/permissions/manage_role_permissions.php`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Saved ${data.count} permissions for ${currentRoleName}`, 'success');
            loadRolePermissions(); // Reload to show updated state
        } else {
            showToast(data.message || 'Failed to save permissions', 'error');
        }
    } catch (error) {
        console.error('Error saving permissions:', error);
        showToast('Error saving permissions', 'error');
    } finally {
        const button = document.getElementById('roleSavePermissionsBtn');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-save"></i> Save Permissions';
    }
}

// ===== Load User Permissions =====
async function loadUserPermissions() {
    try {
        const response = await fetch(
            `${BASE_URL}/api/permissions/manage_user_permissions.php?action=get_user_permissions&user_id=${currentUserId}&project_id=${currentUserProjectId}`
        );
        
        const data = await response.json();
        
        if (data.success) {
            renderUserEffectivePermissions(data.effective_permissions);
            renderUserDirectPermissions(data.direct_permissions);
        } else {
            showToast(data.message || 'Failed to load user permissions', 'error');
        }
    } catch (error) {
        console.error('Error loading user permissions:', error);
        showToast('Error loading user permissions', 'error');
    }
}

function renderUserEffectivePermissions(permissions) {
    const container = document.getElementById('effectivePermissionsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    document.getElementById('effectiveCount').textContent = permissions.length;
    
    if (permissions.length === 0) {
        container.innerHTML = '<p class="no-data">No permissions found</p>';
        return;
    }
    
    permissions.forEach(perm => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'permission-list-item';
        
        const projectBadge = perm.project_id == 0 ? 
            '<span class="badge badge-global">Global</span>' :
            `<span class="badge badge-project">${getProjectName(perm.project_id)}</span>`;
        
        const sourceBadge = perm.source === 'direct' ?
            '<span class="badge badge-direct">Direct</span>' :
            '<span class="badge badge-role">From Role</span>';
        
        itemDiv.innerHTML = `
            <div class="permission-list-info">
                <div class="permission-list-name">${escapeHtml(perm.description || perm.name)}</div>
                <div class="permission-list-meta">
                    ${sourceBadge}
                    ${projectBadge}
                    <span class="badge">${perm.category}</span>
                </div>
            </div>
        `;
        
        container.appendChild(itemDiv);
    });
}

function renderUserDirectPermissions(permissions) {
    const container = document.getElementById('directPermissionsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    document.getElementById('directCount').textContent = permissions.length;
    
    if (permissions.length === 0) {
        container.innerHTML = '<p class="no-data">No direct permissions assigned</p>';
        return;
    }
    
    permissions.forEach(perm => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'permission-list-item';
        
        const projectBadge = perm.project_id == 0 ? 
            '<span class="badge badge-global">Global</span>' :
            `<span class="badge badge-project">${getProjectName(perm.project_id)}</span>`;
        
        itemDiv.innerHTML = `
            <div class="permission-list-info">
                <div class="permission-list-name">${escapeHtml(perm.description || perm.name)}</div>
                <div class="permission-list-meta">
                    ${projectBadge}
                    <span class="badge">${perm.category}</span>
                    ${perm.notes ? `<br><small>${escapeHtml(perm.notes)}</small>` : ''}
                </div>
            </div>
            <div class="permission-list-actions">
                <button class="btn-danger btn-sm" onclick="revokeUserPermission(${perm.id}, ${perm.project_id})">
                    <i class="fas fa-times"></i> Revoke
                </button>
            </div>
        `;
        
        container.appendChild(itemDiv);
    });
}

// ===== Grant User Permission =====
async function grantUserPermission() {
    const permissionId = document.getElementById('grantPermissionSelect').value;
    const expiresAt = document.getElementById('grantExpiresAt').value;
    const notes = document.getElementById('grantNotes').value;
    
    if (!permissionId) {
        showToast('Please select a permission', 'warning');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'grant_permission');
        formData.append('user_id', currentUserId);
        formData.append('permission_id', permissionId);
        formData.append('project_id', currentUserProjectId);
        if (expiresAt) formData.append('expires_at', expiresAt);
        if (notes) formData.append('notes', notes);
        
        const response = await fetch(`${BASE_URL}/api/permissions/manage_user_permissions.php`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Permission granted successfully', 'success');
            
            // Reset form
            document.getElementById('grantPermissionSelect').value = '';
            document.getElementById('grantExpiresAt').value = '';
            document.getElementById('grantNotes').value = '';
            
            // Reload permissions
            loadUserPermissions();
            
            // Switch to direct permissions tab
            document.querySelector('[data-tab="direct"]').click();
        } else {
            showToast(data.message || 'Failed to grant permission', 'error');
        }
    } catch (error) {
        console.error('Error granting permission:', error);
        showToast('Error granting permission', 'error');
    }
}

// ===== Revoke User Permission =====
async function revokeUserPermission(permissionId, projectId) {
    if (!confirm('Are you sure you want to revoke this permission?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'revoke_permission');
        formData.append('user_id', currentUserId);
        formData.append('permission_id', permissionId);
        formData.append('project_id', projectId);
        
        const response = await fetch(`${BASE_URL}/api/permissions/manage_user_permissions.php`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Permission revoked successfully', 'success');
            loadUserPermissions();
        } else {
            showToast(data.message || 'Failed to revoke permission', 'error');
        }
    } catch (error) {
        console.error('Error revoking permission:', error);
        showToast('Error revoking permission', 'error');
    }
}

// ===== Sync Permissions =====
async function syncPermissions() {
    if (!confirm('Sync permissions from page files? This will auto-discover new pages.')) {
        return;
    }
    
    try {
        const button = document.getElementById('syncPermissionsBtn');
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner"></span> Syncing...';
        
        const response = await fetch(`${BASE_URL}/api/permissions/manage_role_permissions.php?action=sync_permissions`);
        const data = await response.json();
        
        if (data.success) {
            const results = data.results;
            const message = `Sync complete!\nAdded: ${results.added.length}\nExisting: ${results.existing.length}`;
            showToast(message, 'success');
            
            if (currentRoleId) {
                loadRolePermissions();
            }
        } else {
            showToast(data.message || 'Sync failed', 'error');
        }
    } catch (error) {
        console.error('Error syncing permissions:', error);
        showToast('Error syncing permissions', 'error');
    } finally {
        const button = document.getElementById('syncPermissionsBtn');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-sync-alt"></i> Sync Permissions';
    }
}

// ===== Helper Functions =====
function selectAllPermissions(type, checked) {
    const checkboxes = document.querySelectorAll(`#${type}PermissionsContainer .perm-checkbox`);
    checkboxes.forEach(cb => {
        cb.checked = checked;
        cb.closest('.permission-item').classList.toggle('checked', checked);
    });
}

function searchPermissions(query, type) {
    const categories = document.querySelectorAll(`#${type}PermissionsContainer .permission-category`);
    query = query.toLowerCase();
    
    categories.forEach(category => {
        const items = category.querySelectorAll('.permission-item');
        let visibleCount = 0;
        
        items.forEach(item => {
            const name = item.querySelector('.permission-name').textContent.toLowerCase();
            const desc = item.querySelector('.permission-desc').textContent.toLowerCase();
            const matches = name.includes(query) || desc.includes(query);
            
            item.style.display = matches ? 'flex' : 'none';
            if (matches) visibleCount++;
        });
        
        // Hide category if no items match
        category.style.display = visibleCount > 0 ? 'block' : 'none';
    });
}

function getCategoryIcon(category) {
    const icons = {
        'page': 'file-alt',
        'data': 'database',
        'admin': 'user-shield',
        'custom': 'cog',
        'other': 'ellipsis-h'
    };
    return icons[category] || 'shield-alt';
}

function getProjectName(projectId) {
    const project = PROJECTS.find(p => p.project_id == projectId);
    return project ? project.project_code : `Project #${projectId}`;
}

function showLoading(type) {
    const container = document.getElementById(`${type}PermissionsContainer`);
    if (container) {
        container.innerHTML = '<div style="text-align:center;padding:3rem;"><span class="loading-spinner" style="width:40px;height:40px;"></span></div>';
    }
}

function hideLoading(type) {
    // Loading is hidden when content is rendered
}

function openAuditLog() {
    // TODO: Implement audit log modal
    showToast('Audit log feature coming soon', 'info');
}

function closeAuditModal() {
    document.getElementById('auditLogModal').classList.remove('show');
}

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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add slideOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    .no-data {
        text-align: center;
        padding: 2rem;
        color: var(--secondary-color);
    }
`;
document.head.appendChild(style);
