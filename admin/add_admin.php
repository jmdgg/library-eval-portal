<?php
/**
 * add_admin.php
 * Securely hashes passwords, creates new standard admins, and triggers Audit Log.
 */

session_start();
require_once '../db_connect.php';

// Security Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Unauthorized Access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: settings.php?error=empty_fields");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new admin into the normalized 3NF admin_user table
        $stmt = $pdo->prepare("
            INSERT INTO admin_user (username, password_hash, is_active) 
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$username, $hashed_password]);

        // Trigger the Audit Log
        $action_details = "Provisioned new administrator account: " . $username;
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_log (admin_id, action_type, action_details, ip_address) 
            VALUES (?, 'USER_CREATE', ?, ?)
        ");
        $audit_stmt->execute([$_SESSION['admin_id'], $action_details, $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();
        
        header("Location: settings.php?success=user_created");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        if ($e->errorInfo[1] == 1062) {
            header("Location: settings.php?error=username_taken");
        } else {
            die("Database Error: " . $e->getMessage());
        }
    }
}
