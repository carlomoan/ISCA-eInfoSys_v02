<?php
session_start();

require_once '../../config/db_connect.php';
require_once '../../helpers/permission_helper.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

header('Content-Type: application/json');

if (!checkPermission('data_entry')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if (!isset($_FILES['field_file']) || $_FILES['field_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
    exit;
}

$tmpFilePath = $_FILES['field_file']['tmp_name'];
$fileName = $_FILES['field_file']['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Required columns (normalized lowercase)
$requiredColumns = [
    'start', 'end', 'deviceid', 'ento_fld_frm_title', 'field_coll_date',
    'fldrecname', 'clstname', 'clstid', 'clsttype_lst', 'round',
    'hhcode', 'hhname', 'ddrln', 'aninsln', 'ddltwrk', 'ddltwrk_gcomment',
    'lighttrapid', 'collectionbgid', 'user_id', 'instanceid'
];

try {
    // Load spreadsheet based on file extension
    if ($fileExt === 'csv') {
        $reader = new Csv();
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);
    } elseif (in_array($fileExt, ['xls', 'xlsx'])) {
        $reader = new Xlsx();
    } else {
        throw new Exception("Unsupported file type: .$fileExt. Only CSV, XLS, XLSX allowed.");
    }

    $spreadsheet = $reader->load($tmpFilePath);
    $worksheet = $spreadsheet->getActiveSheet();

    $rows = [];
    $headerRowClean = [];

    foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = trim((string)$cell->getValue());
        }

        if ($rowIndex == 1) {
            // Normalize headers (lowercase and last part after colon if exists)
            $headerRowClean = array_map(function ($header) {
                $parts = preg_split('/[:]/', strtolower(trim($header)));
                return end($parts);
            }, $cells);

            // Check all required columns exist
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $headerRowClean)) {
                    throw new Exception("Missing required column: $col");
                }
            }
            continue;
        }

        // Skip empty rows
        if (count(array_filter($cells)) === 0) continue;

        // Map row data by required columns only
        $rowData = [];
        foreach ($requiredColumns as $col) {
            $idx = array_search($col, $headerRowClean);
            $rowData[$col] = ($idx !== false && isset($cells[$idx])) ? $cells[$idx] : null;
        }

        // Basic validation for required data per row
        if (empty($rowData['fldrecname'])) {
            throw new Exception("Missing fldrecname on row $rowIndex");
        }
        if (empty($rowData['hhcode'])) {
            throw new Exception("Missing hhcode on row $rowIndex");
        }
        if (!is_numeric($rowData['round'])) {
            throw new Exception("Invalid round on row $rowIndex: " . $rowData['round']);
        }

        $rowData['round'] = (int)$rowData['round'];
        $rows[] = $rowData;
    }

    if (count($rows) === 0) {
        throw new Exception("No valid data rows found in file");
    }

    // Only one round allowed per upload
    $rounds = array_unique(array_column($rows, 'round'));
    if (count($rounds) !== 1) {
        throw new Exception("Multiple rounds found; only one round is allowed per upload");
    }
    $round = $rounds[0];

    // Fetch expected households + cluster + assigned field recorder full name
    $stmt = $pdo->prepare("
        SELECT 
          h.hhcode, 
          h.cluster_id, 
          c.cluster_name, 
          CONCAT(u.fname, ' ', u.lname) AS field_recorder
        FROM households h
        LEFT JOIN clusters c ON h.cluster_id = c.cluster_id
        LEFT JOIN users u ON c.user_id = u.id
    ");
    $stmt->execute();
    $expectedHouseholds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expectedHHCodes = array_column($expectedHouseholds, 'hhcode');
    $uploadedHHCodes = array_unique(array_column($rows, 'hhcode'));

    $uploadedHHCount = count($uploadedHHCodes);
    $expectedHHCount = count($expectedHHCodes);

    // Clear old temp data for this user
    $pdo->prepare("DELETE FROM temp_field_collector WHERE user_id = ?")->execute([$userId]);

    // Insert uploaded rows into temp table
    $insertStmt = $pdo->prepare("INSERT INTO temp_field_collector
        (`start`, `end`, `deviceid`, `ento_fld_frm_title`, `field_coll_date`, `fldrecname`,
         `clstname`, `clstid`, `clsttype_lst`, `round`, `hhcode`, `hhname`, `ddrln`, `aninsln`,
         `ddltwrk`, `ddltwrk_gcomment`, `lighttrapid`, `collectionbgid`,`user_id`,`instanceID`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($rows as $r) {
        $insertStmt->execute([
            $r['start'] ?: null,
            $r['end'] ?: null,
            $r['deviceid'] ?: null,
            $r['ento_fld_frm_title'] ?: null,
            $r['field_coll_date'] ?: null,
            $r['fldrecname'],
            $r['clstname'] ?: null,
            $r['clstid'] ?: null,
            $r['clsttype_lst'] ?: null,
            $r['round'],
            $r['hhcode'],
            $r['hhname'] ?: null,
            $r['ddrln'] ?: null,
            $r['aninsln'] ?: null,
            $r['ddltwrk'] ?: null,
            $r['ddltwrk_gcomment'] ?: null,
            is_numeric($r['lighttrapid']) ? (int)$r['lighttrapid'] : null,
            is_numeric($r['collectionbgid']) ? (int)$r['collectionbgid'] : null,
            $userId,
            $r['instanceid'] ?: null
        ]);
    }

    // Compute missing households (expected but not uploaded)
    $missingHHs = [];
    $uploadedHHSet = array_flip($uploadedHHCodes);
    foreach ($expectedHouseholds as $hh) {
        if (!isset($uploadedHHSet[$hh['hhcode']])) {
            $missingHHs[] = [
                'hhcode' => $hh['hhcode'],
                'cluster' => $hh['cluster_name'] ?: '-',
                'field_recorder' => $hh['field_recorder'] ?: '-',
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Field data uploaded success------fully. Uploaded $uploadedHHCount of $expectedHHCount expected households.",
        'preview' => array_slice($rows, 0, 60),
        'missing_households' => $missingHHs,
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}


