<?php
require_once 'db_connect.php';
$sub = $pdo->query("SELECT * FROM SURVEY_SUBMISSION ORDER BY submission_id DESC LIMIT 1")->fetch();
if ($sub) {
    echo "LATEST SUBMISSION:\n";
    echo "ID: " . $sub['submission_id'] . "\n";
    echo "Dept ID: " . $sub['department_id'] . "\n";
    echo "Name: " . $sub['full_name'] . "\n";
    echo "Period ID: " . $sub['period_id'] . "\n";
} else {
    echo "No submissions found.\n";
}
?>
