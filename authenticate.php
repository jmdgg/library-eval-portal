<?php
/**
 * authenticate.php
 * Secure login handler for the Admin Portal.
 */

// 1. Start a secure session
session_start();

// If your db_connect.php is in the root folder, adjust this path (e.g., '../db_connect.php')
require_once 'db_connect.php';

// 2. Reject non-POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php?error=invalid_request");
    exit;
}

try {
    // 3. Extract and sanitize inputs
    // We trim whitespace just in case the user accidentally copied a space
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception("empty_fields");
    }

    // 4. Fetch the user from the database
    $stmt = $pdo->prepare("SELECT * FROM admin_user WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. Verify User Exists AND Password Matches
    // We use PHP's native, highly secure password_verify() to check against the hash
    if (!$user || !password_verify($password, $user['password_hash'])) {
        // We throw a generic error. NEVER tell the user "Username not found" or "Wrong password".
        // Always be vague so hackers can't guess usernames.
        throw new Exception("invalid_credentials");
    }

    // 6. Check if the account is active
    if ($user['is_active'] == 0) {
        throw new Exception("account_disabled");
    }

    // 7. Success! Establish the Session
    // We regenerate the session ID to prevent Session Fixation attacks
    session_regenerate_id(true);

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user['admin_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['department_id'] = $user['department_id']; // Will be NULL for Super Admins
    $_SESSION['is_superadmin'] = $user['is_superadmin'];

    // 8. Update their Last Login Timestamp
    $update_stmt = $pdo->prepare("UPDATE admin_user SET last_login = NOW() WHERE admin_id = ?");
    $update_stmt->execute([$user['admin_id']]);

    // 9. Redirect to the Dashboard
    header("Location: index.php");
    exit;

} catch (Exception $e) {
    // Redirect back to login with a specific error flag for the UI to display
    $error_code = $e->getMessage();
    header("Location: login.php?error=" . urlencode($error_code));
    exit;
}