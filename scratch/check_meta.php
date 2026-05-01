<?php
require_once 'db_connect.php';

echo "DEPARTMENTS:\n";
$depts = $pdo->query("SELECT * FROM library_department")->fetchAll(PDO::FETCH_ASSOC);
print_r($depts);

echo "\nQUESTIONS:\n";
$questions = $pdo->query("SELECT * FROM question_metric")->fetchAll(PDO::FETCH_ASSOC);
print_r($questions);
?>
