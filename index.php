<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

// Check User Login
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Refresh User Info
$stmt = $pdo->prepare("
    SELECT u.email, u.fname, u.lname, u.role_id, r.name AS role_name,
           u.is_verified, u.is_admin
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Update Session
$_SESSION['email']       = $user['email'];
$_SESSION['fname']       = $user['fname'];
$_SESSION['lname']       = $user['lname'];
$_SESSION['role_id']     = $user['role_id'];
$_SESSION['role_name']   = $user['role_name'] ?? 'User';
$_SESSION['is_verified'] = (bool)$user['is_verified'];
$_SESSION['is_admin']    = (bool)$user['is_admin'];
$full_Name             = $user['fname'] .' '.  $_SESSION['lname'] ;
// Awaiting Approval Check
if (shouldAwaitApproval()) {
    $page = 'awaiting_approval';
} else {
    // Load Permissions if needed
    $isAdmin     = $_SESSION['is_admin'];
    $permissions = $_SESSION['permissions'] ?? [];

    if (empty($permissions) && !$isAdmin) {
        // Permissions loaded in getPermissions(), so call once here
        getPermissions();
        $permissions = $_SESSION['permissions'];
    }

    // Language
    $lang  = $_GET['lang'] ?? 'en';
    $langs = require ROOT_PATH . 'lang/lang.php';
    $texts = $langs[$lang] ?? $langs['en'];

    // Menu Items
    $stmt = $pdo->prepare("
        SELECT m.label, m.link, m.icon, p.name AS perm
        FROM menu m
        LEFT JOIN permissions p ON m.permission_id = p.id
        WHERE m.is_active = 1
        ORDER BY m.sort_order ASC
    ");
    $stmt->execute();
    $allMenuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $menuItems = filterMenuByPermission($allMenuItems);

    // Page Routing & Access Control
    $page = sanitizePage($_GET['page'] ?? getDefaultPage($menuItems));

    if (!$isAdmin) {
        $requiredPermission = 'view_' . $page;
        if (!checkPermission($requiredPermission)) {
            $page = 'access_denied';
        }
    }
}

// Variables for Sidebar & Header
$currentPage = $page;

// Hapa tunajenga majina kamili
$fullname    = $_SESSION['fname'] . ' ' . $_SESSION['lname'];
$roleName    = $_SESSION['role_name'];
$isVerified  = $_SESSION['is_verified'];

// Tuweke jina la mtumiaji kwenye session kwa matumizi mengine (mfano kwa collector)
$_SESSION['username'] = $fullname;

// Email ni rahisi kuitrack pia
$_SESSION['email'] = $_SESSION['email'] ?? null;

// Kama unataka kutumia user_id kama collector_code (kwa kuwa iko kwenye field_collector)
$_SESSION['collector_code'] = $_SESSION['user_id']; 



// ===== Page-Level Access Control =====
// Check if user has permission to view this page
$pagePermission = "view_" . $page;
$isAdmin = $_SESSION['is_admin'] ?? false;

// Pages that don't require specific permissions
$publicPages = ['dashboard', 'profile', 'change_password', 'awaiting_approval', 'access_denied', '404', 'under_construction'];

if (!in_array($page, $publicPages) && !$isAdmin) {
    // Check if user has permission to view this page
    if (!checkPermission($pagePermission)) {
        // User doesn't have permission, redirect to access denied
        $page = 'access_denied';
        $_SESSION['denied_page'] = $page;
        $_SESSION['required_permission'] = $pagePermission;
    }
}

// Include Layout
require_once ROOT_PATH . 'includes/header.php';

// Page Content is rendered here (inside .page-content div opened in header.php)
$viewPath = ROOT_PATH . 'pages/views/' . $page . '.php';
if (file_exists($viewPath)) {
    require $viewPath;
} else {
    require ROOT_PATH . 'pages/views/404.php';
}

require ROOT_PATH . 'includes/footer.php';
