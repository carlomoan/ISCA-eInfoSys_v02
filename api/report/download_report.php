<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../helpers/permission_helper.php';

if (!checkPermission('view_report') || !checkPermission('download_report')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$type = $_GET['type'] ?? '';
$filetype = $_GET['filetype'] ?? '';
$id = $_GET['id'] ?? null;
$view = $_GET['view'] ?? null;
$export = $_GET['export'] ?? null;

if (!in_array($type, ['generated', 'uploaded']) || !in_array($filetype, ['excel', 'pdf'])) {
    http_response_code(400);
    echo "Invalid parameters.";
    exit;
}

function sendDownloadHeaders($filename, $mimeType) {
    header("Content-Description: File Transfer");
    header("Content-Type: $mimeType");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate");
    header("Pragma: public");
    exit;
}

if ($type === 'generated') {
    $allowedViews = [
        'vw_species_percentage_per_round',
        'vw_species_summary',
        'vw_field_lab_merged',
    ];

    if (!$view || !in_array($view, $allowedViews)) {
        http_response_code(400);
        echo "Invalid view.";
        exit;
    }

    // Prepare data export query
    if ($export === 'all') {
        $sql = "SELECT * FROM `$view` ORDER BY 1 ASC";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Export single id
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$view` LIMIT 1");
        $stmt->execute();
        $firstCol = $stmt->fetchColumn();
        if (!$firstCol) {
            http_response_code(500);
            echo "View structure invalid.";
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM `$view` WHERE `$firstCol` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            http_response_code(404);
            echo "Record not found.";
            exit;
        }
    }
} else {
    // Uploaded reports
    if ($export === 'all') {
        $stmt = $pdo->query("SELECT * FROM uploaded_reports ORDER BY uploaded_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM uploaded_reports WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            http_response_code(404);
            echo "Report not found.";
            exit;
        }
    }
}

// Generate and send file
// NOTE: For demonstration, generate CSV for Excel and simple PDF (text-based).
// For real app, use libraries like PhpSpreadsheet and TCPDF or Dompdf.

$filenameBase = ($type === 'generated' ? $view : 'uploaded_reports') . '_' . date('Ymd_His');
if ($export === 'all') $filenameBase .= '_all';
if ($filetype === 'excel') {
    // CSV export
    $filename = $filenameBase . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if (count($rows) > 0) {
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
} else {
    // PDF export - very simple plain text PDF (for real app use Dompdf etc)
    $filename = $filenameBase . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Generate very simple PDF text
    // For demo: just output plain text with PDF header (this is not a valid PDF, but placeholder)
    echo "%PDF-1.4\n%Demo PDF export\n";
    echo "Report: " . $filenameBase . "\n\n";

    foreach ($rows as $row) {
        foreach ($row as $key => $val) {
            echo strtoupper($key) . ": " . $val . "\n";
        }
        echo "\n-----------------------\n\n";
    }
    echo "%%EOF";
    exit;
}
