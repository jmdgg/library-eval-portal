<?php
// final_migration.php
require_once 'db_connect.php';

try {
    $pdo->beginTransaction();

    // 1. Drop the Foreign Key on SURVEY_SUBMISSION
    // We already know the name is fk_submission_resp
    $pdo->exec("ALTER TABLE SURVEY_SUBMISSION DROP FOREIGN KEY fk_submission_resp");
    
    // 2. Drop the respondent_id column
    $pdo->exec("ALTER TABLE SURVEY_SUBMISSION DROP COLUMN respondent_id");
    
    // 3. Drop the RESPONDENT table entirely
    $pdo->exec("DROP TABLE IF EXISTS respondent");
    
    // 4. Add the new demographic columns directly to SURVEY_SUBMISSION
    $pdo->exec("ALTER TABLE SURVEY_SUBMISSION 
                ADD COLUMN full_name VARCHAR(255) AFTER period_id,
                ADD COLUMN role VARCHAR(100) AFTER full_name,
                ADD COLUMN college VARCHAR(255) AFTER role,
                ADD COLUMN academic_department VARCHAR(255) AFTER college");

    $pdo->commit();
    echo "SUCCESS: Database migrated to Path A (Denormalized).\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: Migration failed - " . $e->getMessage() . "\n";
}
?>
