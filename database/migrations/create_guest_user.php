<?php
/**
 * ================================================
 * CREATE GUEST USER ACCOUNT
 * ================================================
 * Purpose: Create Guest_1 user account with proper password hashing
 * Username: Guest_1
 * Password: Guest@2025
 * ================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db_connect.php';

try {
    // Check if guest user already exists
    $stmt = $pdo->prepare("SELECT id, fname, lname, email FROM users WHERE email = :email OR phone = :phone");
    $stmt->execute([
        ':email' => 'guest1@edatacolls.co.tz',
        ':phone' => '+255000000001'
    ]);

    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        echo "✓ Guest user already exists!\n";
        echo "  ID: {$existingUser['id']}\n";
        echo "  Name: {$existingUser['fname']} {$existingUser['lname']}\n";
        echo "  Email: {$existingUser['email']}\n\n";

        // Update password to ensure it's correct
        $hashedPassword = password_hash('Guest@2025', PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $updateStmt->execute([
            ':password' => $hashedPassword,
            ':id' => $existingUser['id']
        ]);
        echo "✓ Password updated to: Guest@2025\n";

    } else {
        // Create new guest user
        $hashedPassword = password_hash('Guest@2025', PASSWORD_DEFAULT);

        $insertStmt = $pdo->prepare("
            INSERT INTO users (fname, lname, email, phone, password, role_id, is_verified, user_project_id, created_at)
            VALUES (:fname, :lname, :email, :phone, :password, :role_id, :is_verified, :user_project_id, NOW())
        ");

        $insertStmt->execute([
            ':fname' => 'Guest',
            ':lname' => '1',
            ':email' => 'guest1@edatacolls.co.tz',
            ':phone' => '+255000000001',
            ':password' => $hashedPassword,
            ':role_id' => 0,
            ':is_verified' => 1,
            ':user_project_id' => 0
        ]);

        $newUserId = $pdo->lastInsertId();

        echo "✓ Guest user created successfully!\n";
        echo "  ID: {$newUserId}\n";
        echo "  Username/Email: guest1@edatacolls.co.tz\n";
        echo "  Password: Guest@2025\n";
        echo "  Phone: +255000000001\n";
    }

    echo "\n=================================================\n";
    echo "LOGIN CREDENTIALS:\n";
    echo "=================================================\n";
    echo "Email: guest1@edatacolls.co.tz\n";
    echo "Password: Guest@2025\n";
    echo "=================================================\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
