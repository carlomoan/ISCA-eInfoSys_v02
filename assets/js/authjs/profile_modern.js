/**
 * Modern Profile Page JavaScript
 * Handles profile updates, avatar upload, password changes, and activity log
 */

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.tab;

            // Update active tab button
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Update active tab content
            tabContents.forEach(content => content.classList.remove('active'));
            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }

            // Load activity log when tab is opened
            if (targetTab === 'activity') {
                loadActivityLog();
            }
        });
    });

    // Avatar upload
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');

    if (changeAvatarBtn && avatarInput) {
        changeAvatarBtn.addEventListener('click', () => {
            avatarInput.click();
        });

        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showToast('File too large. Maximum size is 5MB', 'error');
                    return;
                }

                // Validate file type
                if (!file.type.match(/image\/(jpeg|png|gif|webp)/)) {
                    showToast('Invalid file type. Only JPG, PNG, GIF, and WEBP allowed', 'error');
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (avatarPreview.tagName === 'IMG') {
                        avatarPreview.src = e.target.result;
                    } else {
                        // Replace placeholder with img
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile Photo';
                        img.id = 'avatarPreview';
                        img.className = 'avatar-image';
                        avatarPreview.parentNode.replaceChild(img, avatarPreview);
                    }
                };
                reader.readAsDataURL(file);

                // Upload avatar
                uploadAvatar(file);
            }
        });
    }

    // Profile form submission
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(profileForm);
            const data = {
                action: 'update',
                fname: formData.get('fname'),
                lname: formData.get('lname'),
                phone: formData.get('phone')
            };

            try {
                const response = await fetch(`${BASE_URL}/api/auth/profile_api.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Profile updated successfully!', 'success');
                    // Update the header name
                    const userName = document.querySelector('.user-name');
                    if (userName) {
                        userName.textContent = `${data.fname} ${data.lname}`;
                    }
                } else {
                    showToast(result.message || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Profile update error:', error);
                showToast('An error occurred while updating profile', 'error');
            }
        });
    }

    // Change password form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(changePasswordForm);
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            // Validate passwords match
            if (newPassword !== confirmPassword) {
                showToast('New passwords do not match', 'error');
                return;
            }

            // Validate password strength
            if (newPassword.length < 8) {
                showToast('Password must be at least 8 characters', 'error');
                return;
            }

            if (!/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
                showToast('Password must include a special character', 'error');
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/api/auth/change_password_api.php`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Password changed successfully! Redirecting to login...', 'success');
                    setTimeout(() => {
                        window.location.href = `${BASE_URL}/login.php`;
                    }, 2000);
                } else {
                    showToast(result.message || 'Failed to change password', 'error');
                }
            } catch (error) {
                console.error('Password change error:', error);
                showToast('An error occurred while changing password', 'error');
            }
        });
    }

    // Upload avatar function
    async function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);

        try {
            showToast('Uploading avatar...', 'info');

            const response = await fetch(`${BASE_URL}/api/auth/upload_avatar.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('Avatar updated successfully!', 'success');
            } else {
                showToast(result.message || 'Failed to upload avatar', 'error');
            }
        } catch (error) {
            console.error('Avatar upload error:', error);
            showToast('An error occurred while uploading avatar', 'error');
        }
    }

    // Load activity log
    async function loadActivityLog() {
        const activityLog = document.getElementById('activityLog');
        if (!activityLog) return;

        activityLog.innerHTML = '<div class="loading">Loading activity...</div>';

        try {
            const response = await fetch(`${BASE_URL}/api/auth/get_activity_log.php`);
            const result = await response.json();

            if (result.success && Array.isArray(result.activities)) {
                if (result.activities.length === 0) {
                    activityLog.innerHTML = '<div class="empty-state"><p>No recent activity</p></div>';
                } else {
                    activityLog.innerHTML = result.activities.map(activity => `
                        <div class="activity-item">
                            <div class="activity-icon ${activity.action}">
                                <i class="fas ${getActivityIcon(activity.action)}"></i>
                            </div>
                            <div class="activity-content">
                                <p class="activity-action">${formatActivityAction(activity.action)}</p>
                                <p class="activity-details">${activity.details || ''}</p>
                                <span class="activity-time">${formatTime(activity.created_at)}</span>
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                activityLog.innerHTML = '<div class="error-state"><p>Failed to load activity log</p></div>';
            }
        } catch (error) {
            console.error('Activity log error:', error);
            activityLog.innerHTML = '<div class="error-state"><p>Error loading activity log</p></div>';
        }
    }

    // Helper functions
    function getActivityIcon(action) {
        const icons = {
            'login': 'fa-sign-in-alt',
            'logout': 'fa-sign-out-alt',
            'profile_update': 'fa-user-edit',
            'password_change': 'fa-key',
            'avatar_upload': 'fa-camera',
            'create_user': 'fa-user-plus',
            'update_user': 'fa-user-edit',
            'delete_user': 'fa-user-minus'
        };
        return icons[action] || 'fa-circle';
    }

    function formatActivityAction(action) {
        const actions = {
            'login': 'Logged in',
            'logout': 'Logged out',
            'profile_update': 'Updated profile',
            'password_change': 'Changed password',
            'avatar_upload': 'Updated profile photo',
            'create_user': 'Created new user',
            'update_user': 'Updated user',
            'delete_user': 'Deleted user'
        };
        return actions[action] || action.replace(/_/g, ' ');
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (seconds < 60) return 'Just now';
        if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
        return date.toLocaleDateString();
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
        toast.textContent = message;
        toastContainer.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    }
});
