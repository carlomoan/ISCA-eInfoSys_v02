<?php
session_start();
header('Content-Type: application/json');

// Debug session info
echo json_encode([
    'session_data' => [
        'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
        'role_id' => $_SESSION['role_id'] ?? 'NOT SET',
        'is_admin' => $_SESSION['is_admin'] ?? 'NOT SET',
        'permissions' => $_SESSION['permissions'] ?? 'NOT SET',
        'is_verified' => $_SESSION['is_verified'] ?? 'NOT SET',
    ]
], JSON_PRETTY_PRINT);
