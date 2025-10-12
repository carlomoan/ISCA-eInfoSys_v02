<?php
/**
 * Optimized Dashboard API with Caching
 * Uses SimpleCache to reduce database load
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'config/cache.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if (!checkPermission('view_dashboard')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    // Cache key based on user permissions and role
    $userId = $_SESSION['user_id'] ?? 'guest';
    $cacheKey = "dashboard_data_{$userId}";

    // Try to get from cache (5 minute TTL)
    $cachedData = SimpleCache::get($cacheKey);
    if ($cachedData !== null) {
        echo json_encode($cachedData);
        exit;
    }

    // If not cached, fetch from database
    $columns = $pdo->query("SHOW COLUMNS FROM vw_merged_field_lab_data")->fetchAll(PDO::FETCH_COLUMN);

    $speciesDefs = [
        'ag' => ['label' => 'An. gambiae'],
        'af' => ['label' => 'An. funestus'],
        'oan' => ['label' => 'Other Anopheles'],
        'culex' => ['label' => 'Culex'],
        'other_culex' => ['label' => 'Other Culex'],
        'aedes' => ['label' => 'Aedes']
    ];
    $feedingStates = ['fed', 'unfed', 'gravid', 'semi_gravid'];

    // --- OPTIMIZED SUMMARY WITH SINGLE QUERY ---
    $summaryQuery = "
        SELECT
            (SELECT COUNT(*) FROM clusters) AS total_clusters,
            COUNT(DISTINCT hhcode) AS total_households,
            COALESCE(MAX(`round`), 0) AS total_rounds
        FROM vw_merged_field_lab_data
    ";
    $summary = $pdo->query($summaryQuery)->fetch(PDO::FETCH_ASSOC);

    // Build total records calculation
    $sumPieces = [];
    foreach ($speciesDefs as $spKey => $sp) {
        foreach (['male', 'female'] as $mf) {
            $col = $mf . '_' . $spKey;
            if (in_array($col, $columns)) {
                $sumPieces[] = "COALESCE($col, 0)";
            }
        }
    }

    $total_records = 0;
    if (!empty($sumPieces)) {
        $total_records = (int)$pdo->query(
            "SELECT COALESCE(SUM(" . implode("+", $sumPieces) . "), 0) FROM vw_merged_field_lab_data"
        )->fetchColumn();
    }

    // Build female mosquitoes calculation
    $femaleCols = [];
    foreach ($speciesDefs as $spKey => $sp) {
        $col = 'female_' . $spKey;
        if (in_array($col, $columns)) {
            $femaleCols[] = "COALESCE($col, 0)";
        }
    }

    $total_mosquitoes = 0;
    if (!empty($femaleCols)) {
        $total_mosquitoes = (int)$pdo->query(
            "SELECT COALESCE(SUM(" . implode("+", $femaleCols) . "), 0) FROM vw_merged_field_lab_data"
        )->fetchColumn();
    }

    // --- HISTOGRAM ---
    $selectPieces = [];
    foreach ($speciesDefs as $spKey => $sp) {
        $selectPieces[] = in_array("female_$spKey", $columns)
            ? "SUM(COALESCE(female_$spKey, 0)) AS {$spKey}_female"
            : "0 AS {$spKey}_female";
        foreach ($feedingStates as $fs) {
            $selectPieces[] = in_array("{$fs}_$spKey", $columns)
                ? "SUM(COALESCE({$fs}_$spKey, 0)) AS {$spKey}_{$fs}"
                : "0 AS {$spKey}_{$fs}";
        }
    }
    $histRow = $pdo->query("SELECT " . implode(",", $selectPieces) . " FROM vw_merged_field_lab_data")
        ->fetch(PDO::FETCH_ASSOC);

    $histogram = [];
    foreach ($speciesDefs as $spKey => $sp) {
        $histogram[$sp['label']] = [
            'fed' => (int)($histRow[$spKey . '_fed'] ?? 0),
            'unfed' => (int)($histRow[$spKey . '_unfed'] ?? 0),
            'gravid' => (int)($histRow[$spKey . '_gravid'] ?? 0),
            'semi_gravid' => (int)($histRow[$spKey . '_semi_gravid'] ?? 0),
            'female_total' => (int)($histRow[$spKey . '_female'] ?? 0)
        ];
    }

    // --- SPECIES PER CLUSTER ---
    $clusterSelect = ["COALESCE(clstname, '(Unknown)') AS clstname"];
    foreach ($speciesDefs as $spKey => $sp) {
        $clusterSelect[] = in_array("female_$spKey", $columns)
            ? "SUM(COALESCE(female_$spKey, 0)) AS female_$spKey"
            : "0 AS female_$spKey";
    }
    $clusterRows = $pdo->query(
        "SELECT " . implode(",", $clusterSelect) .
        " FROM vw_merged_field_lab_data GROUP BY clstname ORDER BY clstname"
    )->fetchAll(PDO::FETCH_ASSOC);

    $clusterLabels = [];
    $clusterSeries = [];
    foreach ($speciesDefs as $spKey => $sp) {
        $clusterSeries[$sp['label']] = [];
    }

    foreach ($clusterRows as $r) {
        $clusterLabels[] = $r['clstname'];
        foreach ($speciesDefs as $spKey => $sp) {
            $clusterSeries[$sp['label']][] = (int)($r['female_' . $spKey] ?? 0);
        }
    }

    // Build response
    $response = [
        'success' => true,
        'summary' => [
            'total_clusters' => (int)$summary['total_clusters'],
            'total_households' => (int)$summary['total_households'],
            'total_rounds' => (int)$summary['total_rounds'],
            'total_records' => $total_records,
            'total_mosquitoes' => $total_mosquitoes
        ],
        'histogram' => $histogram,
        'cluster_chart' => [
            'labels' => $clusterLabels,
            'series' => $clusterSeries
        ]
    ];

    // Cache the response for 5 minutes (300 seconds)
    SimpleCache::set($cacheKey, $response, 300);

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch dashboard data',
        'message' => Config::isDebug() ? $e->getMessage() : 'Internal server error'
    ]);
}
