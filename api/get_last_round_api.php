<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

// Check user session
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get last round from field_collector table (global, not cluster-specific)
    $stmt = $pdo->prepare("SELECT MAX(round) AS last_round FROM field_collector");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $lastRound = $result['last_round'] !== null ? (int)$result['last_round'] : 0;

    echo json_encode([
        'success' => true,
        'last_round' => $lastRound
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
