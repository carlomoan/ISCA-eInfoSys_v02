<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['field_file']) || $_FILES['field_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or error occurred.']);
    exit;
}

$tmpPath = $_FILES['field_file']['tmp_name'];
$spreadsheet = IOFactory::load($tmpPath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);

$header = array_shift($rows);
$required = ['hhcode', 'cluster_id', 'round'];

$headerMap = [];
foreach ($header as $key => $col) {
    $colLower = strtolower(trim($col));
    if (in_array($colLower, $required)) {
        $headerMap[$colLower] = $key;
    }
}

foreach ($required as $col) {
    if (!isset($headerMap[$col])) {
        echo json_encode(['success' => false, 'error' => "Missing required column: $col"]);
        exit;
    }
}

$hhcodes = [];
foreach ($rows as $row) {
    $hhcodes[] = $row[$headerMap['hhcode']];
}

$placeholders = implode(',', array_fill(0, count($hhcodes), '?'));
$sql = "
    SELECT h.hhcode, h.cluster_id, c.cluster_name, CONCAT(u.fname, ' ', u.lname) AS field_recorder
    FROM households h
    LEFT JOIN clusters c ON h.cluster_id = c.cluster_id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE h.hhcode IN ($placeholders)
";
$stmt = $pdo->prepare($sql);
$stmt->execute($hhcodes);
$expectedHouseholds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$expectedMap = [];
foreach ($expectedHouseholds as $hh) {
    $expectedMap[$hh['hhcode']] = $hh;
}

$feedback = [];

foreach ($rows as $row) {
    $hhcode = $row[$headerMap['hhcode']];
    $cluster_id = $row[$headerMap['cluster_id']];
    $round = $row[$headerMap['round']];

    if (isset($expectedMap[$hhcode])) {
        $info = $expectedMap[$hhcode];
        $feedback[] = [
            'hhcode' => $hhcode,
            'cluster_name' => $info['cluster_name'] ?? '–',
            'round' => $round,
            'field_recorder' => $info['field_recorder'] ?? '–',
            'status' => '✅ Found'
        ];
    } else {
        $feedback[] = [
            'hhcode' => $hhcode,
            'cluster_name' => '–',
            'round' => $round,
            'field_recorder' => '–',
            'status' => '❌ Not Found'
        ];
    }
}

echo json_encode(['success' => true, 'data' => $feedback]);
