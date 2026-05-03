<?php
require_once 'db_connect.php';

$sql = "
ALTER TABLE admin_user 
ADD COLUMN IF NOT EXISTS role ENUM('superadmin', 'subadmin') DEFAULT 'subadmin' AFTER password_hash,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER role,
ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL AFTER is_active,
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL AFTER last_login,
ADD COLUMN IF NOT EXISTS token_expiry DATETIME NULL AFTER reset_token;

UPDATE admin_user SET role = 'superadmin' WHERE admin_id = 1;
";

try {
    $pdo->exec($sql);
    echo "SQL Migration successful.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
