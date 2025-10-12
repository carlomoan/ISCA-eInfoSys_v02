<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if(!checkPermission('view_dashboard')){
    http_response_code(403);
    echo json_encode(['error'=>'Access denied']);
    exit;
}

try {
    $columns = $pdo->query("SHOW COLUMNS FROM vw_merged_field_lab_data")->fetchAll(PDO::FETCH_COLUMN);

    $speciesDefs = [
        'ag'=>['label'=>'An. gambiae'],
        'af'=>['label'=>'An. funestus'],
        'oan'=>['label'=>'Other Anopheles'],
        'culex'=>['label'=>'Culex'],
        'other_culex'=>['label'=>'Other Culex'],
        'aedes'=>['label'=>'Aedes']
    ];
    $feedingStates = ['fed','unfed','gravid','semi_gravid'];

    // --- SUMMARY ---
    $total_clusters = (int)$pdo->query("SELECT COUNT(*) FROM clusters")->fetchColumn();
    $total_households = (int)$pdo->query("SELECT COUNT(DISTINCT hhcode) FROM vw_merged_field_lab_data")->fetchColumn();
    $total_rounds = (int)$pdo->query("SELECT COALESCE(MAX(`round`),0) FROM vw_merged_field_lab_data")->fetchColumn();

    // total records
    $sumPieces = [];
    foreach($speciesDefs as $spKey=>$sp){
        foreach(['male','female'] as $mf){
            $col = $mf.'_'.$spKey;
            if(in_array($col,$columns)) $sumPieces[] = "COALESCE($col,0)";
        }
    }
    $total_records = !empty($sumPieces) ? (int)$pdo->query("SELECT COALESCE(SUM(".implode("+",$sumPieces)."),0) FROM vw_merged_field_lab_data")->fetchColumn() : 0;

    // total mosquitoes (female)
    $femaleCols = [];
    foreach($speciesDefs as $spKey=>$sp){
        $col = 'female_'.$spKey;
        if(in_array($col,$columns)) $femaleCols[] = "COALESCE($col,0)";
    }
    $total_mosquitoes = !empty($femaleCols) ? (int)$pdo->query("SELECT COALESCE(SUM(".implode("+",$femaleCols)."),0) FROM vw_merged_field_lab_data")->fetchColumn() : 0;

    // --- HISTOGRAM ---
    $selectPieces=[];
    foreach($speciesDefs as $spKey=>$sp){
        $selectPieces[] = in_array("female_$spKey",$columns) ? "SUM(COALESCE(female_$spKey,0)) AS {$spKey}_female":"0 AS {$spKey}_female";
        foreach($feedingStates as $fs){
            $selectPieces[] = in_array("{$fs}_$spKey",$columns) ? "SUM(COALESCE({$fs}_$spKey,0)) AS {$spKey}_{$fs}" : "0 AS {$spKey}_{$fs}";
        }
    }
    $histRow = $pdo->query("SELECT ".implode(",",$selectPieces)." FROM vw_merged_field_lab_data")->fetch(PDO::FETCH_ASSOC);

    $histogram=[];
    foreach($speciesDefs as $spKey=>$sp){
        $histogram[$sp['label']]=[
            'fed'=>(int)($histRow[$spKey.'_fed']??0),
            'unfed'=>(int)($histRow[$spKey.'_unfed']??0),
            'gravid'=>(int)($histRow[$spKey.'_gravid']??0),
            'semi_gravid'=>(int)($histRow[$spKey.'_semi_gravid']??0),
            'female_total'=>(int)($histRow[$spKey.'_female']??0)
        ];
    }

    // --- SPECIES PER CLUSTER ---
    $clusterSelect=["COALESCE(clstname,'(Unknown)') AS clstname"];
    foreach($speciesDefs as $spKey=>$sp){
        $clusterSelect[] = in_array("female_$spKey",$columns) ? "SUM(COALESCE(female_$spKey,0)) AS female_$spKey":"0 AS female_$spKey";
    }
    $clusterRows=$pdo->query("SELECT ".implode(",",$clusterSelect)." FROM vw_merged_field_lab_data GROUP BY clstname ORDER BY clstname")->fetchAll(PDO::FETCH_ASSOC);

    $clusterLabels=[]; $clusterSeries=[];
    foreach($speciesDefs as $spKey=>$sp) $clusterSeries[$sp['label']]=[];
    foreach($clusterRows as $r){
        $clusterLabels[]=$r['clstname'];
        foreach($speciesDefs as $spKey=>$sp){
            $clusterSeries[$sp['label']][]=(int)($r['female_'.$spKey]??0);
        }
    }

    // --- TRENDING PER ROUND ---
    $trendCols=[];
    foreach($speciesDefs as $spKey=>$sp) if(in_array("female_$spKey",$columns)) $trendCols[]="COALESCE(female_$spKey,0)";
    $trendLabels=[]; $trendValues=[];
    if(!empty($trendCols)){
        $trendRows=$pdo->query("SELECT `round`, SUM(".implode("+",$trendCols).") AS total_female FROM vw_merged_field_lab_data GROUP BY `round` ORDER BY `round` ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach($trendRows as $tr){
            $trendLabels[]="Round ".$tr['round'];
            $trendValues[]=(int)$tr['total_female'];
        }
    }

    // --- HOUSEHOLDS TABLE (LATEST ROUND PER HH) ---
    $hhSelect=["v.hhcode","h.latitude","h.longitude"];
    foreach($speciesDefs as $spKey=>$sp){
        foreach(['male','female'] as $mf){
            $col = $mf.'_'.$spKey;
            $hhSelect[] = in_array($col,$columns)?"COALESCE(v.$col,0) AS $col":"0 AS $col";
        }
    }
    $hhRows=$pdo->query("
        SELECT * FROM (
            SELECT * FROM vw_merged_field_lab_data v
            WHERE (`hhcode`,`round`) IN (
                SELECT hhcode, MAX(`round`) FROM vw_merged_field_lab_data GROUP BY hhcode
            )
        ) t
        INNER JOIN households h ON t.hhcode=h.hhcode
        WHERE h.latitude IS NOT NULL AND h.longitude IS NOT NULL
        ORDER BY t.hhcode
    ")->fetchAll(PDO::FETCH_ASSOC);

    $householdTable=[];
    foreach($hhRows as $r){
        $speciesParts=[]; $totalMF=0;
        foreach($speciesDefs as $spKey=>$sp){
            $male=(int)($r['male_'.$spKey]??0);
            $female=(int)($r['female_'.$spKey]??0);
            $sum=$male+$female;
            if($sum>0) $speciesParts[]=strtoupper($spKey).": ".$sum;
            $totalMF+=$sum;
        }
        $householdTable[]=[
            'hhcode'=>$r['hhcode'],
            'species'=>implode(", ",$speciesParts),
            'total'=>$totalMF,
            'lat'=>(float)$r['latitude'],
            'lng'=>(float)$r['longitude']
        ];
    }

    // --- CLUSTER TOTALS ---
    $clusterTotals=[];
    foreach($clusterRows as $r){
        $sum=0;
        foreach($speciesDefs as $spKey=>$sp) $sum+=(int)($r['female_'.$spKey]??0);
        $clusterTotals[]=['cluster'=>$r['clstname'],'total'=>$sum];
    }

    echo json_encode([
        'summary'=>[
            'total_clusters'=>$total_clusters,
            'total_households'=>$total_households,
            'total_rounds'=>$total_rounds,
            'total_records'=>$total_records,
            'total_mosquitoes'=>$total_mosquitoes
        ],
        'histogram'=>$histogram,
        'clusterLabels'=>$clusterLabels,
        'clusterSeries'=>$clusterSeries,
        'trendLabels'=>$trendLabels,
        'trendValues'=>$trendValues,
        'householdTable'=>$householdTable,
        'clusterTotals'=>$clusterTotals
    ], JSON_UNESCAPED_UNICODE);

} catch(PDOException $e){
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
