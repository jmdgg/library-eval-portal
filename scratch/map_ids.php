<?php
require_once 'db_connect.php';
echo "QUESTIONS:\n";
foreach($pdo->query('SELECT * FROM question_metric')->fetchAll() as $q) {
    echo $q['question_id'] . ": " . $q['question_text'] . "\n";
}
echo "\nDEPARTMENTS:\n";
foreach($pdo->query('SELECT * FROM department')->fetchAll() as $d) {
    echo $d['department_id'] . ": " . $d['department_name'] . "\n";
}
echo "\nPERIODS:\n";
foreach($pdo->query('SELECT * FROM evaluation_period')->fetchAll() as $p) {
    echo $p['period_id'] . ": " . $p['eval_month'] . " " . $p['eval_year'] . "\n";
}
?>
