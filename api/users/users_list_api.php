<?php
require_once __DIR__ . '/../../config/db_connect.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.fname,
            u.lname,
            u.email,
            u.phone,
            u.role_id,
            r.name AS role_name,
            u.is_verified,
            u.is_admin,
            u.lab_tech_id,
            CONCAT(ltu.fname,' ', ltu.lname) AS lab_tech_name,
            GROUP_CONCAT(DISTINCT p.project_id) AS project_ids,
            GROUP_CONCAT(DISTINCT p.project_name) AS project_names,
            GROUP_CONCAT(DISTINCT uc.cluster_id) AS cluster_ids,
            GROUP_CONCAT(DISTINCT c.cluster_name) AS cluster_names,
            u.created_at,
            u.updated_at
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN lab_technicians lt ON u.lab_tech_id = lt.lab_tech_id
        LEFT JOIN users ltu ON lt.user_id = ltu.id
        LEFT JOIN user_projects up ON u.id = up.user_id
        LEFT JOIN projects p ON up.project_id = p.project_id
        LEFT JOIN user_clusters uc ON u.id = uc.user_id
        LEFT JOIN clusters c ON uc.cluster_id = c.cluster_id
        GROUP BY u.id
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $users = [];

    foreach($rows as $row){
        // Projects
        $projects = [];
        if(!empty($row['project_ids'])){
            $ids = explode(',', $row['project_ids']);
            $names = explode(',', $row['project_names']);
            foreach($ids as $i => $pid){
                $projects[] = ['id' => (int)$pid, 'name' => $names[$i] ?? ''];
            }
        }

        // Clusters
        $clusters = [];
        if(!empty($row['cluster_ids'])){
            $ids = explode(',', $row['cluster_ids']);
            $names = explode(',', $row['cluster_names']);
            foreach($ids as $i => $cid){
                $clusters[] = ['id' => $cid, 'name' => $names[$i] ?? ''];
            }
        }

        $users[] = [
            'id' => (int)$row['id'],
            'fname' => $row['fname'],
            'lname' => $row['lname'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'role_id' => (int)$row['role_id'],
            'role_name' => $row['role_name'],
            'is_verified' => (bool)$row['is_verified'],
            'is_admin' => (bool)$row['is_admin'],
            'lab_tech_id' => $row['lab_tech_id'],
            'lab_tech_name' => $row['lab_tech_name'],
            'projects' => $projects,
            'clusters' => $clusters,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    echo json_encode(['success' => true, 'users' => $users]);
} catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
