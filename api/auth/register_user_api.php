<?php
/**
 * User Registration API
 * Handles admin-created user registration with role and project assignment
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

// Check if user has permission to add users
if (!checkPermission('add_user') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. You do not have permission to add users.'
    ]);
    exit;
}

// Decode JSON input
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// Required fields check
$required = ['fname', 'lname', 'email', 'phone', 'password', 'role_id', 'user_project_id'];
foreach ($required as $f) {
    if (!isset($input[$f]) || trim($input[$f]) === '') {
        echo json_encode(["success" => false, "message" => "Missing required field: $f"]);
        exit;
    }
}

// Email validation
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit;
}

// Password validation
$password = $input['password'];
if (strlen($password) < 8) {
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters"]);
    exit;
}

if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must include at least one special character"]);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute([':email' => strtolower(trim($input['email']))]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Email already registered"]);
        exit;
    }

    // Insert new user
    $sql = "INSERT INTO users (fname, lname, email, phone, password, role_id, is_verified, user_project_id, created_at, is_active)
            VALUES (:fname, :lname, :email, :phone, :password, :role_id, 1, :user_project_id, NOW(), 1)";

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':fname' => htmlspecialchars(trim($input['fname'])),
        ':lname' => htmlspecialchars(trim($input['lname'])),
        ':email' => strtolower(trim($input['email'])),
        ':phone' => htmlspecialchars(trim($input['phone'])),
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role_id' => intval($input['role_id']),
        ':user_project_id' => intval($input['user_project_id'])
    ]);

    if ($ok) {
        $newUserId = $pdo->lastInsertId();

        // Log activity
        try {
            $activityStmt = $pdo->prepare("
                INSERT INTO user_activity_log (user_id, action, details, created_at)
                VALUES (:user_id, 'create_user', :details, NOW())
            ");
            $activityStmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':details' => json_encode([
                    'new_user_id' => $newUserId,
                    'email' => $input['email'],
                    'role_id' => $input['role_id'],
                    'project_id' => $input['user_project_id']
                ])
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the registration
            error_log("Failed to log user creation activity: " . $e->getMessage());
        }

        echo json_encode([
            "success" => true,
            "message" => "User registered successfully!",
            "user_id" => $newUserId
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to register user"]);
    }

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
