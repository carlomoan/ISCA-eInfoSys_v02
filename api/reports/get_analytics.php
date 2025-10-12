<?php
/**
 * Get Analytics Data API
 * Returns chart data and metrics for analytics tab
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

try {
    // Get metrics - match dashboard calculation
    $stmt = $pdo->query("
        SELECT
            COALESCE(SUM(
                COALESCE(male_ag,0) + COALESCE(female_ag,0) +
                COALESCE(male_af,0) + COALESCE(female_af,0) +
                COALESCE(male_oan,0) + COALESCE(female_oan,0) +
                COALESCE(male_culex,0) + COALESCE(female_culex,0) +
                COALESCE(male_other_culex,0) + COALESCE(female_other_culex,0) +
                COALESCE(male_aedes,0) + COALESCE(female_aedes,0)
            ), 0) as total_records,
            COALESCE(SUM(
                COALESCE(female_ag,0) + COALESCE(female_af,0) +
                COALESCE(female_oan,0) + COALESCE(female_culex,0) +
                COALESCE(female_other_culex,0) + COALESCE(female_aedes,0)
            ), 0) as total_mosquitoes,
            COUNT(DISTINCT hhcode) as total_households,
            COUNT(DISTINCT clstid) as total_clusters,
            COALESCE(MAX(round), 0) as total_rounds
        FROM vw_merged_field_lab_data
    ");
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

    // Records by round
    $stmt = $pdo->query("
        SELECT round, COUNT(*) as count
        FROM vw_merged_field_lab_data
        GROUP BY round
        ORDER BY round
    ");
    $byRound = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Species distribution
    $stmt = $pdo->query("
        SELECT
            'An. gambiae' as species,
            SUM(female_ag) as count
        FROM vw_merged_field_lab_data
        UNION ALL
        SELECT
            'An. funestus' as species,
            SUM(female_af) as count
        FROM vw_merged_field_lab_data
        UNION ALL
        SELECT
            'Other Anopheles' as species,
            SUM(female_oan) as count
        FROM vw_merged_field_lab_data
        UNION ALL
        SELECT
            'Culex' as species,
            SUM(female_culex) as count
        FROM vw_merged_field_lab_data
    ");
    $bySpecies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Timeline (last 30 days of collections)
    $stmt = $pdo->query("
        SELECT
            DATE(field_coll_date) as date,
            COUNT(*) as count
        FROM vw_merged_field_lab_data
        WHERE field_coll_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(field_coll_date)
        ORDER BY date
    ");
    $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'metrics' => [
            'total_records' => (int)($metrics['total_records'] ?? 0),
            'total_mosquitoes' => (int)($metrics['total_mosquitoes'] ?? 0),
            'total_households' => (int)($metrics['total_households'] ?? 0),
            'total_clusters' => (int)($metrics['total_clusters'] ?? 0),
            'total_rounds' => (int)($metrics['total_rounds'] ?? 0)
        ],
        'charts' => [
            'by_round' => $byRound,
            'by_species' => $bySpecies,
            'timeline' => $timeline
        ]
    ]);

} catch (PDOException $e) {
    error_log("Analytics error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch analytics data',
        'metrics' => [],
        'charts' => []
    ]);
}
