<?php
// =========================
// HEADER PRE-SETUP
// =========================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

// =========================
// Default & Safe Variables
// =========================
$currentTitle = $currentTitle ?? 'National Institute For Medical Research - NIMR';
$lang         = $lang ?? ($_GET['lang'] ?? 'en');

// Sanitize and get page for routing
$page         = sanitizePage($_GET['page'] ?? getDefaultPage($_SESSION['menu_items'] ?? []));
$email        = $_SESSION['email'] ?? 'User';
$roleName     = $_SESSION['role_name'] ?? 'User';
$role_id     = $_SESSION['role_id'] ?? 'User';
$texts        = $texts ?? [];

// Ensure BASE_URL is defined (should be defined in config.php)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    define('BASE_URL', $protocol . $host . $scriptPath);
}

// Escape all variables for safe HTML output
$email    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$roleName = htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8');
$page     = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
$lang     = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($currentTitle, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/icons/favicon.png" type="image/png">

    <!-- Optional: Apple touch icon for iOS -->
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon.png">

    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css" />

    <!-- FontAwesome (External) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- Global JavaScript Variables -->
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-left">     
        <button id="menu-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <img src="<?= BASE_URL ?>/assets/images/emblem-TZ.png" alt="Brand Image" class="brand-image" />
        <div class="page-title"><h3><?= htmlspecialchars($currentTitle, ENT_QUOTES, 'UTF-8') ?></h3></div>
    </div>

    <div class="topbar-right">
        <!-- Language Switch -->
        <form method="get" id="language-form">
            <select name="lang" onchange="this.form.submit()" aria-label="Select language">
                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                <option value="sw" <?= $lang === 'sw' ? 'selected' : '' ?>>Swahili</option>
            </select>
            <input type="hidden" name="page" value="<?= $page ?>" />
        </form>

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay"></div>

<!-- Dark mode toggle -->
<button id="dark-toggle"><i class="fas fa-moon"></i></button>
        
       
        
<!-- Profile Dropdown -->
<div class="profile-dropdown">
    <button id="profile-btn" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-user-circle"></i> <?= $email ?> <i class="fas fa-caret-down"></i>
    </button>
    <div class="dropdown-menu" id="profile" role="menu" aria-label="Profile menu">
        <a href="?page=profile&lang=<?= $lang ?>" role="menuitem">
            <?= htmlspecialchars($texts['manage_profile'] ?? 'Profile', ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a href="?page=change_password&lang=<?= $lang ?>" role="menuitem">
            <?= htmlspecialchars($texts['change_password'] ?? 'Change Password', ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a href="<?= BASE_URL ?>/logout.php" style="color:#f44336;" role="menuitem">
            <i class="fas fa-sign-out-alt"></i> <?= htmlspecialchars($texts['logout'] ?? 'Logout', ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
</div>

    </div>
</div>



  <script src="<?= BASE_URL ?>/assets/js/global.js" defer></script>
<!-- Container starts here                 <div class="container">     -->

