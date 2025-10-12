<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');

$userId = $_SESSION['user_id'] ?? null;
$permissions = $_SESSION['permissions'] ?? [];
$roleId = $_SESSION['role_id'] ?? null;

if (!$userId || (!in_array('view_report', $permissions) && $roleId != 1)) {
    http_response_code(403);
    echo "<p style='color:red;'>Access Denied.</p>";
    exit;
}

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$view = $_GET['view'] ?? '';

// ✅ Allowed views for security (same as in download_report)
$allowedViews = [
    'vw_merged_field_lab_data' => 'Merged - field and laboratory',
    'field_collector' => 'Field Data',
    'lab_sorter' => 'Laboratory Data',
];

try {
    if ($type === 'generated') {
        if (!array_key_exists($view, $allowedViews)) {
            echo "<p style='color:red;'>Invalid view selection.</p>";
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM $view WHERE round = :round");
        $stmt->execute([':round' => $id]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$reportData) {
            echo "<p>No data/Views found for the selected report. Please use download all in excel format..!</p>";
            exit;
        }

        echo "<h3>Generated Report (View: " . htmlspecialchars($allowedViews[$view]) . ")</h3>";
        echo "<div style='overflow-x:auto; max-width:100%;'>";
        echo "<table class='modal-table'>";

        // Get keys (column names)
        $allKeys = array_keys($reportData[0]);
        $firstFive = array_slice($allKeys, 0, 5);
        $lastTwo = array_slice($allKeys, -2);
        $displayKeys = array_merge($firstFive, $lastTwo);

        echo "<thead><tr>";
        foreach ($displayKeys as $col) {
            echo "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $col))) . "</th>";
        }
        echo "</tr></thead><tbody>";

        foreach ($reportData as $row) {
            echo "<tr>";
            foreach ($displayKeys as $key) {
                echo "<td>" . htmlspecialchars($row[$key]) . "</td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table></div>";

    } elseif ($type === 'uploaded') {
        $stmt = $pdo->prepare("SELECT 
        ur.*, 
        CONCAT(u.fname, ' ', u.lname) AS uploaded_by 
        FROM uploaded_reports ur
        LEFT JOIN users u ON u.id = ur.uploaded_by
        WHERE ur.id = :id");
        $stmt->execute([':id' => $id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            echo "<p>Uploaded report not found.</p>";
            exit;
        }

        echo "<h3>Uploaded Report Details</h3>";
        echo "<ul>";
        echo "<li><strong>File Name:</strong> " . htmlspecialchars($report['file_name']) . "</li>";
        echo "<li><strong>Type:</strong> " . htmlspecialchars($report['report_type']) . "</li>";
        echo "<li><strong>Round:</strong> " . htmlspecialchars($report['round']) . "</li>";
        echo "<li><strong>Cluster:</strong> " . htmlspecialchars($report['cluster_name']) . "</li>";
        echo "<li><strong>Uploaded By:</strong> " . htmlspecialchars($report['uploaded_by'] ?? 'System') . "</li>";
        echo "<li><strong>Uploaded At:</strong> " . htmlspecialchars($report['uploaded_at']) . "</li>";
        echo "</ul>";

        echo "<p><a href='/ISCA-eInfoSys_v02/" . htmlspecialchars($report['file_path']) . "' target='_blank'>Download to Open File</a></p>";
    } else {
        echo "<p style='color:red;'> Invalid report type</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error loading report: " . htmlspecialchars($e->getMessage()) . "</p>";
}
