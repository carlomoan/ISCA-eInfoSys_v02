<?php
/**
 * ================================================
 * Create Superuser Admin Role Script
 * ================================================
 *
 * Creates a superuser role with all permissions
 * and assigns it to specified email
 *
 * Usage: php scripts/create_superuser.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../classes/Permission.php';

echo "╔════════════════════════════════════════╗" . PHP_EOL;
echo "║     Create Superuser Admin Script     ║" . PHP_EOL;
echo "╚════════════════════════════════════════╝" . PHP_EOL . PHP_EOL;

$targetEmail = 'Hbaraka2010@gmail.com';
$permission = new Permission($pdo);

try {
    $pdo->beginTransaction();

    // Step 1: Check if Superuser role exists
    echo "Step 1: Checking for Superuser role..." . PHP_EOL;
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Superuser'");
    $stmt->execute();
    $superuserRole = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($superuserRole) {
        $roleId = $superuserRole['id'];
        echo "   ✓ Superuser role exists (ID: $roleId)" . PHP_EOL;
    } else {
        // Create Superuser role
        $stmt = $pdo->prepare("INSERT INTO roles (name) VALUES ('Superuser')");
        $stmt->execute();
        $roleId = $pdo->lastInsertId();
        echo "   ✓ Created Superuser role (ID: $roleId)" . PHP_EOL;
    }

    // Step 2: Get all permissions
    echo PHP_EOL . "Step 2: Getting all permissions..." . PHP_EOL;
    $allPermissions = $permission->getAllPermissions();
    $permissionCount = count($allPermissions);
    echo "   ✓ Found $permissionCount permissions" . PHP_EOL;

    // Step 3: Assign all permissions to Superuser role
    echo PHP_EOL . "Step 3: Assigning all permissions to Superuser role..." . PHP_EOL;

    // First, clear existing permissions for this role
    $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);

    // Now assign all permissions
    $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    $assignedCount = 0;

    foreach ($allPermissions as $perm) {
        $stmt->execute([$roleId, $perm['id']]);
        $assignedCount++;
    }

    echo "   ✓ Assigned $assignedCount permissions to Superuser role" . PHP_EOL;

    // Step 4: Create manage_roles permission if it doesn't exist
    echo PHP_EOL . "Step 4: Ensuring manage_roles permission exists..." . PHP_EOL;

    if (!$permission->permissionExists('manage_roles')) {
        $permission->createPermission('manage_roles', 'Manage Roles and Permissions', 'admin');
        echo "   ✓ Created manage_roles permission" . PHP_EOL;

        // Assign to Superuser role
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE name = 'manage_roles'");
        $stmt->execute();
        $manageRolesPerm = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($manageRolesPerm) {
            $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            $stmt->execute([$roleId, $manageRolesPerm['id']]);
            echo "   ✓ Assigned manage_roles to Superuser role" . PHP_EOL;
        }
    } else {
        echo "   ✓ manage_roles permission already exists" . PHP_EOL;
    }

    // Step 5: Find user by email
    echo PHP_EOL . "Step 5: Finding user $targetEmail..." . PHP_EOL;
    $stmt = $pdo->prepare("SELECT id, fname, lname, email, is_admin FROM users WHERE email = ?");
    $stmt->execute([$targetEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User with email $targetEmail not found!");
    }

    $userId = $user['id'];
    $userName = $user['fname'] . ' ' . $user['lname'];
    echo "   ✓ Found user: $userName (ID: $userId)" . PHP_EOL;

    // Step 6: Update user's role to Superuser
    echo PHP_EOL . "Step 6: Assigning Superuser role to user..." . PHP_EOL;

    // Update user's role_id
    $stmt = $pdo->prepare("UPDATE users SET role_id = ?, is_admin = 1 WHERE id = ?");
    $stmt->execute([$roleId, $userId]);
    echo "   ✓ Updated user's role_id to $roleId" . PHP_EOL;

    // Delete existing user_roles entries for this user
    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Insert new user_roles entry
    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->execute([$userId, $roleId]);
    echo "   ✓ Updated user_roles entry" . PHP_EOL;

    // Step 7: Set is_verified = 1
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    echo "   ✓ Set user as verified" . PHP_EOL;

    // Commit transaction
    $pdo->commit();

    // Summary
    echo PHP_EOL . "════════════════════════════════════════" . PHP_EOL;
    echo "✅ SUCCESS!" . PHP_EOL;
    echo "════════════════════════════════════════" . PHP_EOL;
    echo "User Details:" . PHP_EOL;
    echo "  Email: $targetEmail" . PHP_EOL;
    echo "  Name: $userName" . PHP_EOL;
    echo "  Role: Superuser (ID: $roleId)" . PHP_EOL;
    echo "  Admin: Yes" . PHP_EOL;
    echo "  Permissions: ALL ($assignedCount permissions)" . PHP_EOL;
    echo "════════════════════════════════════════" . PHP_EOL . PHP_EOL;

    echo "🎉 User can now login with superuser privileges!" . PHP_EOL;
    echo "📧 Login at: " . BASE_URL . "/login.php" . PHP_EOL;
    echo "🔑 Email: $targetEmail" . PHP_EOL;
    echo PHP_EOL;

    echo "✨ Available Actions:" . PHP_EOL;
    echo "  • Access all pages in the system" . PHP_EOL;
    echo "  • Manage roles and permissions" . PHP_EOL;
    echo "  • Add/edit/delete all data" . PHP_EOL;
    echo "  • View all reports and analytics" . PHP_EOL;
    echo "  • Configure system settings" . PHP_EOL;
    echo PHP_EOL;

} catch (Exception $e) {
    $pdo->rollBack();
    echo PHP_EOL . "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Transaction rolled back. No changes made." . PHP_EOL;
    exit(1);
}
