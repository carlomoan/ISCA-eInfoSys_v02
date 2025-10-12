<?php
// ajax/export_data.php

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/helpers/permissions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

// Validate query params
$filetype = $_GET['filetype'] ?? 'excel';
$type = $_GET['type'] ?? 'all'; // expected 'field', 'lab', or 'all'

// Check permission for viewing/exporting data
if (!checkPermission('view_field_data') && !checkPermission('view_lab_data') && !$isAdmin) {
    http_response_code(403);
    exit('Access denied.');
}

// Require PhpSpreadsheet for Excel export (install via composer or include manually)
// For this example, I assume composer installed PhpSpreadsheet
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Export Data');

$rowNum = 1;

function writeHeaderRow($sheet, $headers, $startRow = 1) {
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $startRow, $header);
        $col++;
    }
}

// Fetch Field Data
$fieldData = [];
if ($type === 'field' || $type === 'all') {
    $sql = "SELECT * FROM field_collector";
    $stmt = $pdo->query($sql);
    $fieldData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Lab Data
$labData = [];
if ($type === 'lab' || $type === 'all') {
    $sql2 = "SELECT * FROM lab_sorter";
    $stmt2 = $pdo->query($sql2);
    $labData = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

// Write field data first if requested
if ($type === 'field' || $type === 'all') {
    $sheet->setCellValue('A' . $rowNum, "Field Data");
    $rowNum += 2;
    if (!empty($fieldData)) {
        // Write headers from first row keys
        writeHeaderRow($sheet, array_keys($fieldData[0]), $rowNum);
        $rowNum++;

        foreach ($fieldData as $dataRow) {
            $col = 'A';
            foreach ($dataRow as $value) {
                $sheet->setCellValue($col . $rowNum, $value);
                $col++;
            }
            $rowNum++;
        }
    } else {
        $sheet->setCellValue('A' . $rowNum, 'No field data found.');
        $rowNum++;
    }
}

// Add some spacing between tables if both exported
if ($type === 'all') $rowNum += 3;

// Write lab data next if requested
if ($type === 'lab' || $type === 'all') {
    $sheet->setCellValue('A' . $rowNum, "Lab Data");
    $rowNum += 2;
    if (!empty($labData)) {
        writeHeaderRow($sheet, array_keys($labData[0]), $rowNum);
        $rowNum++;

        foreach ($labData as $dataRow) {
            $col = 'A';
            foreach ($dataRow as $value) {
                $sheet->setCellValue($col . $rowNum, $value);
                $col++;
            }
            $rowNum++;
        }
    } else {
        $sheet->setCellValue('A' . $rowNum, 'No lab data found.');
        $rowNum++;
    }
}

// Output file to browser
$filename = "export_" . $type . "_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
