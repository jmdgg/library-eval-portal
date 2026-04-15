<?php
// check_fkeys.php
require_once 'db_connect.php';

echo "CONSTRAINTS ON SURVEY_SUBMISSION:\n";
$stmt = $pdo->prepare("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
                       FROM information_schema.KEY_COLUMN_USAGE 
                       WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'SURVEY_SUBMISSION' AND REFERENCED_TABLE_NAME IS NOT NULL");
$stmt->execute([':db' => $dbname]);
foreach ($stmt->fetchAll() as $row) {
    echo "- Name: {$row['CONSTRAINT_NAME']}, Column: {$row['COLUMN_NAME']}, Refs: {$row['REFERENCED_TABLE_NAME']}\n";
}
?>
