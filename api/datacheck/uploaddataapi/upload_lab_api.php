<?php
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

if (!isset($_FILES['lab_file']) || $_FILES['lab_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
    exit;
}

$tmpFilePath = $_FILES['lab_file']['tmp_name'];
$fileName = $_FILES['lab_file']['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$columnMapping = [
    'start' => 'start',
    'end' => 'end',
    'deviceid' => 'deviceid',
    'ento_lab_frm_title' => 'ento_lab_frm_title',
    'lab_date' => 'lab_date',
    'PREDTLSOFLI_GRP:srtname' => 'srtname',
    'PREDTLSOFLI_GRP:round' => 'round',
    'PREDTLSOFLI_GRP:hhname' => 'hhname',
    'PREDTLSOFLI_GRP:hhcode' => 'hhcode',
    'PREDTLSOFLI_GRP:field_coll_date' => 'field_coll_date',
    'ANGCMPLX_GRP:male_ag' => 'male_ag',
    'ANGCMPLX_GRP:female_ag' => 'female_ag',
    'ANGCMPLX_GRP:fed_ag' => 'fed_ag',
    'ANGCMPLX_GRP:unfed_ag' => 'unfed_ag',
    'ANGCMPLX_GRP:gravid_ag' => 'gravid_ag',
    'ANGCMPLX_GRP:semi_gravid_ag' => 'semi_gravid_ag',
    'ANFUNES_GRP:male_af' => 'male_af',
    'ANFUNES_GRP:female_af' => 'female_af',
    'ANFUNES_GRP:fed_af' => 'fed_af',
    'ANFUNES_GRP:unfed_af' => 'unfed_af',
    'ANFUNES_GRP:gravid_af' => 'gravid_af',
    'ANFUNES_GRP:semi_gravid_af' => 'semi_gravid_af',
    'OTHERAN_GRP:male_oan' => 'male_oan',
    'OTHERAN_GRP:female_oan' => 'female_oan',
    'OTHERAN_GRP:fed_oan' => 'fed_oan',
    'OTHERAN_GRP:unfed_oan' => 'unfed_oan',
    'OTHERAN_GRP:gravid_oan' => 'gravid_oan',
    'OTHERAN_GRP:semi_gravid_oan' => 'semi_gravid_oan',
    'CULEX_GRP:male_culex' => 'male_culex',
    'CULEX_GRP:female_culex' => 'female_culex',
    'CULEX_GRP:fed_culex' => 'fed_culex',
    'CULEX_GRP:unfed_culex' => 'unfed_culex',
    'CULEX_GRP:gravid_culex' => 'gravid_culex',
    'CULEX_GRP:semi_gravid_culex' => 'semi_gravid_culex',
    'OTHERCULEX_GRP:male_other_culex' => 'male_other_culex',
    'OTHERCULEX_GRP:female_other_culex' => 'female_other_culex',
    'AEDES_GRP-A:male_aedes' => 'male_aedes',
    'AEDES_GRP-A:female_aedes' => 'female_aedes',
    'meta:instanceID' => 'instanceID',
];

$requiredColumns = ['hhcode', 'srtname', 'round', 'instanceID'];

try {
    if ($fileExt === 'csv') {
        $reader = new Csv();
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
    } elseif (in_array($fileExt, ['xls', 'xlsx'])) {
        $reader = new Xlsx();
    } else {
        throw new Exception("Unsupported file type: .$fileExt. Only CSV, XLS, XLSX allowed.");
    }

    $spreadsheet = $reader->load($tmpFilePath);
    $worksheet = $spreadsheet->getActiveSheet();

    $allRows = [];
    $headerRow = [];
    foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = trim((string)$cell->getValue());
        }

        if ($rowIndex === 1) {
            $headerRow = $cells;
            $mappedCols = array_map(fn($col) => $columnMapping[$col] ?? null, $headerRow);
            foreach ($requiredColumns as $req) {
                if (!in_array($req, $mappedCols)) {
                    throw new Exception("Missing required column: $req");
                }
            }
            continue;
        }

        if (count(array_filter($cells)) === 0) continue;

        $rowData = [];
        foreach ($headerRow as $idx => $colName) {
            if (isset($columnMapping[$colName])) {
                $dbField = $columnMapping[$colName];
                $rowData[$dbField] = $cells[$idx] ?? null;
            }
        }

        foreach ($requiredColumns as $req) {
            if (empty($rowData[$req])) {
                throw new Exception("Missing $req on row $rowIndex");
            }
        }

        // normalize numeric fields
        $numFields = [
            'round','male_ag','female_ag','fed_ag','unfed_ag','gravid_ag','semi_gravid_ag',
            'male_af','female_af','fed_af','unfed_af','gravid_af','semi_gravid_af',
            'male_oan','female_oan','fed_oan','unfed_oan','gravid_oan','semi_gravid_oan',
            'male_culex','female_culex','fed_culex','unfed_culex','gravid_culex','semi_gravid_culex',
            'male_other_culex','female_other_culex','male_aedes','female_aedes'
        ];
        foreach ($numFields as $nf) {
            $rowData[$nf] = isset($rowData[$nf]) && is_numeric($rowData[$nf]) ? (int)$rowData[$nf] : 0;
        }

        $rowData['user_id'] = $userId;
        $rowData['created_at'] = date('Y-m-d H:i:s');

        $allRows[] = $rowData;
    }

    if (count($allRows) === 0) {
        throw new Exception("No valid data rows found in file");
    }

    // determine latest round
    $roundValues = array_map(fn($r) => is_numeric($r['round']) ? (int)$r['round'] : $r['round'], $allRows);
    $latestRound = null;
    if (count(array_filter($roundValues, 'is_int')) > 0) {
        $latestRound = max($roundValues);
    } else {
        foreach (array_reverse($roundValues) as $v) {
            if ($v !== null && $v !== '') { $latestRound = $v; break; }
        }
        if ($latestRound === null) throw new Exception("Could not determine latest round from file");
    }

    $rowsForLatestRound = array_filter($allRows, fn($r) => ((string)$r['round'] === (string)$latestRound));
    $totalRecordsForRound = count($rowsForLatestRound);
    if ($totalRecordsForRound === 0) {
        throw new Exception("No rows found for latest round: " . (string)$latestRound);
    }

    // Delete old temp data for this user
    $pdo->exec("DELETE FROM temp_lab_sorter WHERE user_id = " . (int)$userId);

    // deduplicate by hhcode and limit to 54
    $uniqueKeys = [];
    $uniqueRows = [];
    foreach ($rowsForLatestRound as $r) {
        $hh = trim((string)($r['hhcode'] ?? ''));
        if ($hh === '') continue;
        if (!isset($uniqueKeys[$hh])) {
            $uniqueKeys[$hh] = true;
            $uniqueRows[] = $r;
        }
        if (count($uniqueRows) == 54) break; // keep existing behaviour
    }

    $uploaded_count = count($uniqueRows);

    // Use an explicit INSERT with named placeholders (safe and avoids header-name issues)
    $insertSql = "INSERT INTO temp_lab_sorter (
        start, end, deviceid, ento_lab_frm_title, lab_date,
        srtname, round, hhname, hhcode, field_coll_date,
        male_ag, female_ag, fed_ag, unfed_ag, gravid_ag, semi_gravid_ag,
        male_af, female_af, fed_af, unfed_af, gravid_af, semi_gravid_af,
        male_oan, female_oan, fed_oan, unfed_oan, gravid_oan, semi_gravid_oan,
        male_culex, female_culex, fed_culex, unfed_culex, gravid_culex, semi_gravid_culex,
        male_other_culex, female_other_culex, male_aedes, female_aedes, cluster_id,
        user_id, instanceID, created_at
    ) VALUES (
        :start, :end, :deviceid, :ento_lab_frm_title, :lab_date,
        :srtname, :round, :hhname, :hhcode, :field_coll_date,
        :male_ag, :female_ag, :fed_ag, :unfed_ag, :gravid_ag, :semi_gravid_ag,
        :male_af, :female_af, :fed_af, :unfed_af, :gravid_af, :semi_gravid_af,
        :male_oan, :female_oan, :fed_oan, :unfed_oan, :gravid_oan, :semi_gravid_oan,
        :male_culex, :female_culex, :fed_culex, :unfed_culex, :gravid_culex, :semi_gravid_culex,
        :male_other_culex, :female_other_culex, :male_aedes, :female_aedes, :cluster_id,
        :user_id, :instanceID, :created_at
    )
    ON DUPLICATE KEY UPDATE
        end = VALUES(end),
        deviceid = VALUES(deviceid),
        ento_lab_frm_title = VALUES(ento_lab_frm_title),
        lab_date = VALUES(lab_date),
        srtname = VALUES(srtname),
        round = VALUES(round),
        hhname = VALUES(hhname),
        field_coll_date = VALUES(field_coll_date),
        male_ag = VALUES(male_ag), female_ag = VALUES(female_ag), fed_ag = VALUES(fed_ag), unfed_ag = VALUES(unfed_ag),
        gravid_ag = VALUES(gravid_ag), semi_gravid_ag = VALUES(semi_gravid_ag),
        male_af = VALUES(male_af), female_af = VALUES(female_af), fed_af = VALUES(fed_af), unfed_af = VALUES(unfed_af),
        gravid_af = VALUES(gravid_af), semi_gravid_af = VALUES(semi_gravid_af),
        male_oan = VALUES(male_oan), female_oan = VALUES(female_oan), fed_oan = VALUES(fed_oan), unfed_oan = VALUES(unfed_oan),
        gravid_oan = VALUES(gravid_oan), semi_gravid_oan = VALUES(semi_gravid_oan),
        male_culex = VALUES(male_culex), female_culex = VALUES(female_culex),
        fed_culex = VALUES(fed_culex), unfed_culex = VALUES(unfed_culex),
        gravid_culex = VALUES(gravid_culex), semi_gravid_culex = VALUES(semi_gravid_culex),
        male_other_culex = VALUES(male_other_culex), female_other_culex = VALUES(female_other_culex),
        male_aedes = VALUES(male_aedes), female_aedes = VALUES(female_aedes),
        cluster_id = VALUES(cluster_id),
        instanceID = VALUES(instanceID),
        created_at = NOW()
    ";

    $stmtInsert = $pdo->prepare($insertSql);

    // Helper function to parse datetime strings
    $parseDateTime = function($value) {
        if (empty($value)) return null;
        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    };

    // Helper function to parse date strings
    $parseDate = function($value) {
        if (empty($value)) return null;
        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    };

    foreach ($uniqueRows as $row) {
        // compute cluster_id from hhcode prefix (same approach as before)
        $clusterId = (int) (substr((string)($row['hhcode'] ?? ''), 0, 5) ?: 0);

        $stmtInsert->execute([
            ':start' => $parseDateTime($row['start'] ?? null),
            ':end' => $parseDateTime($row['end'] ?? null),
            ':deviceid' => $row['deviceid'] ?? null,
            ':ento_lab_frm_title' => $row['ento_lab_frm_title'] ?? null,
            ':lab_date' => $parseDate($row['lab_date'] ?? null),
            ':srtname' => $row['srtname'] ?? null,
            ':round' => $row['round'] ?? 0,
            ':hhname' => $row['hhname'] ?? null,
            ':hhcode' => $row['hhcode'] ?? null,
            ':field_coll_date' => $parseDate($row['field_coll_date'] ?? null),
            ':male_ag' => $row['male_ag'] ?? 0,
            ':female_ag' => $row['female_ag'] ?? 0,
            ':fed_ag' => $row['fed_ag'] ?? 0,
            ':unfed_ag' => $row['unfed_ag'] ?? 0,
            ':gravid_ag' => $row['gravid_ag'] ?? 0,
            ':semi_gravid_ag' => $row['semi_gravid_ag'] ?? 0,
            ':male_af' => $row['male_af'] ?? 0,
            ':female_af' => $row['female_af'] ?? 0,
            ':fed_af' => $row['fed_af'] ?? 0,
            ':unfed_af' => $row['unfed_af'] ?? 0,
            ':gravid_af' => $row['gravid_af'] ?? 0,
            ':semi_gravid_af' => $row['semi_gravid_af'] ?? 0,
            ':male_oan' => $row['male_oan'] ?? 0,
            ':female_oan' => $row['female_oan'] ?? 0,
            ':fed_oan' => $row['fed_oan'] ?? 0,
            ':unfed_oan' => $row['unfed_oan'] ?? 0,
            ':gravid_oan' => $row['gravid_oan'] ?? 0,
            ':semi_gravid_oan' => $row['semi_gravid_oan'] ?? 0,
            ':male_culex' => $row['male_culex'] ?? 0,
            ':female_culex' => $row['female_culex'] ?? 0,
            ':fed_culex' => $row['fed_culex'] ?? 0,
            ':unfed_culex' => $row['unfed_culex'] ?? 0,
            ':gravid_culex' => $row['gravid_culex'] ?? 0,
            ':semi_gravid_culex' => $row['semi_gravid_culex'] ?? 0,
            ':male_other_culex' => $row['male_other_culex'] ?? 0,
            ':female_other_culex' => $row['female_other_culex'] ?? 0,
            ':male_aedes' => $row['male_aedes'] ?? 0,
            ':female_aedes' => $row['female_aedes'] ?? 0,
            ':cluster_id' => $clusterId,
            ':user_id' => $userId,
            ':instanceID' => $row['instanceID'] ?? null,
            ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    // --- Fetch expected households (only active clusters/households) ---
    $stmt = $pdo->prepare("
        SELECT h.hhcode, h.cluster_id, c.cluster_name
        FROM households h
        INNER JOIN clusters c ON h.cluster_id = c.cluster_id
        WHERE h.household_state_id = 1
          AND c.cluster_state_id = 1
    ");
    $stmt->execute();
    $expectedHouseholds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expectedKeys = [];
    foreach ($expectedHouseholds as $hh) {
        $key = trim($hh['hhcode']);
        $expectedKeys[$key] = $hh;
    }

    $uploadedKeys = [];
    foreach ($uniqueRows as $r) {
        $key = trim($r['hhcode']);
        $uploadedKeys[$key] = true;
    }

    // --- Missing households check (get field recorder info) ---
    $missingHHs = [];
    $stmtCluster = $pdo->prepare("SELECT user_id FROM clusters WHERE cluster_id = :cluster_id LIMIT 1");
    $stmtUser = $pdo->prepare("SELECT fname, lname, phone FROM users WHERE id = :user_id LIMIT 1");

    foreach ($expectedKeys as $key => $hh) {
        if (!isset($uploadedKeys[$key])) {
            $fieldRecorder = '-';
            $phone = '-';

            $stmtCluster->execute([':cluster_id' => $hh['cluster_id']]);
            $cluster = $stmtCluster->fetch(PDO::FETCH_ASSOC);
            if ($cluster && !empty($cluster['user_id'])) {
                $stmtUser->execute([':user_id' => $cluster['user_id']]);
                $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $fname = $user['fname'] ?? '';
                    $lname = $user['lname'] ?? '';
                    $fieldRecorder = trim($fname . ' ' . $lname) ?: '-';
                    $phone = $user['phone'] ?? '-';
                }
            }

            $missingHHs[] = [
                'hhcode' => $hh['hhcode'],
                'cluster_name' => $hh['cluster_name'],
                'field_recorder' => $fieldRecorder,
                'phone' => $phone,
            ];
        }
    }

    $preview = array_slice($uniqueRows, 0, 30);

    echo json_encode([
        'success' => true,
        'message' => "File processed successfully",
        'uploaded_count' => $uploaded_count,
        'expected_count' => count($expectedHouseholds),
        'missing_count' => count($missingHHs),
        'missing_households' => $missingHHs,
        'preview' => $preview,
    ]);
    exit;

} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing file: ' . $ex->getMessage()
    ]);
    exit;
}
