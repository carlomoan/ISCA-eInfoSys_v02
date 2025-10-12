<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php?page=dashboard");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | e-DataColls</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- Global JavaScript Variables -->
  <script>
    const BASE_URL = '<?= BASE_URL ?>';
  </script>
</head>
<body>
<div class="login-container">
<div class="login-left">

  <div class="top-nav">
    <a href="survey/survey_home.php">Home</a>
    <a href="about_us.php">About Us</a>
    <a href="survey/survey.php">Survey</a>
  </div>

  <div class="slider-container">
    <img src="<?= asset('images/Survey_img_1_-Upd.png') ?>" alt="Slide 1" class="active">
    <img src="<?= asset('images/NIMR-Logo-Up.png') ?>" alt="Slide 2">
    <img src="<?= asset('images/emblem-TZ.png') ?>" alt="Slide 2">

    <div class="slider-nav">
      <button id="prev">&#10094;</button>
      <button id="next">&#10095;</button>
    </div>
  </div>

</div>

    <div class="login-right">
       <div class="brand-header">
         <img src="<?= asset('images/emblem-TZ.png') ?>" alt="Brand Logo 1">
        <div class="brand-title">
          <h4>Amani Medical Research Centre </h4>
          <h5> e-Data Collection and Survey Sytem</h5>
        </div>
        <img src="<?= asset('images/NIMR-Logo-Up.png') ?>" alt="Brand Logo 2">
      </div>
        <div class="login-box">
        <h4>Registered User Login</h4>
        <form id="login-form" method="POST" novalidate>
          <div class="form-group">
            <input type="email" name="email" id="email" placeholder=" " required />
            <label for="username">Username</label>
            <small class="error-message" id="email-error"></small>
          </div>
          <div class="form-group">
            <input type="password" name="password" id="password" placeholder=" " required minlength="6" />
            <label for="username">Password</label>
            <small class="error-message" id="password-error"></small>
            <div id="password-strength"></div>
          </div>
          <div class="form-group">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
          <button type="submit">Login</button>
          <div id="loader" class="spinner" style="display:none;"></div>
          <div id="login-error-message" class="error-message" style="display:none; color:red; font: size 12px;"></div>
        </form>

        <div class="links">
          <a href="<?= url('api/auth/forgot_password_pub.php') ?>">Forgot Password?</a> |
          <a href="<?= url('api/auth/register_user_pub.php') ?>">Register</a>
        </div>
      </div>
    </div>
  </div>



  <script>
    const BASE_URL = '<?= BASE_URL ?>';
  </script>
  <script src="<?= asset('js/global.js') ?>" defer></script>
  <script src="<?= asset('js/login.js') ?>" defer></script>
  <link rel="stylesheet" href="<?= asset('css/login_ex.css') ?>" />
  <script src="<?= asset('js/login_slide_img.js') ?>" defer></script>
</body>
</html>
