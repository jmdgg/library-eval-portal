<?php
/**
 * run this ONCE in your browser, then DELETE IT immediately.
 */
require_once 'db_connect.php';

$username = 'admin';
$password = 'admin123'; // Change this to whatever you want

// This generates a secure Bcrypt hash
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO admin_user (username, password_hash, is_superadmin, is_active) 
    VALUES (?, ?, 1, 1)
");

if ($stmt->execute([$username, $hashed_password])) {
    echo "<h1>Success!</h1>";
    echo "Super Admin created.<br>Username: <strong>$username</strong><br>";
    echo "Now, please DELETE this file from your server for security.";
} else {
    echo "Error creating admin.";
}