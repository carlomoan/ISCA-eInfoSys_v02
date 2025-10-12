<?php
session_start();
require_once('../config/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$allowedRoles = ['data_entrants', 'field_collector'];
$stmtRole = $pdo->prepare("SELECT name FROM roles WHERE id = (SELECT role_id FROM users WHERE id = ?)");
$stmtRole->execute([$userId]);
$role = $stmtRole->fetchColumn();

if (!in_array($role, $allowedRoles)) {
    die("You don't have permission to input Field Collector data.");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs here (example)
    $start = $_POST['start'] ?? null;
    $end = $_POST['end'] ?? null;
    $deviceid = $_POST['deviceid'] ?? null;
    $ento_fld_frm_title = $_POST['ento_fld_frm_title'] ?? null;
    $field_coll_date = $_POST['field_coll_date'] ?? null;
    $fldrecname = $_POST['fldrecname'] ?? null;
    $clstname = $_POST['clstname'] ?? null;
    $clstid = $_POST['clstid'] ?? null;
    $clsttype_lst = $_POST['clsttype_lst'] ?? null;
    $round = $_POST['round'] ?? null;
    $hhcode = $_POST['hhcode'] ?? null;
    $hhname = $_POST['hhname'] ?? null;
    $ddrln = $_POST['ddrln'] ?? null;
    $aninsln = $_POST['aninsln'] ?? null;
    $ddltwrk = $_POST['ddltwrk'] ?? null;
    $ddltwrk_gcomment = $_POST['ddltwrk_gcomment'] ?? null;
    $lighttrapid = $_POST['lighttrapid'] ?? null;
    $collectionbgid = $_POST['collectionbgid'] ?? null;
    $instanceID = $_POST['instanceID'] ?? null;

    // Insert into DB
    $sql = "INSERT INTO field_collector (
        start, end, deviceid, ento_fld_frm_title, field_coll_date,
        fldrecname, clstname, clstid,
        clsttype_lst, round, hhcode, hhname,
        ddrln, aninsln, ddltwrk, ddltwrk_gcomment, lighttrapid,
        collectionbgid, instanceID, user_id
    ) VALUES (
        :start, :end, :deviceid, :ento_fld_frm_title, :field_coll_date,
        :fldrecname, :clstname, :clstid,
        :clsttype_lst, :round, :hhcode, :hhname,
        :ddrln, :aninsln, :ddltwrk, :ddltwrk_gcomment, :lighttrapid,
        :collectionbgid, :instanceID, :user_id
    )";

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        ':start' => $start,
        ':end' => $end,
        ':deviceid' => $deviceid,
        ':ento_fld_frm_title' => $ento_fld_frm_title,
        ':field_coll_date' => $field_coll_date,
        ':fldrecname' => $fldrecname,
        ':clstname' => $clstname,
        ':clstid' => $clstid,
        ':clsttype_lst' => $clsttype_lst,
        ':round' => $round,
        ':hhcode' => $hhcode,
        ':hhname' => $hhname,
        ':ddrln' => $ddrln,
        ':aninsln' => $aninsln,
        ':ddltwrk' => $ddltwrk,
        ':ddltwrk_gcomment' => $ddltwrk_gcomment,
        ':lighttrapid' => $lighttrapid,
        ':collectionbgid' => $collectionbgid,
        ':instanceID' => $instanceID,
        ':user_id' => $userId
    ]);

    if ($success) {
        $message = "Data entered successfully.";
    } else {
        $message = "Failed to enter data.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Field Collector Input Form</title></head>
<body>
    <h1>Enter Field Collector Data</h1>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Start: <input type="datetime-local" name="start" required></label><br>
        <label>End: <input type="datetime-local" name="end" required></label><br>
        <label>Device ID: <input type="text" name="deviceid" required></label><br>
        <label>Form Title: <input type="text" name="ento_fld_frm_title"></label><br>
        <label>Field Collection Date: <input type="date" name="field_coll_date" required></label><br>
        <label>Field Recorder Name: <input type="text" name="fldrecname" required></label><br>
        <label>Cluster Name: <input type="text" name="clstname" required></label><br>
        <label>Cluster ID: <input type="text" name="clstid" required></label><br>
        <label>Cluster Type: <input type="text" name="clsttype_lst" required></label><br>
        <label>Round: <input type="number" name="round" required></label><br>
        <label>Household Code: <input type="text" name="hhcode" required></label><br>
        <label>Household Name: <input type="text" name="hhname" required></label><br>
        <label>DDRLN: <input type="text" name="ddrln"></label><br>
        <label>Aninsln: <input type="text" name="aninsln"></label><br>
        <label>DDLTWRK: <input type="text" name="ddltwrk"></label><br>
        <label>DDLTWRK Comment: <input type="text" name="ddltwrk_gcomment"></label><br>
        <label>Light Trap ID: <input type="number" name="lighttrapid"></label><br>
        <label>Collection Bag ID: <input type="number" name="collectionbgid"></label><br>
        <label>Instance ID: <input type="text" name="instanceID" required></label><br>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
