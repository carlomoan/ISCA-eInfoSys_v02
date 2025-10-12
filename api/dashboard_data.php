<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/helpers/permission_helper.php');

header('Content-Type: application/json');

if (!checkPermission('view_dashboard')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    // Badilisha 'some_table' na 'created_at', 'category' kulingana na DB yako

    // Bar Chart: Records per month (last 6 months)
    $stmtBar = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
        FROMvw_merged_field_lab_data
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmtBar->execute();
    $barData = $stmtBar->fetchAll(PDO::FETCH_ASSOC);

    $barLabels = [];
    $barValues = [];
    foreach ($barData as $row) {
        $barLabels[] = $row['month'];
        $barValues[] = (int)$row['total'];
    }

    // Pie Chart: Count by category
    $stmtPie = $pdo->prepare("
        SELECT SUM(male_ag)+SUM(female_ag), AS Anopheles Gambiae Total
        FROM vw_merged_field_lab_data
        GROUP BY round
    ");
    $stmtPie->execute();
    $pieData = $stmtPie->fetchAll(PDO::FETCH_ASSOC);

    $pieLabels = [];
    $pieValues = [];
    foreach ($pieData as $row) {
        $pieLabels[] = $row['c'];
        $pieValues[] = (int)$row['total'];
    }

    // Line Chart: Daily counts for last 30 days
    $stmtLine = $pdo->prepare("
        SELECT DATE(created_at) AS day, COUNT(*) AS total
        FROM some_table
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY day
        ORDER BY day ASC
    ");
    $stmtLine->execute();
    $lineData = $stmtLine->fetchAll(PDO::FETCH_ASSOC);

    $lineLabels = [];
    $lineValues = [];
    foreach ($lineData as $row) {
        $lineLabels[] = $row['day'];
        $lineValues[] = (int)$row['total'];
    }

    echo json_encode([
        'bar' => ['labels' => $barLabels, 'values' => $barValues],
        'pie' => ['labels' => $pieLabels, 'values' => $pieValues],
        'line' => ['labels' => $lineLabels, 'values' => $lineValues],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
