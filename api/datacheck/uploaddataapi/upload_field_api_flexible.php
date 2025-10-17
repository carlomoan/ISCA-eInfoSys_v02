<?php
/**
 * Flexible Field Data Upload API
 *
 * Supports multiple ODK form versions with intelligent column mapping
 * Provides detailed error messages showing found vs expected columns
 */

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'vendor/autoload.php';

if (session_status() == PHP_SESSION_NONE) {
    $sessionConfig = Config::session();
    session_name($sessionConfig['name']);
    session_start();
}

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

// Check file upload
if (!isset($_FILES['field_file']) || $_FILES['field_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
    exit;
}

$tmpFilePath = $_FILES['field_file']['tmp_name'];
$fileName = $_FILES['field_file']['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Flexible column mapping - supports multiple ODK form versions
$columnMapping = [
    // Standard column names (direct mapping)
    'start' => 'start',
    'end' => 'end',
    'deviceid' => 'deviceid',
    'ento_fld_frm_title' => 'ento_fld_frm_title',
    'field_coll_date' => 'field_coll_date',
    'round' => 'round',
    'hhcode' => 'hhcode',
    'hhname' => 'hhname',
    'clstid' => 'clstid',
    'clstname' => 'clstname',
    'fldrecname' => 'fldrecname',
    'clsttype_lst' => 'clsttype_lst',

    // ODK group format (ISCA 2025 format)
    'PREDTLS_GRP:fldrecname' => 'fldrecname',
    'PREDTLS_GRP:clstname' => 'clstname',
    'PREDTLS_GRP:clstid' => 'clstid',
    'PREDTLS-CLUSTER_TYPE_GRP:clsttype_lst' => 'clsttype_lst',
    'PREDTLS-CLUSTER_TYPE_GRP:round' => 'round',
    'PREDTLS-CLUSTER_TYPE_GRP:hhcode' => 'hhcode',
    'PREDTLS-CLUSTER_TYPE_GRP:hhname' => 'hhname',

    // Additional group formats (for backward compatibility)
    'PREDTLS_GRP:field_coll_date' => 'field_coll_date',
    'PREDTLS-CLUSTER_TYPE_GRP:field_coll_date' => 'field_coll_date',

    // Field-specific data
    'INFOQNSLAST-2_GRP:ddrln' => 'ddrln',
    'INFOQNSLAST-2_GRP:aninsln' => 'aninsln',
    'INFOQNSLAST-2_GRP:ddltwrk' => 'ddltwrk',
    'INFOQNSLAST-2_GRP:ddltwrk_gcomment' => 'ddltwrk_gcomment',
    'INFOQNSLAST-2_GRP:lighttrapid' => 'lighttrapid',
    'INFOQNSLAST-2_GRP:collectionbgid' => 'collectionbgid',

    // Metadata
    'meta:instanceID' => 'instanceID',
    'instanceID' => 'instanceID'
];

// Core required columns (minimum needed for processing)
$coreRequiredColumns = ['round', 'hhcode'];

// Strongly recommended columns (warn if missing but allow upload)
$recommendedColumns = ['hhname', 'clstid', 'clstname', 'field_coll_date', 'start', 'end', 'deviceid'];

try {
    // Load file
    if ($fileExt === 'csv') {
        $reader = new Csv();
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
    } elseif (in_array($fileExt, ['xls', 'xlsx'])) {
        $reader = new Xlsx();
    } else {
        throw new Exception("Unsupported file type: .$fileExt. Please upload CSV, XLS, or XLSX file.");
    }

    $spreadsheet = $reader->load($tmpFilePath);
    $worksheet = $spreadsheet->getActiveSheet();

    $rows = [];
    $headerRowRaw = [];
    $headerRowMapped = [];
    $foundColumns = [];
    $warnings = [];

    foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = trim((string)$cell->getValue());
        }

        if ($rowIndex == 1) {
            // Process header row
            $headerRowRaw = $cells;

            // Map columns and track what was found
            foreach ($headerRowRaw as $colName) {
                $mappedCol = $columnMapping[$colName] ?? null;
                $headerRowMapped[] = $mappedCol;
                if ($mappedCol !== null) {
                    $foundColumns[$mappedCol] = $colName;
                }
            }

            // Check core required columns
            $missingRequired = [];
            foreach ($coreRequiredColumns as $reqCol) {
                if (!in_array($reqCol, $headerRowMapped)) {
                    $missingRequired[] = $reqCol;
                }
            }

            if (!empty($missingRequired)) {
                // Build detailed error message
                $errorMsg = "Missing required columns: " . implode(', ', $missingRequired) . "\n\n";
                $errorMsg .= "Found columns in your file:\n";
                $errorMsg .= "- " . implode("\n- ", array_slice($headerRowRaw, 0, 10));
                if (count($headerRowRaw) > 10) {
                    $errorMsg .= "\n- ... and " . (count($headerRowRaw) - 10) . " more";
                }
                $errorMsg .= "\n\nExpected column names (any of these formats):\n";
                $errorMsg .= "- round (or PREDTLS-CLUSTER_TYPE_GRP:round)\n";
                $errorMsg .= "- hhcode (or PREDTLS-CLUSTER_TYPE_GRP:hhcode)\n";
                $errorMsg .= "\nPlease check your ODK form export settings.";

                throw new Exception($errorMsg);
            }

            // Check recommended columns
            $missingRecommended = [];
            foreach ($recommendedColumns as $recCol) {
                if (!in_array($recCol, $headerRowMapped)) {
                    $missingRecommended[] = $recCol;
                }
            }

            if (!empty($missingRecommended)) {
                $warnings[] = "Optional columns not found: " . implode(', ', $missingRecommended);
            }

            continue;
        }

        // Skip empty rows
        if (count(array_filter($cells)) === 0) continue;

        // Map row data
        $rowData = [];
        foreach ($headerRowRaw as $idx => $origCol) {
            $dbCol = $columnMapping[$origCol] ?? null;
            if ($dbCol !== null) {
                $rowData[$dbCol] = $cells[$idx] ?? null;
            }
        }

        // Validate and normalize
        $rowData['round'] = isset($rowData['round']) ? (int)$rowData['round'] : null;

        if (empty($rowData['hhcode'])) {
            throw new Exception("Missing hhcode on row $rowIndex");
        }

        if (!isset($rowData['round']) || $rowData['round'] === null) {
            throw new Exception("Missing round on row $rowIndex");
        }

        // Set defaults for missing optional fields
        $optionalFields = [
            'start', 'end', 'deviceid', 'ento_fld_frm_title', 'field_coll_date',
            'fldrecname', 'clstname', 'clstid', 'clsttype_lst', 'hhname',
            'ddrln', 'aninsln', 'ddltwrk', 'ddltwrk_gcomment',
            'lighttrapid', 'collectionbgid', 'instanceID'
        ];

        foreach ($optionalFields as $optField) {
            if (!isset($rowData[$optField]) || $rowData[$optField] === '') {
                $rowData[$optField] = null;
            }
        }

        // Generate device ID if missing (for internal tracking)
        if (empty($rowData['deviceid'])) {
            $rowData['deviceid'] = 'UPLOAD-' . time() . '-' . $rowIndex;
            if (!in_array('Generated device IDs for rows without deviceid', $warnings)) {
                $warnings[] = 'Generated device IDs for rows without deviceid';
            }
        }

        $rows[] = $rowData;
    }

    if (count($rows) === 0) {
        throw new Exception("No valid data rows found in file. Please check that your file contains data after the header row.");
    }

    // Filter to latest round only
    $maxRound = max(array_column($rows, 'round'));
    $rows = array_filter($rows, fn($r) => $r['round'] === $maxRound);

    // Deduplicate by hhcode+clstid+round
    $uniqueKeys = [];
    $uniqueRows = [];
    $duplicateCount = 0;

    foreach ($rows as $r) {
        $clstid = $r['clstid'] ?? 'unknown';
        $key = $r['hhcode'] . '_' . $clstid . '_' . $r['round'];
        if (!isset($uniqueKeys[$key])) {
            $uniqueKeys[$key] = true;
            $uniqueRows[] = $r;
        } else {
            $duplicateCount++;
        }
    }

    if ($duplicateCount > 0) {
        $warnings[] = "Removed $duplicateCount duplicate records";
    }

    // Delete old temp upload data
    $stmtDel = $pdo->prepare("DELETE FROM temp_field_collector WHERE user_id=?");
    $stmtDel->execute([$userId]);

    // Prepare insert
    $insertCols = [
        'start', 'end', 'deviceid', 'ento_fld_frm_title', 'field_coll_date',
        'fldrecname', 'clstname', 'clstid', 'clsttype_lst', 'round',
        'hhcode', 'hhname', 'ddrln', 'aninsln', 'ddltwrk',
        'ddltwrk_gcomment', 'lighttrapid', 'collectionbgid', 'instanceID',
        'user_id', 'created_at'
    ];

    $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
    $insertSql = "INSERT INTO temp_field_collector (" . implode(',', $insertCols) . ") VALUES ($placeholders)";
    $stmtInsert = $pdo->prepare($insertSql);
    $now = date('Y-m-d H:i:s');

    foreach ($uniqueRows as $row) {
        $params = [];
        foreach ($insertCols as $col) {
            if ($col === 'user_id') {
                $params[] = $userId;
            } elseif ($col === 'created_at') {
                $params[] = $now;
            } else {
                $params[] = $row[$col] ?? null;
            }
        }
        $stmtInsert->execute($params);
    }

    // Fetch expected households (only active)
    $stmtExp = $pdo->prepare("
        SELECT h.hhcode, h.cluster_id AS clstid, c.cluster_name, u.phone,
               CONCAT(u.fname,' ',u.lname) AS field_recorder
        FROM households h
        INNER JOIN clusters c ON h.cluster_id=c.cluster_id
        LEFT JOIN users u ON c.user_id=u.id
        WHERE c.cluster_state_id=1 AND h.household_state_id=1
    ");
    $stmtExp->execute();
    $expectedHouseholds = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    $expectedKeys = [];
    foreach ($expectedHouseholds as $hh) {
        $expectedKeys[$hh['hhcode'] . '_' . $hh['clstid']] = $hh;
    }

    // Uploaded keys
    $uploadedKeys = [];
    foreach ($uniqueRows as $r) {
        $clstid = $r['clstid'] ?? 'unknown';
        $uploadedKeys[$r['hhcode'] . '_' . $clstid] = true;
    }

    // Missing households
    $missingHouseholds = [];
    foreach ($expectedKeys as $key => $hh) {
        if (!isset($uploadedKeys[$key])) {
            $missingHouseholds[] = [
                'hhcode' => $hh['hhcode'],
                'cluster_name' => $hh['cluster_name'] ?: '-',
                'field_recorder' => $hh['field_recorder'] ?: '-',
                'phone' => $hh['phone'] ?: '-'
            ];
        }
    }

    // Preview rows (limit 30)
    $previewRows = array_slice($uniqueRows, 0, 30);

    // Build success response
    $response = [
        'success' => true,
        'message' => 'Records uploaded successfully to staging area.',
        'uploaded_count' => count($uniqueRows),
        'expected_count' => count($expectedHouseholds),
        'missing_count' => count($missingHouseholds),
        'missing_households' => $missingHouseholds,
        'preview' => $previewRows,
        'found_columns' => array_keys($foundColumns),
        'round' => $maxRound
    ];

    if (!empty($warnings)) {
        $response['warnings'] = $warnings;
    }

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
