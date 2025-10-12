<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/ISCA-eInfoSys_v02/config/db_connect.php');

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ 1. CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid CSRF token',
        'code' => 419
    ]);
    exit;
}

// ✅ 2. Basic validation
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and password are required',
        'code' => 400
    ]);
    exit;
}

// ✅ 3. Check User
$stmt = $pdo->prepare("
    SELECT u.id, u.password, u.is_verified, u.role_id, r.name AS role_name, u.is_admin
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {

    if (!(bool)$user['is_verified']) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Account not verified',
            'code' => 403
        ]);
        exit;
    }

    // ✅ 4. Set Basic Session
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['email']      = $email;
    $_SESSION['role_id']    = $user['role_id'];
    $_SESSION['role_name']  = $user['role_name'];
    $_SESSION['is_admin']   = (bool)$user['is_admin'];
    $_SESSION['is_verified']= (bool)$user['is_verified'];

    // ✅ 5. Load Permissions immediately (initial session)
    if (!$_SESSION['is_admin']) {
        $stmt = $pdo->prepare("
            SELECT p.name
            FROM role_permissions rp
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$user['role_id']]);
        $_SESSION['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN) ?? [];
    } else {
        $_SESSION['permissions'] = [];
    }

    $_SESSION['permissions_last_update'] = time(); // important for hybrid refresh

    // ✅ 6. Success Response
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'userId' => $user['id'],
        'role' => $user['role_name'],
        'permissions' => $_SESSION['permissions']
    ]);

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email or password',
        'code' => 401
    ]);
}
