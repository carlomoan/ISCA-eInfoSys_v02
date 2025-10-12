<?php
header("Content-Type: application/json");
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');

$data = json_decode(file_get_contents("php://input"), true);
$token = trim($data['token'] ?? '');
$password = $data['password'] ?? '';
$confirm = $data['confirm'] ?? '';

if (!$token || !$password || !$confirm) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

// Find user with this token
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
        exit;
    }

    // Reset password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
    $stmt->execute([$hashed, $user['id']]);

    echo json_encode(['status' => 'success', 'message' => 'Password reset successful. You may now log in.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error.']);
}
