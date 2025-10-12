<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

// Check POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get POST data safely
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

// Validate inputs
if (!$email || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Check CSRF token
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

try {
    // Prepare query
    $stmt = $pdo->prepare("SELECT id, fname, lname, email, password, role_id, is_verified, is_admin FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }

    // Verify password (assuming password hashed with password_hash)
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }

    // Optional: Check if user is verified
    if (!$user['is_verified']) {
        http_response_code(403);
        echo json_encode(['error' => 'Account not verified']);
        exit;
    }

    // All good, create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['fname'] = $user['fname'];
    $_SESSION['lname'] = $user['lname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['is_admin'] = $user['is_admin'];

    // Regenerate session ID for security
    session_regenerate_id(true);

    echo json_encode(['success' => true, 'message' => 'Login successful']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error, please try again later']);
    $e->getMessage();
    exit;
}
