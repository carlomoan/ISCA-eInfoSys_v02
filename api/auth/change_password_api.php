<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/db_connect.php';

$userId = $_SESSION['user_id'] ?? null;
if(!$userId){
    echo json_encode(['success'=>false,'message'=>'User not logged in.']);
    exit;
}

$oldPassword = trim($_POST['old_password'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

if(empty($oldPassword) || empty($newPassword) || empty($confirmPassword)){
    echo json_encode(['success'=>false,'message'=>'All fields are required.']);
    exit;
}

if($newPassword !== $confirmPassword){
    echo json_encode(['success'=>false,'message'=>'New password and confirmation do not match.']);
    exit;
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user || !password_verify($oldPassword, $user['password'])){
    echo json_encode(['success'=>false,'message'=>'Old password is incorrect.']);
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
$updateStmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");

if($updateStmt->execute([$hashedPassword, $userId])){
    // Optional: destroy session server-side immediately
     session_unset();
     session_destroy();

    echo json_encode(['success'=>true,'message'=>'Password changed successfully. You will be logged out.']);
} else {
    echo json_encode(['success'=>false,'message'=>'Failed to change password. Try again.']);
}
