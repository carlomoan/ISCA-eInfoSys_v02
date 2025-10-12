<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if (session_status() == PHP_SESSION_NONE) {
    $sessionConfig = Config::session();
    session_name($sessionConfig['name']);
    session_start();
}

header('Content-Type: application/json');

if(!checkPermission('data_entry')){
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Access denied']); exit;
}


$userId = $_SESSION['user_id'] ?? null;
if(!$userId){
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'User not logged in']); exit;
}

try {
    $latestRoundField = $pdo->prepare("SELECT MAX(round) FROM temp_field_collector WHERE user_id=?");
    $latestRoundField->execute([$userId]);
    $latestRoundField = $latestRoundField->fetchColumn();

    $latestRoundLab = $pdo->prepare("SELECT MAX(round) FROM temp_lab_sorter WHERE user_id=?");
    $latestRoundLab->execute([$userId]);
    $latestRoundLab = $latestRoundLab->fetchColumn();

    if(!$latestRoundField || !$latestRoundLab) throw new Exception("No rounds found.");
    if((int)$latestRoundField !== (int)$latestRoundLab) throw new Exception("Latest rounds mismatch.");

    $latestRound = (int)$latestRoundField;

    // Get HH codes
    $hhFieldSet = $pdo->prepare("SELECT DISTINCT hhcode FROM temp_field_collector WHERE round=? AND user_id=?");
    $hhFieldSet->execute([$latestRound,$userId]);
    $hhFieldSet = $hhFieldSet->fetchAll(PDO::FETCH_COLUMN);

    $hhLabSet = $pdo->prepare("SELECT DISTINCT hhcode FROM temp_lab_sorter WHERE round=? AND user_id=?");
    $hhLabSet->execute([$latestRound,$userId]);
    $hhLabSet = $hhLabSet->fetchAll(PDO::FETCH_COLUMN);

    if(empty($hhFieldSet) && empty($hhLabSet)) throw new Exception("No HH codes to merge.");

    $matchedHH = array_intersect($hhFieldSet, $hhLabSet);
    $mismatchedField = array_diff($hhFieldSet,$matchedHH);
    $mismatchedLab = array_diff($hhLabSet,$matchedHH);
    $mismatchedHH = array_unique(array_merge($mismatchedField,$mismatchedLab));

    $pdo->beginTransaction();

    $placeholdersMatched = implode(',', array_fill(0,count($matchedHH),'?'));
    $placeholdersMismatched = implode(',', array_fill(0,count($mismatchedHH),'?'));

    // Matched: insert/update
    if(!empty($matchedHH)){
        $stmtInsField = $pdo->prepare("
            INSERT INTO field_collector (start,end,deviceid,ento_fld_frm_title,field_coll_date,
                fldrecname,clstname,clstid,clsttype_lst,round,hhcode,hhname,ddrln,aninsln,ddltwrk,
                ddltwrk_gcomment,lighttrapid,collectionbgid,instanceID,user_id,created_at)
            SELECT start,end,deviceid,ento_fld_frm_title,field_coll_date,
                fldrecname,clstname,clstid,clsttype_lst,round,hhcode,hhname,ddrln,aninsln,ddltwrk,
                ddltwrk_gcomment,lighttrapid,collectionbgid,instanceID,user_id,created_at
            FROM temp_field_collector
            WHERE round=? AND user_id=? AND hhcode IN ($placeholdersMatched)
            ON DUPLICATE KEY UPDATE
                start=VALUES(start), end=VALUES(end), deviceid=VALUES(deviceid),
                ento_fld_frm_title=VALUES(ento_fld_frm_title), field_coll_date=VALUES(field_coll_date),
                fldrecname=VALUES(fldrecname), clstname=VALUES(clstname), clstid=VALUES(clstid),
                clsttype_lst=VALUES(clsttype_lst), hhname=VALUES(hhname),
                ddrln=VALUES(ddrln), aninsln=VALUES(aninsln), ddltwrk=VALUES(ddltwrk),
                ddltwrk_gcomment=VALUES(ddltwrk_gcomment), lighttrapid=VALUES(lighttrapid),
                collectionbgid=VALUES(collectionbgid), instanceID=VALUES(instanceID),
                user_id=VALUES(user_id), created_at=VALUES(created_at)
        ");
        $stmtInsField->execute(array_merge([$latestRound,$userId],$matchedHH));

        $stmtInsLab = $pdo->prepare("
            INSERT INTO lab_sorter (start,end,deviceid,ento_lab_frm_title,lab_date,
                srtname,round,hhname,hhcode,field_coll_date,
                male_ag,female_ag,fed_ag,unfed_ag,gravid_ag,semi_gravid_ag,
                male_af,female_af,fed_af,unfed_af,gravid_af,semi_gravid_af,
                male_oan,female_oan,fed_oan,unfed_oan,gravid_oan,semi_gravid_oan,
                male_culex,female_culex,fed_culex,unfed_culex,gravid_culex,semi_gravid_culex,
                male_other_culex,female_other_culex,male_aedes,female_aedes,cluster_id,
                user_id,instanceID,created_at)
            SELECT start,end,deviceid,ento_lab_frm_title,lab_date,
                srtname,round,hhname,hhcode,field_coll_date,
                male_ag,female_ag,fed_ag,unfed_ag,gravid_ag,semi_gravid_ag,
                male_af,female_af,fed_af,unfed_af,gravid_af,semi_gravid_af,
                male_oan,female_oan,fed_oan,unfed_oan,gravid_oan,semi_gravid_oan,
                male_culex,female_culex,fed_culex,unfed_culex,gravid_culex,semi_gravid_culex,
                male_other_culex,female_other_culex,male_aedes,female_aedes,cluster_id,
                user_id,instanceID,created_at
            FROM temp_lab_sorter
            WHERE round=? AND user_id=? AND hhcode IN ($placeholdersMatched)
            ON DUPLICATE KEY UPDATE
                start=VALUES(start), end=VALUES(end), deviceid=VALUES(deviceid),
                ento_lab_frm_title=VALUES(ento_lab_frm_title), lab_date=VALUES(lab_date),
                srtname=VALUES(srtname), hhname=VALUES(hhname), field_coll_date=VALUES(field_coll_date),
                male_ag=VALUES(male_ag), female_ag=VALUES(female_ag), fed_ag=VALUES(fed_ag), unfed_ag=VALUES(unfed_ag),
                gravid_ag=VALUES(gravid_ag), semi_gravid_ag=VALUES(semi_gravid_ag),
                male_af=VALUES(male_af), female_af=VALUES(female_af), fed_af=VALUES(fed_af), unfed_af=VALUES(unfed_af),
                gravid_af=VALUES(gravid_af), semi_gravid_af=VALUES(semi_gravid_af),
                male_oan=VALUES(male_oan), female_oan=VALUES(female_oan), fed_oan=VALUES(fed_oan), unfed_oan=VALUES(unfed_oan),
                gravid_oan=VALUES(gravid_oan), semi_gravid_oan=VALUES(semi_gravid_oan),
                male_culex=VALUES(male_culex), female_culex=VALUES(female_culex), fed_culex=VALUES(fed_culex), unfed_culex=VALUES(unfed_culex),
                gravid_culex=VALUES(gravid_culex), semi_gravid_culex=VALUES(semi_gravid_culex),
                male_other_culex=VALUES(male_other_culex), female_other_culex=VALUES(female_other_culex),
                male_aedes=VALUES(male_aedes), female_aedes=VALUES(female_aedes), cluster_id=VALUES(cluster_id),
                user_id=VALUES(user_id), instanceID=VALUES(instanceID), created_at=VALUES(created_at)
        ");
        $stmtInsLab->execute(array_merge([$latestRound,$userId],$matchedHH));
    }

    // Mismatched: delete + insert to desk tables
    if(!empty($mismatchedHH)){
        $stmtDeskFieldDel = $pdo->prepare("DELETE FROM desk_field_collector WHERE round=? AND user_id=? AND hhcode IN ($placeholdersMismatched)");
        $stmtDeskFieldDel->execute(array_merge([$latestRound,$userId],$mismatchedHH));

        $stmtDeskLabDel = $pdo->prepare("DELETE FROM desk_lab_sorter WHERE round=? AND user_id=? AND hhcode IN ($placeholdersMismatched)");
        $stmtDeskLabDel->execute(array_merge([$latestRound,$userId],$mismatchedHH));

        $stmtDeskField = $pdo->prepare("
            INSERT INTO desk_field_collector (start,end,deviceid,ento_fld_frm_title,field_coll_date,
                fldrecname,clstname,clstid,clsttype_lst,round,hhcode,hhname,ddrln,aninsln,ddltwrk,
                ddltwrk_gcomment,lighttrapid,collectionbgid,instanceID,user_id,created_at)
            SELECT start,end,deviceid,ento_fld_frm_title,field_coll_date,fldrecname,clstname,clstid,clsttype_lst,round,hhcode,hhname,ddrln,aninsln,ddltwrk,
                ddltwrk_gcomment,lighttrapid,collectionbgid,instanceID,user_id,created_at
            FROM temp_field_collector
            WHERE round=? AND user_id=? AND hhcode IN ($placeholdersMismatched)
        ");
        $stmtDeskField->execute(array_merge([$latestRound,$userId],$mismatchedHH));

        $stmtDeskLab = $pdo->prepare("
            INSERT INTO desk_lab_sorter (start,end,deviceid,ento_lab_frm_title,lab_date,
                srtname,round,hhname,hhcode,field_coll_date,
                male_ag,female_ag,fed_ag,unfed_ag,gravid_ag,semi_gravid_ag,
                male_af,female_af,fed_af,unfed_af,gravid_af,semi_gravid_af,
                male_oan,female_oan,fed_oan,unfed_oan,gravid_oan,semi_gravid_oan,
                male_culex,female_culex,fed_culex,unfed_culex,gravid_culex,semi_gravid_culex,
                male_other_culex,female_other_culex,male_aedes,female_aedes,cluster_id,
                user_id,instanceID,created_at)
            SELECT start,end,deviceid,ento_lab_frm_title,lab_date,
                srtname,round,hhname,hhcode,field_coll_date,
                male_ag,female_ag,fed_ag,unfed_ag,gravid_ag,semi_gravid_ag,
                male_af,female_af,fed_af,unfed_af,gravid_af,semi_gravid_af,
                male_oan,female_oan,fed_oan,unfed_oan,gravid_oan,semi_gravid_oan,
                male_culex,female_culex,fed_culex,unfed_culex,gravid_culex,semi_gravid_culex,
                male_other_culex,female_other_culex,male_aedes,female_aedes,cluster_id,
                user_id,instanceID,created_at
            FROM temp_lab_sorter
            WHERE round=? AND user_id=? AND hhcode IN ($placeholdersMismatched)
        ");
        $stmtDeskLab->execute(array_merge([$latestRound,$userId],$mismatchedHH));
    }

    $pdo->commit();

    // Prepare preview data for JS
    $previewData = [];
    foreach($matchedHH as $hh){
        $previewData[] = ['hhcode'=>$hh,'status'=>'matched'];
    }
    foreach($mismatchedHH as $hh){
        $previewData[] = ['hhcode'=>$hh,'status'=>'mismatched'];
    }

    echo json_encode([
        "success"=>true,
        "message"=>"✅ Merge completed for round $latestRound. ".count($matchedHH)." matched, ".count($mismatchedHH)." mismatched.",
        "total_hh"=>count(array_unique(array_merge($hhFieldSet,$hhLabSet))),
        "matched_count"=>count($matchedHH),
        "mismatched_count"=>count($mismatchedHH),
        "matched_list"=>$matchedHH,
        "mismatched_list"=>$mismatchedHH,
        "preview"=>$previewData
    ]);

} catch(Exception $ex){
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(["success"=>false,"message"=>"❌ Merge failed: ".$ex->getMessage()]);
}


