<?php
/**
 * Avatar Upload API
 * Handles user profile photo uploads
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['avatar'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed']);
    exit;
}

// Validate file size
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit;
}

try {
    // Create uploads directory if it doesn't exist
    $uploadDir = ROOT_PATH . 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $dbPath = 'uploads/avatars/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Resize image to 300x300
    resizeImage($filepath, 300, 300);

    // Delete old avatar
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldAvatar = $stmt->fetchColumn();
    if ($oldAvatar && file_exists(ROOT_PATH . $oldAvatar)) {
        unlink(ROOT_PATH . $oldAvatar);
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$dbPath, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Avatar updated successfully',
        'avatar_url' => BASE_URL . '/' . $dbPath
    ]);

} catch (Exception $e) {
    error_log("Avatar upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()]);
}

/**
 * Resize and crop image to square
 */
function resizeImage($filepath, $width, $height) {
    list($origWidth, $origHeight, $type) = getimagesize($filepath);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return false;
    }

    // Calculate crop dimensions
    $size = min($origWidth, $origHeight);
    $x = ($origWidth - $size) / 2;
    $y = ($origHeight - $size) / 2;

    // Create new image
    $dest = imagecreatetruecolor($width, $height);

    // Preserve transparency for PNG/GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
    }

    // Crop and resize
    imagecopyresampled($dest, $source, 0, 0, $x, $y, $width, $height, $size, $size);

    // Save based on original type
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dest, $filepath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($dest, $filepath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($dest, $filepath);
            break;
    }

    imagedestroy($source);
    imagedestroy($dest);

    return true;
}
