<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/helpers/permission_helper.php');

header('Content-Type: application/json');

// Hakikisha user ana permission
if (!checkPermission('view_dashboard')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['userid'];

try {
    // Mfano: Card data
    $totalReports = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ?");
    $totalReports->execute([$userId]);
    $totalReportsCount = $totalReports->fetchColumn();

    $pendingReports = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'pending'");
    $pendingReports->execute([$userId]);
    $pendingReportsCount = $pendingReports->fetchColumn();

    $completedReports = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'completed'");
    $completedReports->execute([$userId]);
    $completedReportsCount = $completedReports->fetchColumn();

    // Mfano: Chart data (Bar)
    $stmt = $pdo->prepare("
        SELECT MONTH(created_at) as month, COUNT(*) as total
        FROM reports
        WHERE user_id = ?
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at)
    ");
    $stmt->execute([$userId]);
    $barData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mfano: Chart data (Pie)
    $stmtPie = $pdo->prepare("
        SELECT status, COUNT(*) as total
        FROM reports
        WHERE user_id = ?
        GROUP BY status
    ");
    $stmtPie->execute([$userId]);
    $pieData = $stmtPie->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'cards' => [
            'totalReports' => $totalReportsCount,
            'pendingReports' => $pendingReportsCount,
            'completedReports' => $completedReportsCount
        ],
        'charts' => [
            'bar' => $barData,
            'pie' => $pieData
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
