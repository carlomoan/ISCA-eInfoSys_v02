<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';
header('Content-Type: application/json');

// Decode JSON input
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// Honeypot bot check
if (!empty($input['website'])) {
    echo json_encode(["success" => false, "message" => "Bot detected"]);
    exit;
}

// Required fields check
$required = ['fname', 'lname', 'email', 'phone', 'password'];
foreach ($required as $f) {
    if (empty($input[$f])) {
        echo json_encode(["success" => false, "message" => "Missing: $f"]);
        exit;
    }
}

// Email validation
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit;
}

// Phone validation
if (!preg_match('/^\+255\d{9}$/', $input['phone'])) {
    echo json_encode(["success" => false, "message" => "Phone must start with +255 followed by 9 digits"]);
    exit;
}

// Password validation
$password = $input['password'];
if (strlen($password) < 8 || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters and include a special character"]);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute([':email' => strtolower($input['email'])]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Email already registered"]);
        exit;
    }

    // Insert new user
    $sql = "INSERT INTO users (fname, lname, email, phone, password, role_id, is_verified, user_project_id, created_at)
            VALUES (:fname, :lname, :email, :phone, :password, 0, 0, 0, NOW())";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':fname' => htmlspecialchars($input['fname']),
        ':lname' => htmlspecialchars($input['lname']),
        ':email' => strtolower($input['email']),
        ':phone' => htmlspecialchars($input['phone']),
        ':password' => password_hash($input['password'], PASSWORD_DEFAULT)
    ]);

    // Save name in session for "awaiting approval" page
    $_SESSION['fname'] = $input['fname'];
    $_SESSION['lname'] = $input['lname'];

    echo json_encode($ok 
        ? ["success" => true, "message" => "Registration successful! Await admin approval."]
        : ["success" => false, "message" => "Failed to register"]
    );

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB Error: " . $e->getMessage()]);
}
