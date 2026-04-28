<?php
// cleanup_migration.php
require_once 'db_connect.php';

try {
    // Drop the department_id Foreign Key and column to finalize denormalization
    $pdo->exec("ALTER TABLE SURVEY_SUBMISSION DROP FOREIGN KEY fk_submission_dept");
    $pdo->exec("ALTER TABLE SURVEY_SUBMISSION DROP COLUMN department_id");
    echo "SUCCESS: department_id denormalized.\n";
} catch (Exception $e) {
    echo "NOTE: department_id might already be gone. " . $e->getMessage() . "\n";
}
?>
