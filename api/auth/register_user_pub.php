<?php
// Public Registration Page - Standalone
require_once __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration | e-DataColls</title>
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/register_user.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/password_strength.css">
</head>
<body>

<div class="public-page-container">
    <div class="system-header">
         <img src="<?= BASE_URL ?>/assets/images/emblem-TZ.png" class="header-logo-left" alt="Left Logo">
         <div class="system-title">Data Collection and Survey System - eDataColls</div>
         <img src="<?= BASE_URL ?>/assets/images/NIMR-Logo-Up.png" class="header-logo-right" alt="Right Logo">
    </div>

    <h4 class="registration-header">User registration: Fill your details</h4>

    <div id="registration-wizard-container">
        <!-- Wizard Steps -->
        <div class="wizard-steps">
            <div class="step active" data-step="1">
                <span class="step-dot">1</span>
                <span class="step-label">Personal Info</span>
            </div>
            <div class="step" data-step="2">
                <span class="step-dot">2</span>
                <span class="step-label">Contact Info</span>
            </div>
            <div class="step" data-step="3">
                <span class="step-dot">3</span>
                <span class="step-label">Password</span>
            </div>
            <div class="step" data-step="4">
                <span class="step-dot">4</span>
                <span class="step-label">Confirm</span>
            </div>
        </div>

        <!-- Wizard Form -->
        <form id="publicRegisterWizardForm" autocomplete="off">
            <!-- Step 1 -->
            <div class="wizard-step active" data-step="1">
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="fname" required>
                </div>
                <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" name="lname" required>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="wizard-step" data-step="2">
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone <span class="required">*</span></label>
                    <input type="text" name="phone" value="+255" required>
                </div>
            </div>

<!-- Step 3 -->
<div class="wizard-step" data-step="3">
    <div class="form-group">
        <label>Password <span class="required">*</span></label>
        <input type="password" name="password" required>
        <!-- Password strength indicator -->
        <div class="password-strength-wrapper">
            <div class="password-strength-bar"></div>
        </div>
        <span class="password-strength-text">Min 8 chars, 1 special char</span>
    </div>

    <div class="form-group">
        <label>Confirm Password <span class="required">*</span></label>
        <input type="password" name="confirm_password" required>
    </div>
</div>


            <!-- Step 4 -->
            <div class="wizard-step" data-step="4">
                <h3>Confirm Your Details</h3>
                <div id="confirmationBody" class="confirmation-table"></div>
                <p class="text-muted">Your account will be pending admin approval.</p>
                <div class="actions">
                    <button type="button" id="backBtn" class="btn btn-secondary">← Back</button>
                    <button type="button" id="cancelBtn" class="btn btn-light">✖ Cancel</button>
                    <button type="button" id="submitBtn" class="btn btn-primary">✔ Register</button>
                </div>
            </div>

            <!-- Navigation -->
            <div class="actions wizard-nav">
                <button type="button" id="prevBtn" class="btn btn-secondary">Previous</button>
                <button type="button" id="nextBtn" class="btn btn-primary">Next</button>
            </div>

                <a href="<?= BASE_URL ?>/login.php" style="text-decoration:none; color:#007bff;">   ← Back to Login  </a>
            <!-- Honeypot -->
            <input type="text" name="website" style="display:none">
        </form>
    </div>
</div>

<!-- JS -->
<script src="<?= BASE_URL ?>/assets/js/authjs/register_pub.js" defer></script>
</body>
</html>
