<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    $sessionConfig = Config::session();
    session_name($sessionConfig['name']);
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    // Fetch all merged data (same as verify ODK data for now)
    // This shows finalized merged field and lab data
    $stmt = $pdo->query("
        SELECT
            round,
            hhcode,
            hhname,
            clstid,
            clstname,
            field_recorder,
            lab_sorter,
            field_coll_date,
            lab_date,
            male_ag + female_ag + male_af + female_af AS total_mosquitoes,
            field_created_at,
            lab_created_at
        FROM vw_merged_field_lab_data
        WHERE lab_created_at IS NOT NULL
        ORDER BY field_created_at DESC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);

} catch (PDOException $e) {
    error_log("Append finalize data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load data for append and finalize: ' . $e->getMessage(),
        'data' => []
    ]);
}
