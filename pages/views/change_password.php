<?php
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/change_password.css" defer />

<div class="change-password-modal show" id="changePasswordModal">
    <div class="change-password-content">

        <!-- Close Button -->
         <button class="close-btn" onclick="goBack()">&times;</button>

        <!-- Title -->
        <h2>CHANGE PASSWORD</h2>

        <!-- Success/Error Messages -->
        <p id="change-password-msg"></p>

        <!-- Form -->
        <form id="change-password-form">
            <div class="input-wrapper">
                <input type="password" name="old_password" placeholder="Old Password *" required>
            </div>

            <div class="input-wrapper">
                <input type="password" id="new_password" name="new_password" placeholder="New Password *" required>
                <i class="fas fa-eye" onclick="togglePassword('new_password', this)"></i>
            </div>

            <div class="input-wrapper">
                <input type="password" name="confirm_password" placeholder="Confirm Password *" required>
            </div>

            <button type="submit" class="submit-btn">Change Password</button>
        </form>
    </div>
</div>



<script src="<?= BASE_URL ?>/assets/js/authjs/change_password.js" defer></script>
