<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'get_users') {
        $sql = "SELECT u.id, CONCAT(u.fname, ' ', u.lname) AS fullname, u.email, u.phone, 
                       u.role_id, r.role_name, 
                       IF(u.is_verified=1,'active','inactive') as status
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id";
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as &$user) {
            $stmtP = $pdo->prepare("SELECT p.id as project_id, p.project_name 
                                    FROM user_projects up 
                                    JOIN projects p ON up.project_id=p.id 
                                    WHERE up.user_id=?");
            $stmtP->execute([$user['id']]);
            $user['projects'] = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(["success" => true, "data" => $users]);
    }

    elseif ($action === 'get_user') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmtP = $pdo->prepare("SELECT p.id as project_id, p.project_name 
                                FROM user_projects up 
                                JOIN projects p ON up.project_id=p.id 
                                WHERE up.user_id=?");
        $stmtP->execute([$id]);
        $user['projects'] = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $user]);
    }

    elseif ($action === 'get_roles') {
        $stmt = $pdo->query("SELECT id, role_name FROM roles");
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'get_projects') {
        $stmt = $pdo->query("SELECT id, project_name FROM projects");
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'update_user') {
        $id = $_POST['id'];
        $role_id = $_POST['role_id'];
        $projects = $_POST['projects'] ?? [];

        $stmt = $pdo->prepare("UPDATE users SET role_id=? WHERE id=?");
        $stmt->execute([$role_id, $id]);

        $pdo->prepare("DELETE FROM user_projects WHERE user_id=?")->execute([$id]);
        foreach ($projects as $pid) {
            $pdo->prepare("INSERT INTO user_projects (user_id, project_id) VALUES (?,?)")
                ->execute([$id, $pid]);
        }

        echo json_encode(["success" => true, "message" => "User updated successfully"]);
    }

    elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $newStatus = $status === "active" ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE users SET is_verified=? WHERE id=?");
        $stmt->execute([$newStatus, $id]);

        echo json_encode(["success" => true, "message" => "User status updated"]);
    }

    elseif ($action === 'delete_user') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true, "message" => "User deleted"]);
    }

    else {
        echo json_encode(["success" => false, "message" => "Invalid action"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
