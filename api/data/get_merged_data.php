<?php
/**
 * Get Merged Data View API
 *
 * Fetches data from merged_data_view (3-layer architecture unified view)
 * Replaces vw_merged_field_lab_data with enhanced status and source tracking
 *
 * @requires Permission: data_entry or view_data
 * @input GET: { round?: int, search?: string, status?: 'all'|'permanent'|'pending', source?: 'all'|'ODK'|'Internal Form', limit?: int }
 * @output JSON: { success: bool, data: array, total: int, summary: object }
 */

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

// Permission check
if (!checkPermission('data_entry') && !checkPermission('view_data')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    // Get filters
    $round = isset($_GET['round']) ? (int)$_GET['round'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : 'all'; // all, permanent, pending
    $source = isset($_GET['source']) ? trim($_GET['source']) : 'all'; // all, ODK, Internal Form
    $dataType = isset($_GET['data_type']) ? trim($_GET['data_type']) : 'all'; // all, field, lab
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;

    // Validate status
    if (!in_array($status, ['all', 'permanent', 'pending'])) {
        $status = 'all';
    }

    // Validate source
    if (!in_array($source, ['all', 'ODK', 'Internal Form'])) {
        $source = 'all';
    }

    // Validate data_type
    if (!in_array($dataType, ['all', 'field', 'lab'])) {
        $dataType = 'all';
    }

    // Build query
    $sql = "SELECT
                data_type,
                source_table,
                data_status,
                data_source,
                round,
                hhcode,
                hhname,
                clstid,
                clstname,
                field_coll_date,
                fldrecname AS field_recorder,
                lab_date,
                srtname AS lab_sorter,
                deviceid,
                instanceID,
                user_id,
                created_at,
                -- Lab counts for summary
                male_ag, female_ag, male_af, female_af,
                male_oan, female_oan, male_culex, female_culex,
                male_aedes, female_aedes
            FROM merged_data_view";

    $params = [];
    $where = [];

    // Apply filters
    if ($round > 0) {
        $where[] = "round = :round";
        $params[':round'] = $round;
    }

    if ($status !== 'all') {
        $where[] = "data_status = :status";
        $params[':status'] = $status;
    }

    if ($source !== 'all') {
        $where[] = "data_source = :source";
        $params[':source'] = $source;
    }

    if ($dataType !== 'all') {
        $where[] = "data_type = :data_type";
        $params[':data_type'] = $dataType;
    }

    if ($search !== '') {
        $where[] = "(hhcode LIKE :search OR hhname LIKE :search2 OR
                     clstname LIKE :search3 OR fldrecname LIKE :search4 OR srtname LIKE :search5)";
        $searchParam = '%' . $search . '%';
        $params[':search'] = $searchParam;
        $params[':search2'] = $searchParam;
        $params[':search3'] = $searchParam;
        $params[':search4'] = $searchParam;
        $params[':search5'] = $searchParam;
    }

    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $sql .= " ORDER BY created_at DESC, round DESC LIMIT :limit";
    $params[':limit'] = $limit;

    $stmt = $pdo->prepare($sql);

    // Bind limit separately (must be int)
    foreach ($params as $key => $val) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        } else if ($key === ':round') {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get summary statistics
    $summarySql = "SELECT
                        data_status,
                        data_source,
                        data_type,
                        COUNT(*) as count
                    FROM merged_data_view";

    $summaryWhere = [];
    $summaryParams = [];

    if ($round > 0) {
        $summaryWhere[] = "round = :round";
        $summaryParams[':round'] = $round;
    }

    if ($summaryWhere) {
        $summarySql .= " WHERE " . implode(' AND ', $summaryWhere);
    }

    $summarySql .= " GROUP BY data_status, data_source, data_type";

    $stmtSummary = $pdo->prepare($summarySql);
    $stmtSummary->execute($summaryParams);
    $summaryData = $stmtSummary->fetchAll(PDO::FETCH_ASSOC);

    // Format summary
    $summary = [
        'total' => 0,
        'permanent' => 0,
        'pending' => 0,
        'odk' => 0,
        'internal' => 0,
        'field' => 0,
        'lab' => 0,
        'by_status_source' => []
    ];

    foreach ($summaryData as $row) {
        $count = (int)$row['count'];
        $summary['total'] += $count;

        if ($row['data_status'] === 'permanent') {
            $summary['permanent'] += $count;
        } else if ($row['data_status'] === 'pending') {
            $summary['pending'] += $count;
        }

        if ($row['data_source'] === 'ODK') {
            $summary['odk'] += $count;
        } else if ($row['data_source'] === 'Internal Form') {
            $summary['internal'] += $count;
        }

        if ($row['data_type'] === 'field') {
            $summary['field'] += $count;
        } else if ($row['data_type'] === 'lab') {
            $summary['lab'] += $count;
        }

        $summary['by_status_source'][] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
        'summary' => $summary,
        'filters' => [
            'round' => $round,
            'status' => $status,
            'source' => $source,
            'data_type' => $dataType,
            'search' => $search
        ],
        'message' => count($data) === 0 ? 'No matching records found' : 'Data loaded successfully'
    ]);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $ex->getMessage()
    ]);
}
