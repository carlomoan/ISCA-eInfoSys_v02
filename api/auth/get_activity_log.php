<?php
/**
 * Get Activity Log API
 * Returns user's recent activity
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'activities' => []]);
    exit;
}

try {
    // Get user's recent activity
    $stmt = $pdo->prepare("
        SELECT action, details, created_at
        FROM user_activity_log
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);

} catch (PDOException $e) {
    error_log("Activity log error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch activity log',
        'activities' => []
    ]);
}
