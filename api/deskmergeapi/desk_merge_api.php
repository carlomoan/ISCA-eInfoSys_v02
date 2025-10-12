<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    $sessionConfig = Config::session();
    session_name($sessionConfig['name']);
    session_start();
}

header('Content-Type: application/json');

// Check permission
if (!checkPermission('data_entry') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Access denied']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'User not logged in']);
    exit;
}

try {
    // ========== DELETE TEMP ==========
    if (isset($_GET['delete_temp'])) {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM desk_field_collector WHERE user_id=?")->execute([$userId]);
        $pdo->prepare("DELETE FROM desk_lab_sorter WHERE user_id=?")->execute([$userId]);
        $pdo->commit();
        echo json_encode(['success'=>true,'message'=>'Temp desk data deleted']);
        exit;
    }

    // ========== CHECK SUMMARY ==========
    if (isset($_GET['check'])) {

        // HH from field
        $fieldHH = $pdo->prepare("SELECT DISTINCT hhcode FROM desk_field_collector WHERE user_id=?");
        $fieldHH->execute([$userId]);
        $fieldHH = $fieldHH->fetchAll(PDO::FETCH_COLUMN);

        // HH from lab
        $labHH = $pdo->prepare("SELECT DISTINCT hhcode FROM desk_lab_sorter WHERE user_id=?");
        $labHH->execute([$userId]);
        $labHH = $labHH->fetchAll(PDO::FETCH_COLUMN);

        // Matches
        $matched   = array_values(array_intersect($fieldHH,$labHH));
        $fieldOnly = array_values(array_diff($fieldHH,$labHH));
        $labOnly   = array_values(array_diff($labHH,$fieldHH));

        // ---- Preview matched records ----
        $preview = [];
        if(count($matched) > 0){
            $placeholders = implode(',', array_fill(0, count($matched), '?'));
            $stmt = $pdo->prepare("
                SELECT f.hhcode, f.hhname, c.cluster_name AS cluster_name, f.field_coll_date, l.lab_date
                FROM desk_field_collector f
                JOIN desk_lab_sorter l ON f.hhcode=l.hhcode AND l.user_id=f.user_id
                LEFT JOIN clusters c ON f.clstid = c.cluster_id
                WHERE f.user_id=? AND f.hhcode IN ($placeholders)
                LIMIT 60
            ");
            $stmt->execute(array_merge([$userId], $matched));
            $preview = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // ---- Field only ----
        $fieldOnlyData = [];
        if(!empty($fieldOnly)){
            $placeholders = implode(',', array_fill(0, count($fieldOnly), '?'));
            $stmt = $pdo->prepare("
                SELECT f.hhcode, c.cluster_name AS cluster_name
                FROM desk_field_collector f
                LEFT JOIN clusters c ON f.clstid = c.cluster_id
                WHERE f.user_id=? AND f.hhcode IN ($placeholders)
            ");
            $stmt->execute(array_merge([$userId], $fieldOnly));
            $fieldOnlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // ---- Lab only ----
        $labOnlyData = [];
        if(!empty($labOnly)){
            $placeholders = implode(',', array_fill(0, count($labOnly), '?'));
            $stmt = $pdo->prepare("
                SELECT l.hhcode, c.cluster_name AS cluster_name
                FROM desk_lab_sorter l
                LEFT JOIN clusters c ON l.cluster_id = c.cluster_id
                WHERE l.user_id=? AND l.hhcode IN ($placeholders)
            ");
            $stmt->execute(array_merge([$userId], $labOnly));
            $labOnlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success'       => true,
            'matched_total' => count($matched),
            'preview'       => $preview,
            'field_only'    => $fieldOnlyData,
            'lab_only'      => $labOnlyData
        ]);
        exit;
    }

    // ========== MERGE ==========
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        // Fetch HH codes
        $fieldHH = $pdo->prepare("SELECT DISTINCT hhcode FROM desk_field_collector WHERE user_id=?");
        $fieldHH->execute([$userId]);
        $fieldHH = $fieldHH->fetchAll(PDO::FETCH_COLUMN);

        $labHH = $pdo->prepare("SELECT DISTINCT hhcode FROM desk_lab_sorter WHERE user_id=?");
        $labHH->execute([$userId]);
        $labHH = $labHH->fetchAll(PDO::FETCH_COLUMN);

        $matched = array_values(array_intersect($fieldHH,$labHH));

        if(count($matched) === 0){
            echo json_encode(['success'=>false,'message'=>'No matched records to merge']);
            exit;
        }

        $pdo->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($matched), '?'));
        $params = array_merge([$userId], $matched);

        // Insert into field_collector
        $sqlField = "
            INSERT INTO field_collector (
                hhcode, hhname, field_coll_date, fldrecname, clstid, clsttype_lst,
                round, start, end, deviceid, ento_fld_frm_title, ddltwrk, ddltwrk_gcomment, lighttrapid,
                collectionbgid, aninsln, ddrln, instanceID, user_id, created_at
            )
            SELECT hhcode, hhname, field_coll_date, fldrecname, clstid, clsttype_lst,
                   round, start, end, deviceid, ento_fld_frm_title, ddltwrk, ddltwrk_gcomment, lighttrapid,
                   collectionbgid, aninsln, ddrln, instanceID, user_id, created_at
            FROM desk_field_collector
            WHERE user_id=? AND hhcode IN ($placeholders)
        ";
        $pdo->prepare($sqlField)->execute($params);

        // Insert into lab_sorter
        $sqlLab = "
            INSERT INTO lab_sorter (
                hhcode, hhname, lab_date, srtname, male_ag,female_ag,fed_ag,unfed_ag,gravid_ag,semi_gravid_ag,
                male_af,female_af,fed_af,unfed_af,gravid_af,semi_gravid_af,
                male_oan,female_oan,fed_oan,unfed_oan,gravid_oan,semi_gravid_oan,
                male_culex,female_culex,fed_culex,unfed_culex,gravid_culex,semi_gravid_culex,
                male_other_culex,female_other_culex,male_aedes,female_aedes,cluster_id,
                user_id,instanceID,created_at
            )
            SELECT hhcode, hhname, lab_date, srtname, male_ag,female_ag,fed_ag,unfed_ag,gravid_ag,semi_gravid_ag,
                   male_af,female_af,fed_af,unfed_af,gravid_af,semi_gravid_af,
                   male_oan,female_oan,fed_oan,unfed_oan,gravid_oan,semi_gravid_oan,
                   male_culex,female_culex,fed_culex,unfed_culex,gravid_culex,semi_gravid_oan,
                   male_other_culex,female_other_culex,male_aedes,female_aedes,cluster_id,
                   user_id,instanceID,created_at
            FROM desk_lab_sorter
            WHERE user_id=? AND hhcode IN ($placeholders)
        ";
        $pdo->prepare($sqlLab)->execute($params);

        // Delete temp data
        $pdo->prepare("DELETE FROM desk_field_collector WHERE user_id=? AND hhcode IN ($placeholders)")->execute($params);
        $pdo->prepare("DELETE FROM desk_lab_sorter WHERE user_id=? AND hhcode IN ($placeholders)")->execute($params);

        $pdo->commit();

        echo json_encode(['success'=>true,'message'=>'Matched data merged successfully']);
        exit;
    }

    throw new Exception("Invalid request");

} catch (Exception $ex) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Error: '.$ex->getMessage()]);
}
