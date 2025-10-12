<?php
// upload_csv.php

$host = '127.0.0.1';
$db   = 'survey_amrc_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Tafadhali chagua file la CSV.";
    } else {
        $file = $_FILES['csv_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Kuna tatizo wakati wa kupakia file. Error code: " . $file['error'];
        } else {
            $maxFileSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxFileSize) {
                $errors[] = "File ni kubwa zaidi ya ukubwa unaoruhusiwa (2MB).";
            }

            $allowedExtensions = ['csv'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedMimeTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "File lazima iwe na extension .csv";
            }
            if (!in_array($mimeType, $allowedMimeTypes)) {
                $errors[] = "File sio aina ya CSV halali. Mime type: $mimeType";
            }

            if (filesize($file['tmp_name']) === 0) {
                $errors[] = "File la CSV ni tupu.";
            }
        }
    }

    if (empty($errors)) {
        $uploadType = $_POST['upload_type'] ?? '';
        if (!in_array($uploadType, ['field_collector', 'lab_sorter'])) {
            $errors[] = "Aina ya upload si sahihi.";
        } else {
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                $header = fgetcsv($handle);
                if (!$header) {
                    $errors[] = "CSV haina data au header sahihi.";
                } else {
                    // Normalize header to lowercase and trim
                    $header = array_map('trim', array_map('strtolower', $header));
                    
                    // Prepare insert statement based on upload type
                    if ($uploadType === 'field_collector') {
                        // Expected fields for field_collector (make sure CSV header matches these)
                        $expectedFields = [
                            'start', 'end', 'deviceid', 'ento_fld_frm_title', 'field_coll_date', 'fldrecname', 
                            'clstname', 'clstid', 'clsttype_lst', 'round', 'hhcode', 'hhname', 'ddrln', 'aninsln',
                            'ddltwrk', 'ddltwrk_gcomment', 'lighttrapid', 'collectionbgid', 'instanceid', 'user_id'
                        ];

                        if (array_diff($expectedFields, $header)) {
                            $errors[] = "CSV ya field_collector haina sehemu zote zinazohitajika.";
                        } else {
                            // Prepare insert SQL
                            $sql = "INSERT INTO field_collector 
                            (start, end, deviceid, ento_fld_frm_title, field_coll_date, fldrecname, clstname, clstid, clsttype_lst, round, hhcode, hhname, ddrln, aninsln, ddltwrk, ddltwrk_gcomment, lighttrapid, collectionbgid, instanceID, user_id) 
                            VALUES 
                            (:start, :end, :deviceid, :ento_fld_frm_title, :field_coll_date, :fldrecname, :clstname, :clstid, :clsttype_lst, :round, :hhcode, :hhname, :ddrln, :aninsln, :ddltwrk, :ddltwrk_gcomment, :lighttrapid, :collectionbgid, :instanceID, :user_id)";
                            $stmt = $pdo->prepare($sql);

                            $rowCount = 0;
                            while (($row = fgetcsv($handle)) !== FALSE) {
                                $data = array_combine($header, $row);

                                // Convert numeric fields as needed (optional)
                                $data['round'] = (int)$data['round'];
                                $data['lighttrapid'] = (int)$data['lighttrapid'];
                                $data['collectionbgid'] = (int)$data['collectionbgid'];
                                $data['user_id'] = (int)$data['user_id'];

                                // Execute insert
                                $stmt->execute([
                                    ':start' => $data['start'],
                                    ':end' => $data['end'],
                                    ':deviceid' => $data['deviceid'],
                                    ':ento_fld_frm_title' => $data['ento_fld_frm_title'],
                                    ':field_coll_date' => $data['field_coll_date'],
                                    ':fldrecname' => $data['fldrecname'],
                                    ':clstname' => $data['clstname'],
                                    ':clstid' => $data['clstid'],
                                    ':clsttype_lst' => $data['clsttype_lst'],
                                    ':round' => $data['round'],
                                    ':hhcode' => $data['hhcode'],
                                    ':hhname' => $data['hhname'],
                                    ':ddrln' => $data['ddrln'],
                                    ':aninsln' => $data['aninsln'],
                                    ':ddltwrk' => $data['ddltwrk'],
                                    ':ddltwrk_gcomment' => $data['ddltwrk_gcomment'],
                                    ':lighttrapid' => $data['lighttrapid'],
                                    ':collectionbgid' => $data['collectionbgid'],
                                    ':instanceID' => $data['instanceid'],
                                    ':user_id' => $data['user_id'],
                                ]);
                                $rowCount++;
                            }

                            echo "<p>Field Collector data imeingizwa. Idadi ya rekodi: $rowCount</p>";
                        }
                    } elseif ($uploadType === 'lab_sorter') {
                        // Expected fields for lab_sorter (make sure CSV header matches these)
                        $expectedFields = [
                            'start', 'end', 'deviceid', 'ento_lab_frm_title', 'lab_date', 'srtname', 'round', 'hhname', 'hhcode', 'field_coll_date',
                            'male_ag', 'female_ag', 'fed_ag', 'unfed_ag', 'gravid_ag', 'semi_gravid_ag',
                            'male_af', 'female_af', 'fed_af', 'unfed_af', 'gravid_af', 'semi_gravid_af',
                            'male_oan', 'female_oan', 'fed_oan', 'unfed_oan', 'gravid_oan', 'semi_gravid_oan',
                            'male_culex', 'female_culex', 'fed_culex', 'unfed_culex', 'gravid_culex', 'semi_gravid_culex',
                            'male_other_culex', 'female_other_culex', 'male_aedes', 'female_aedes', 'instanceid', 'user_id'
                        ];

                        if (array_diff($expectedFields, $header)) {
                            $errors[] = "CSV ya lab_sorter haina sehemu zote zinazohitajika.";
                        } else {
                            // Prepare insert SQL
                            $sql = "INSERT INTO lab_sorter
                            (start, end, deviceid, ento_lab_frm_title, lab_date, srtname, round, hhname, hhcode, field_coll_date,
                            male_ag, female_ag, fed_ag, unfed_ag, gravid_ag, semi_gravid_ag,
                            male_af, female_af, fed_af, unfed_af, gravid_af, semi_gravid_af,
                            male_oan, female_oan, fed_oan, unfed_oan, gravid_oan, semi_gravid_oan,
                            male_culex, female_culex, fed_culex, unfed_culex, gravid_culex, semi_gravid_culex,
                            male_other_culex, female_other_culex, male_aedes, female_aedes, instanceID, user_id)
                            VALUES
                            (:start, :end, :deviceid, :ento_lab_frm_title, :lab_date, :srtname, :round, :hhname, :hhcode, :field_coll_date,
                            :male_ag, :female_ag, :fed_ag, :unfed_ag, :gravid_ag, :semi_gravid_ag,
                            :male_af, :female_af, :fed_af, :unfed_af, :gravid_af, :semi_gravid_af,
                            :male_oan, :female_oan, :fed_oan, :unfed_oan, :gravid_oan, :semi_gravid_oan,
                            :male_culex, :female_culex, :fed_culex, :unfed_culex, :gravid_culex, :semi_gravid_culex,
                            :male_other_culex, :female_other_culex, :male_aedes, :female_aedes, :instanceID, :user_id)";
                            $stmt = $pdo->prepare($sql);

                            $rowCount = 0;
                            while (($row = fgetcsv($handle)) !== FALSE) {
                                $data = array_combine($header, $row);

                                // Convert some fields to int
                                $data['round'] = (int)$data['round'];
                                $data['male_ag'] = (int)$data['male_ag'];
                                $data['female_ag'] = (int)$data['female_ag'];
                                $data['fed_ag'] = (int)$data['fed_ag'];
                                $data['unfed_ag'] = (int)$data['unfed_ag'];
                                $data['gravid_ag'] = (int)$data['gravid_ag'];
                                $data['semi_gravid_ag'] = (int)$data['semi_gravid_ag'];
                                $data['male_af'] = (int)$data['male_af'];
                                $data['female_af'] = (int)$data['female_af'];
                                $data['fed_af'] = (int)$data['fed_af'];
                                $data['unfed_af'] = (int)$data['unfed_af'];
                                $data['gravid_af'] = (int)$data['gravid_af'];
                                $data['semi_gravid_af'] = (int)$data['semi_gravid_af'];
                                $data['male_oan'] = (int)$data['male_oan'];
                                $data['female_oan'] = (int)$data['female_oan'];
                                $data['fed_oan'] = (int)$data['fed_oan'];
                                $data['unfed_oan'] = (int)$data['unfed_oan'];
                                $data['gravid_oan'] = (int)$data['gravid_oan'];
                                $data['semi_gravid_oan'] = (int)$data['semi_gravid_oan'];
                                $data['male_culex'] = (int)$data['male_culex'];
                                $data['female_culex'] = (int)$data['female_culex'];
                                $data['fed_culex'] = (int)$data['fed_culex'];
                                $data['unfed_culex'] = (int)$data['unfed_culex'];
                                $data['gravid_culex'] = (int)$data['gravid_culex'];
                                $data['semi_gravid_culex'] = (int)$data['semi_gravid_culex'];
                                $data['male_other_culex'] = (int)$data['male_other_culex'];
                                $data['female_other_culex'] = (int)$data['female_other_culex'];
                                $data['male_aedes'] = (int)$data['male_aedes'];
                                $data['female_aedes'] = (int)$data['female_aedes'];
                                $data['user_id'] = (int)$data['user_id'];

                                $stmt->execute([
                                    ':start' => $data['start'],
                                    ':end' => $data['end'],
                                    ':deviceid' => $data['deviceid'],
                                    ':ento_lab_frm_title' => $data['ento_lab_frm_title'],
                                    ':lab_date' => $data['lab_date'],
                                    ':srtname' => $data['srtname'],
                                    ':round' => $data['round'],
                                    ':hhname' => $data['hhname'],
                                    ':hhcode' => $data['hhcode'],
                                    ':field_coll_date' => $data['field_coll_date'],
                                    ':male_ag' => $data['male_ag'],
                                    ':female_ag' => $data['female_ag'],
                                    ':fed_ag' => $data['fed_ag'],
                                    ':unfed_ag' => $data['unfed_ag'],
                                    ':gravid_ag' => $data['gravid_ag'],
                                    ':semi_gravid_ag' => $data['semi_gravid_ag'],
                                    ':male_af' => $data['male_af'],
                                    ':female_af' => $data['female_af'],
                                    ':fed_af' => $data['fed_af'],
                                    ':unfed_af' => $data['unfed_af'],
                                    ':gravid_af' => $data['gravid_af'],
                                    ':semi_gravid_af' => $data['semi_gravid_af'],
                                    ':male_oan' => $data['male_oan'],
                                    ':female_oan' => $data['female_oan'],
                                    ':fed_oan' => $data['fed_oan'],
                                    ':unfed_oan' => $data['unfed_oan'],
                                    ':gravid_oan' => $data['gravid_oan'],
                                    ':semi_gravid_oan' => $data['semi_gravid_oan'],
                                    ':male_culex' => $data['male_culex'],
                                    ':female_culex' => $data['female_culex'],
                                    ':fed_culex' => $data['fed_culex'],
                                    ':unfed_culex' => $data['unfed_culex'],
                                    ':gravid_culex' => $data['gravid_culex'],
                                    ':semi_gravid_culex' => $data['semi_gravid_culex'],
                                    ':male_other_culex' => $data['male_other_culex'],
                                    ':female_other_culex' => $data['female_other_culex'],
                                    ':male_aedes' => $data['male_aedes'],
                                    ':female_aedes' => $data['female_aedes'],
                                    ':instanceID' => $data['instanceid'],
                                    ':user_id' => $data['user_id'],
                                ]);
                                $rowCount++;
                            }

                            echo "<p>Lab Sorter data imeingizwa. Idadi ya rekodi: $rowCount</p>";
                        }
                    }
                }
                fclose($handle);
            } else {
                $errors[] = "Imeshindikana kufungua file ya CSV.";
            }
        }
    }

    if (!empty($errors)) {
        echo "<ul style='color:red;'>";
        foreach ($errors as $err) {
            echo "<li>" . htmlspecialchars($err) . "</li>";
        }
        echo "</ul>";
    }
}
?>

<!-- Simple upload form -->
<form method="POST" enctype="multipart/form-data">
    <label>Chagua aina ya upload:</label>
    <select name="upload_type" required>
        <option value="">-- Chagua --</option>
        <option value="field_collector">Field Collector</option>
        <option value="lab_sorter">Lab Sorter</option>
    </select><br><br>

    <label>Chagua file la CSV:</label>
    <input type="file" name="csv_file" accept=".csv" required><br><br>

    <button type="submit">Upload CSV</button>
</form>


