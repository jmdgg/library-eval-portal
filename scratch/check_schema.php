<?php
require_once 'db_connect.php';
function describe($table) {
    global $pdo;
    echo "--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
describe('admin_user');
describe('audit_log');
