<?php
/**
 * Get Report Statistics API
 * Returns summary statistics for the report header
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

try {
    // Get total records (sum of all mosquitoes M+F) - match dashboard calculation
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(
            COALESCE(male_ag,0) + COALESCE(female_ag,0) +
            COALESCE(male_af,0) + COALESCE(female_af,0) +
            COALESCE(male_oan,0) + COALESCE(female_oan,0) +
            COALESCE(male_culex,0) + COALESCE(female_culex,0) +
            COALESCE(male_other_culex,0) + COALESCE(female_other_culex,0) +
            COALESCE(male_aedes,0) + COALESCE(female_aedes,0)
        ), 0) as total
        FROM vw_merged_field_lab_data
    ");
    $totalRecords = $stmt->fetchColumn();

    // Get total uploaded reports
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM uploaded_reports");
    $totalReports = $stmt->fetchColumn();

    // Get latest round
    $stmt = $pdo->query("SELECT MAX(round) as latest FROM vw_merged_field_lab_data");
    $latestRound = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_records' => (int)$totalRecords,
            'total_reports' => (int)$totalReports,
            'latest_round' => (int)($latestRound ?? 0)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch statistics',
        'stats' => [
            'total_records' => 0,
            'total_reports' => 0,
            'latest_round' => 0
        ]
    ]);
}
