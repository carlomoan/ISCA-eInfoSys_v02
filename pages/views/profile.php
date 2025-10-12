<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db_connect.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT u.*, r.name as role_name, p.project_name FROM users u LEFT JOIN roles r ON u.role_id = r.id LEFT JOIN projects p ON u.user_project_id = p.project_id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$roleName = $user['role_name'] ?? 'User';
$projectName = $user['project_name'] ?? 'Not Assigned';
$createdAt = $user['created_at'] ?? '';
$updatedAt = $user['updated_at'] ?? '';
$avatar = $user['avatar'] ?? null;
$avatarUrl = $avatar ? BASE_URL . '/' . $avatar : null;
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/profile_modern.css" />

<div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / <strong>My Profile</strong>
    </nav>

    <div class="profile-container">
        <!-- Profile Header Card -->
        <div class="profile-header-card">
            <div class="profile-cover"></div>
            <div class="profile-info">
                <div class="avatar-section">
                    <div class="avatar-wrapper">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile Photo" id="avatarPreview" class="avatar-image">
                        <?php else: ?>
                            <div class="avatar-placeholder" id="avatarPreview">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="avatar-edit-btn" id="changeAvatarBtn">
                            <i class="fas fa-camera"></i>
                        </button>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                    </div>
                </div>
                <div class="user-details">
                    <h2 class="user-name"><?= htmlspecialchars(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')) ?></h2>
                    <p class="user-role"><i class="fas fa-briefcase"></i> <?= htmlspecialchars($roleName) ?></p>
                    <p class="user-email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <p class="user-project"><i class="fas fa-folder"></i> <?= htmlspecialchars($projectName) ?></p>
                </div>
                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-label">Member Since</span>
                        <span class="stat-value"><?= date('M Y', strtotime($createdAt)) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Last Updated</span>
                        <span class="stat-value"><?= $updatedAt ? date('M d, Y', strtotime($updatedAt)) : 'Never' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="profile-tabs">
            <button class="tab-btn active" data-tab="info">
                <i class="fas fa-user"></i> Personal Info
            </button>
            <button class="tab-btn" data-tab="security">
                <i class="fas fa-lock"></i> Security
            </button>
            <button class="tab-btn" data-tab="activity">
                <i class="fas fa-history"></i> Activity Log
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-contents">
            <!-- Personal Info Tab -->
            <div class="tab-content active" id="tab-info">
                <div class="content-card">
                    <h3><i class="fas fa-edit"></i> Edit Personal Information</h3>
                    <form id="profileForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fname">First Name <span class="required">*</span></label>
                                <input type="text" id="fname" name="fname" required value="<?= htmlspecialchars($user['fname'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="lname">Last Name <span class="required">*</span></label>
                                <input type="text" id="lname" name="lname" required value="<?= htmlspecialchars($user['lname'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" readonly value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                <small class="form-hint">Email cannot be changed. Contact admin if needed.</small>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="location.reload()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-content" id="tab-security">
                <div class="content-card">
                    <h3><i class="fas fa-key"></i> Change Password</h3>
                    <form id="changePasswordForm">
                        <div class="form-group">
                            <label for="old_password">Current Password <span class="required">*</span></label>
                            <input type="password" id="old_password" name="old_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password" required>
                            <small class="form-hint">At least 8 characters with a special character</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-lock"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activity Log Tab -->
            <div class="tab-content" id="tab-activity">
                <div class="content-card">
                    <h3><i class="fas fa-list"></i> Recent Activity</h3>
                    <div class="activity-log" id="activityLog">
                        <div class="loading">Loading activity...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.CURRENT_USER = {
        id: <?= json_encode($userId) ?>,
        role: <?= json_encode($roleName) ?>,
        name: <?= json_encode(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')) ?>
    };
</script>

<script src="<?= BASE_URL ?>/assets/js/authjs/profile_modern.js" defer></script>
