<?php
require_once 'db_connect.php';
$res = $pdo->query("SELECT * FROM respondent")->fetchAll();
echo "RESPONDENTS COUNT: " . count($res) . "\n";
print_r($res);
?>
