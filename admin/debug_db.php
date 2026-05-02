<?php
require '../db_connect.php';
try {
    $count = $pdo->query("SELECT COUNT(*) FROM survey_submission")->fetchColumn();
    echo "Total Submissions: " . $count . "\n";
    
    echo "\nColleges:\n";
    $stmt = $pdo->query("SELECT * FROM college");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\nDepartments:\n";
    $stmt = $pdo->query("SELECT * FROM library_department");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
