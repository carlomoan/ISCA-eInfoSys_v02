<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

/*

$token = bin2hex(random_bytes(32));
$expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

$stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
$stmt->execute([$token, $expiry, $user['id']]);

// Simulate link (you'll later send via email)
$resetLink = "https://yourdomain.com/ISCA-eInfoSys_v02/reset_password.php?token=$token";
echo json_encode(['status' => 'success', 'message' => 'Reset link generated (simulated)', 'reset_url' => $resetLink]);
*/

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$csrf_token = $_POST['csrf_token'] ?? '';

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Always respond success to avoid email enumeration
    if (!$user) {
        echo json_encode(['success' => true, 'message' => 'If the email exists, a password reset link has been sent.']);
        exit;
    }

    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Insert or update token
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = CURRENT_TIMESTAMP");
    $stmt->execute([$email, $token, $expires_at]);

    // TODO: Send email with reset link: BASE_URL . 'reset_password.php?token=' . $token
    // For now, just return token in response for testing (remove in production)
    // echo json_encode(['success' => true, 'message' => 'Reset link sent.', 'token' => $token]);
    
    echo json_encode(['success' => true, 'message' => 'If the email exists, a password reset link has been sent.']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error, please try again later']);
    exit;
}
