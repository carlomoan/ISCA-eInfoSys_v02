<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../helpers/permission_helper.php';

if (!checkPermission('view_report')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$view = $_GET['view'] ?? '';

if (empty($type) || empty($id)) {
    http_response_code(400);
    echo "Invalid parameters.";
    exit;
}

if ($type === 'generated') {
    // Map ya whitelist views
    $allowedViews = [
        'vw_species_percentage_per_round',
        'vw_species_summary',
        'vw_field_lab_merged',
    ];
    if (!in_array($view, $allowedViews)) {
        http_response_code(400);
        echo "Invalid view requested.";
        exit;
    }

    // id = primary key of first column, but we don't know column name exactly
    // Assume first column is id for WHERE clause

    try {
        // Get first column name dynamically from view
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$view` LIMIT 1");
        $stmt->execute();
        $firstCol = $stmt->fetchColumn();

        if (!$firstCol) {
            echo "View structure invalid.";
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM `$view` WHERE `$firstCol` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo "Record not found.";
            exit;
        }

        // Output a simple HTML table with the data
        echo "<h3>Details for Report ID: " . htmlspecialchars($id) . "</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%; font-family: Calibri, sans-serif; font-size: 11px;'>";
        foreach ($row as $col => $val) {
            echo "<tr><th style='text-align:left; background:#eee; width:30%;'>" . htmlspecialchars(ucwords(str_replace('_',' ', $col))) . "</th><td>" . htmlspecialchars($val) . "</td></tr>";
        }
        echo "</table>";

    } catch (Exception $e) {
        error_log("Error fetching generated report detail: " . $e->getMessage());
        echo "Error loading report details.";
    }
} elseif ($type === 'uploaded') {
    // Uploaded reports: id = uploaded_reports.id
    try {
        $stmt = $pdo->prepare("SELECT file_name, report_type, round, cluster_name, uploaded_at, file_path FROM uploaded_reports WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo "Report not found.";
            exit;
        }
        // Display details
        echo "<h3>Uploaded Report Details</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%; font-family: Calibri, sans-serif; font-size: 11px;'>";
        foreach ($row as $col => $val) {
            echo "<tr><th style='text-align:left; background:#eee; width:30%;'>" . htmlspecialchars(ucwords(str_replace('_',' ', $col))) . "</th><td>" . htmlspecialchars($val) . "</td></tr>";
        }
        echo "</table>";

    } catch (Exception $e) {
        error_log("Error fetching uploaded report detail: " . $e->getMessage());
        echo "Error loading report details.";
    }
} else {
    http_response_code(400);
    echo "Invalid report type.";
    exit;
}
