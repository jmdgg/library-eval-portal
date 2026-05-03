<?php
require_once '../db_connect.php';
echo "Indexes for survey_submission:\n";
$stmt = $pdo->query("SHOW INDEX FROM survey_submission");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nIndexes for response_detail:\n";
$stmt = $pdo->query("SHOW INDEX FROM response_detail");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
