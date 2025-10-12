<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/ISCA-eInfoSys_v02/config/db_connect.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/ISCA-eInfoSys_v02/helpers/permission_helper.php');

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

$round = $_GET['round'] ?? null;
if ($round === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Round parameter is required']);
    exit;
}

try {
    // Get all rows for this user and round
    $stmt = $pdo->prepare("SELECT * FROM temp_Lab_sorter WHERE user_id = :user_id AND round = :round");
    $stmt->execute([':user_id' => $userId, ':round' => $round]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        echo json_encode([
            'success' => true,
            'round' => $round,
            'preview' => [],
            'missing_households' => [],
            'uploaded_count' => 0,
            'expected_count' => 0,
        ]);
        exit;
    }

    // Build unique hhcode keys from uploaded rows
    $uploadedKeys = [];
    foreach ($rows as $r) {
        $key = trim((string)$r['hhcode']);
        $uploadedKeys[$key] = $r;
    }
    $uploaded_count = count($uploadedKeys);

    // Fetch expected households (max 54)
    $stmt = $pdo->prepare("SELECT h.hhcode, c.cluster_name, CONCAT(u.fname, ' ', u.lname) AS sorter_name, u.phone
                           FROM households h
                           LEFT JOIN clusters c ON h.cluster_id = c.cluster_id
                           LEFT JOIN users u ON c.user_id = u.id
                           LIMIT 54");
    $stmt->execute();
    $expectedHouseholds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expectedKeys = [];
    foreach ($expectedHouseholds as $hh) {
        $key = trim((string)$hh['hhcode']);
        $expectedKeys[$key] = $hh;
    }
    $expected_count = count($expectedKeys);

    // Find missing households = expected - uploaded
    $missingHHs = [];
    foreach ($expectedKeys as $key => $hh) {
        if (!isset($uploadedKeys[$key])) {
            $missingHHs[] = [
                'hhcode' => $hh['hhcode'],
                'cluster_name' => $hh['cluster_name'] ?? '-',
                'sorter_name' => $hh['sorter_name'] ?? '-',
                'phone' => $hh['phone'] ?? '-',
            ];
        }
    }

    // Prepare preview limited to 54
    $preview = array_values($uploadedKeys);
    if (count($preview) > 54) {
        $preview = array_slice($preview, 0, 54);
    }

    echo json_encode([
        'success' => true,
        'round' => $round,
        'preview' => $preview,
        'missing_households' => $missingHHs,
        'uploaded_count' => $uploaded_count,
        'expected_count' => $expected_count,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching preview data']);
}
