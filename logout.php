<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/config/config.php';

// Clear all session data and destroy session
$_SESSION = [];

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

// Redirect to login page
redirect('login.php');
