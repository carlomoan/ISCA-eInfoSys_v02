<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'].'/ISCA-eInfoSys_v02/config/db_connect.php');

$token = $_GET['token'] ?? '';

if (!$token) {
    die('Invalid verification token.');
}

try {
    // Tafuta user kwa token
    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        die('Invalid or expired verification token.');
    }

    if ($user['is_verified']) {
        echo 'Account already verified. You can <a href="index.php?page=login">login here</a>.';
        exit;
    }

    // Update kuwa verified na clear token
    $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
    $update->execute([$user['id']]);

    echo 'Account verified successfully. You can <a href="index.php?page=login">login now</a>.';

} catch (PDOException $e) {
    error_log("VERIFY ERROR: " . $e->getMessage());
    echo 'Server error. Please try again later.';
}
