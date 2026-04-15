<?php
// migrate_schema.php
require_once 'db_connect.php';

try {
    $sql = "ALTER TABLE SURVEY_SUBMISSION 
            ADD COLUMN library_accommodated VARCHAR(255) AFTER respondent_id,
            ADD COLUMN services_availed TEXT AFTER library_accommodated,
            ADD COLUMN satisfied VARCHAR(10) AFTER services_availed,
            ADD COLUMN overall_rating VARCHAR(50) AFTER satisfied,
            ADD COLUMN recommendations TEXT AFTER overall_rating,
            ADD COLUMN comments TEXT AFTER recommendations";
    
    $pdo->exec($sql);
    echo "SUCCESS: Schema updated.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "NOTE: Columns already exist. Skipping.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
?>
