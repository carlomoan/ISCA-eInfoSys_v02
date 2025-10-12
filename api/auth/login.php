<?php
// Include config FIRST to ensure proper session initialization
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Extract and sanitize inputs
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = trim($input['password'] ?? '');
$csrf_token = $input['csrf_token'] ?? '';

// Validate CSRF token presence and correctness
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Validate email format
if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

// Validate password length
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters']);
    exit;
}

try {
    // Query user by email with role information
    $stmt = $pdo->prepare('
        SELECT u.*, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.email = ?
        LIMIT 1
    ');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }

    // Check if account is verified (optional - comment out if not needed)
    // if (!$user['is_verified']) {
    //     http_response_code(403);
    //     echo json_encode(['error' => 'Please verify your email address first']);
    //     exit;
    // }

    // Verify password hash
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['fname'] = $user['fname'];
    $_SESSION['lname'] = $user['lname'];
    $_SESSION['full_name'] = $user['fname'] . ' ' . $user['lname'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'] ?? 'User';
    $_SESSION['is_verified'] = (bool)$user['is_verified'];
    $_SESSION['is_admin'] = isset($user['is_admin']) ? (bool)$user['is_admin'] : false;

    // Initialize permissions for non-admin users
    if (!$_SESSION['is_admin']) {
        // Load permissions from database
        $permStmt = $pdo->prepare("
            SELECT p.name
            FROM role_permissions rp
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ");
        $permStmt->execute([$user['role_id']]);
        $_SESSION['permissions'] = $permStmt->fetchAll(PDO::FETCH_COLUMN) ?? [];
        $_SESSION['permissions_last_update'] = time();
    } else {
        // Admin has all permissions
        $_SESSION['permissions'] = array_values(getAllPermissions());
        $_SESSION['permissions_last_update'] = time();
    }

    // Optional: set a timestamp for login time
    $_SESSION['login_time'] = time();

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => BASE_URL . '/index.php?page=dashboard'
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);

    // Show detailed error only in development
    $errorMsg = defined('APP_ENV') && APP_ENV === 'development'
        ? 'Server error: ' . $e->getMessage()
        : 'Server error, please try again later.';

    echo json_encode(['error' => $errorMsg]);
    exit;
}
