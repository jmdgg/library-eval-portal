<?php
/**
 * admin_actions.php
 * Centralized handler for Superadmin-only administrative operations.
 */
session_start();
require_once '../db_connect.php';
require_once '../config.php';

// 1. Authorization Check (Must be logged in as Superadmin)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$current_admin_id = $_SESSION['admin_id'];
$user_stmt = $pdo->prepare("SELECT role, password_hash FROM admin_user WHERE admin_id = ?");
$user_stmt->execute([$current_admin_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user || $current_user['role'] !== 'superadmin') {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient privileges.']);
    exit;
}

$action = $_POST['action'] ?? '';

// 2. Kill Switch (AJAX Status Toggle) - No Sudo Required
if ($action === 'toggle_status') {
    $target_id = (int)$_POST['target_id'];
    $is_active = (int)$_POST['is_active'];

    // Cannot suspend self
    if ($target_id === $current_admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot suspend own account.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE admin_user SET is_active = ? WHERE admin_id = ?");
        $stmt->execute([$is_active, $target_id]);

        // Audit Log
        $target_name_stmt = $pdo->prepare("SELECT username FROM admin_user WHERE admin_id = ?");
        $target_name_stmt->execute([$target_id]);
        $target_name = $target_name_stmt->fetchColumn();

        $detail = ($is_active ? "Activated" : "Suspended") . " user account: " . $target_name;
        $audit_stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'USER_STATUS_CHANGE', ?, ?)");
        $audit_stmt->execute([$current_admin_id, $detail, $_SERVER['REMOTE_ADDR']]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 3. Sudo Operations (Create & Delete)
if (in_array($action, ['create_user', 'delete_user'])) {
    
    // SUDO VERIFICATION
    $sudo_password = $_POST['sudo_password'] ?? '';
    if (!password_verify($sudo_password, $current_user['password_hash'])) {
        if (isset($_POST['is_ajax'])) {
            echo json_encode(['status' => 'error', 'message' => 'Sudo Verification Failed: Incorrect password.']);
        } else {
            header("Location: settings.php?error=" . urlencode("Sudo Verification Failed: Incorrect password."));
        }
        exit;
    }

    if ($action === 'create_user') {
        $new_username = trim($_POST['new_username']);
        $new_email = trim($_POST['new_email']);
        $new_password = $_POST['new_password'];

        if (empty($new_username) || empty($new_password)) {
            header("Location: settings.php?error=empty_fields");
            exit;
        }

        try {
            $pdo->beginTransaction();
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO admin_user (username, email, password_hash, role, is_active) VALUES (?, ?, ?, 'subadmin', 1)");
            $stmt->execute([$new_username, $new_email, $hashed]);
            
            // Audit Log
            $detail = "Created new subadmin account: " . $new_username;
            $audit_stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'USER_CREATE', ?, ?)");
            $audit_stmt->execute([$current_admin_id, $detail, $_SERVER['REMOTE_ADDR']]);
            
            $pdo->commit();
            header("Location: settings.php?success=" . urlencode("Subadmin '$new_username' provisioned successfully."));
        } catch (PDOException $e) {
            $pdo->rollBack();
            header("Location: settings.php?error=" . urlencode($e->getMessage()));
        }
    }

    if ($action === 'delete_user') {
        $target_id = (int)$_POST['target_id'];

        if ($target_id === $current_admin_id) {
            header("Location: settings.php?error=cannot_delete_self");
            exit;
        }

        try {
            $pdo->beginTransaction();
            
            // Get name for audit
            $name_stmt = $pdo->prepare("SELECT username FROM admin_user WHERE admin_id = ?");
            $name_stmt->execute([$target_id]);
            $target_name = $name_stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM admin_user WHERE admin_id = ?");
            $stmt->execute([$target_id]);

            // Audit Log
            $detail = "Deleted user account: " . $target_name;
            $audit_stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'USER_DELETE', ?, ?)");
            $audit_stmt->execute([$current_admin_id, $detail, $_SERVER['REMOTE_ADDR']]);

            $pdo->commit();
            header("Location: settings.php?success=" . urlencode("Account deleted successfully."));
        } catch (PDOException $e) {
            $pdo->rollBack();
            header("Location: settings.php?error=" . urlencode($e->getMessage()));
        }
    }
}
