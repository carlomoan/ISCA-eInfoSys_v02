/**
 * ================================================
 * Role-Permission Management JavaScript
 * ================================================
 *
 * Handles UI interactions for role-permission management
 * Maintains session state and handles AJAX requests
 */

document.addEventListener('DOMContentLoaded', function() {
    let currentRoleId = null;
    let currentRoleName = '';
    let allPermissions = PERMISSIONS_BY_CATEGORY || {};
    let currentPermissions = [];

    // Initialize
    init();

    function init() {
        // Set first role as active if available
        const firstTab = document.querySelector('.role-tab');
        if (firstTab) {
            const roleId = parseInt(firstTab.dataset.roleId);
            const roleName = firstTab.dataset.roleName;
            switchRole(roleId, roleName);
        }

        // Attach event listeners
        attachEventListeners();
    }

    function attachEventListeners() {
        // Role tab switching
        document.querySelectorAll('.role-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const roleId = parseInt(this.dataset.roleId);
                const roleName = this.dataset.roleName;

                // Remove active class from all tabs
                document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));

                // Add active class to clicked tab
                this.classList.add('active');

                // Load permissions for this role
                switchRole(roleId, roleName);
            });
        });

        // Select all button
        const selectAllBtn = document.getElementById('selectAllBtn');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', () => {
                document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(cb => {
                    cb.checked = true;
                });
                updateStats();
            });
        }

        // Deselect all button
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', () => {
                document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                updateStats();
            });
        }

        // Save permissions button
        const saveBtn = document.getElementById('savePermissionsBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', savePermissions);
        }

        // Sync permissions button
        const syncBtn = document.getElementById('syncPermissionsBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', syncPermissions);
        }

        // Search functionality
        const searchInput = document.getElementById('permissionSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterPermissions(this.value.toLowerCase());
            });
        }
    }

    function switchRole(roleId, roleName) {
        currentRoleId = roleId;
        currentRoleName = roleName;

        // Update header
        const roleNameEl = document.getElementById('currentRoleName');
        if (roleNameEl) {
            roleNameEl.innerHTML = `<i class="fas fa-user-tag"></i> ${roleName}`;
        }

        // Load permissions for this role
        loadRolePermissions(roleId);
    }

    function loadRolePermissions(roleId) {
        showLoading();

        fetch(`${BASE_URL}/api/permissions/manage_role_permissions.php?action=get_role_permissions&role_id=${roleId}`)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to load permissions');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    currentPermissions = data.permissions || [];
                    renderPermissions();
                    updateStats();
                } else {
                    showToast(data.message || 'Failed to load permissions', 'error');
                }
            })
            .catch(err => {
                console.error('Error loading permissions:', err);
                showToast('Error loading permissions: ' + err.message, 'error');
            })
            .finally(() => {
                hideLoading();
            });
    }

    function renderPermissions() {
        const container = document.getElementById('permissionsContainer');
        if (!container) return;

        const rolePermIds = currentPermissions.map(p => parseInt(p.id));

        let html = '';

        // Iterate through categories
        for (const [category, perms] of Object.entries(allPermissions)) {
            if (!perms || perms.length === 0) continue;

            html += `
                <div class="permission-category" data-category="${category}">
                    <div class="category-header">
                        <h4>
                            <i class="fas fa-${getCategoryIcon(category)}"></i>
                            ${formatCategoryName(category)}
                        </h4>
                        <span class="permission-count">${perms.length} permissions</span>
                    </div>
                    <div class="permission-list">
                        ${perms.map(p => `
                            <label class="permission-item" data-perm-name="${p.name.toLowerCase()}">
                                <input type="checkbox"
                                       class="perm-checkbox"
                                       value="${p.id}"
                                       ${rolePermIds.includes(parseInt(p.id)) ? 'checked' : ''}>
                                <div class="perm-info">
                                    <span class="perm-name">${p.name}</span>
                                    ${p.description ? `<span class="perm-desc">${p.description}</span>` : ''}
                                </div>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        if (html === '') {
            html = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>No permissions available</p></div>';
        }

        container.innerHTML = html;

        // Attach change listeners to checkboxes
        container.querySelectorAll('.perm-checkbox').forEach(cb => {
            cb.addEventListener('change', updateStats);
        });
    }

    function updateStats() {
        const total = document.querySelectorAll('.perm-checkbox').length;
        const checked = document.querySelectorAll('.perm-checkbox:checked').length;

        const statsEl = document.getElementById('currentRoleStats');
        if (statsEl) {
            statsEl.textContent = `${checked} of ${total} permissions selected`;
        }
    }

    function savePermissions() {
        if (!currentRoleId) {
            showToast('No role selected', 'error');
            return;
        }

        // Get all checked permission IDs
        const checkedIds = Array.from(document.querySelectorAll('.perm-checkbox:checked'))
                                .map(cb => parseInt(cb.value));

        const saveBtn = document.getElementById('savePermissionsBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const formData = new URLSearchParams();
        formData.append('action', 'save_role_permissions');
        formData.append('role_id', currentRoleId);
        formData.append('permission_ids', JSON.stringify(checkedIds));

        fetch(`${BASE_URL}/api/permissions/manage_role_permissions.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Failed to save permissions');
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                showToast(`Permissions saved successfully for ${currentRoleName}!`, 'success');
                // Reload permissions to reflect changes
                loadRolePermissions(currentRoleId);
            } else {
                showToast(data.message || 'Failed to save permissions', 'error');
            }
        })
        .catch(err => {
            console.error('Error saving permissions:', err);
            showToast('Error saving permissions: ' + err.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
    }

    function syncPermissions() {
        if (!confirm('This will scan all pages and create missing permissions. Continue?')) {
            return;
        }

        const syncBtn = document.getElementById('syncPermissionsBtn');
        const originalText = syncBtn.innerHTML;
        syncBtn.disabled = true;
        syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';

        fetch(`${BASE_URL}/api/permissions/manage_role_permissions.php?action=sync_permissions`)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to sync permissions');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const results = data.results;
                    const added = results.added.length;
                    const existing = results.existing.length;
                    const errors = results.errors.length;

                    let message = `Sync complete! Added: ${added}, Existing: ${existing}`;
                    if (errors > 0) {
                        message += `, Errors: ${errors}`;
                    }

                    showToast(message, errors > 0 ? 'warning' : 'success');

                    // Reload page to reflect new permissions
                    if (added > 0) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                } else {
                    showToast(data.message || 'Failed to sync permissions', 'error');
                }
            })
            .catch(err => {
                console.error('Error syncing permissions:', err);
                showToast('Error syncing permissions: ' + err.message, 'error');
            })
            .finally(() => {
                syncBtn.disabled = false;
                syncBtn.innerHTML = originalText;
            });
    }

    function filterPermissions(searchTerm) {
        const categories = document.querySelectorAll('.permission-category');

        categories.forEach(category => {
            const items = category.querySelectorAll('.permission-item');
            let visibleCount = 0;

            items.forEach(item => {
                const permName = item.dataset.permName || '';
                const isVisible = permName.includes(searchTerm);

                if (isVisible) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Hide category if no visible items
            if (visibleCount === 0) {
                category.style.display = 'none';
            } else {
                category.style.display = 'block';
            }
        });
    }

    function getCategoryIcon(category) {
        const icons = {
            'page': 'file-alt',
            'data': 'database',
            'admin': 'shield-alt',
            'custom': 'cog',
            'other': 'ellipsis-h'
        };
        return icons[category.toLowerCase()] || 'folder';
    }

    function formatCategoryName(category) {
        return category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, ' ');
    }

    function showLoading() {
        const container = document.getElementById('permissionsContainer');
        if (container) {
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading permissions...</div>';
        }
    }

    function hideLoading() {
        // Loading will be replaced by actual content
    }

    function showToast(message, type = 'info') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fas fa-${getToastIcon(type)}"></i> ${message}`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function getToastIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
});
