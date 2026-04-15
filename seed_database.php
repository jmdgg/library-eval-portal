<?php
// seed_database.php
require_once 'db_connect.php';

try {
    $pdo->beginTransaction();

    // 1. Seed Departments
    $departments = [
        'Circulation Section',
        'General Reference Section',
        'Computer and Multimedia Services (CMS)',
        'Health Sciences Library',
        'Filipiniana Section',
        'College of Business and Accountancy Library',
        'PS Library'
    ];
    $deptStmt = $pdo->prepare("INSERT IGNORE INTO department (department_name) VALUES (?)");
    foreach ($departments as $name) {
        $deptStmt->execute([$name]);
    }
    echo "Departments seeded.\n";

    // 2. Seed Question Metrics
    $questions = [
        'The library has sufficient resources for my research and information needs',
        'Library staff provided assistance in a timely and helpful manner',
        'The process of borrowing, returning and renewal of library resources is convenient',
        'The information/procedure provided by the library staff were easy to understand'
    ];
    $qStmt = $pdo->prepare("INSERT IGNORE INTO question_metric (category, question_text, max_score) VALUES (?, ?, ?)");
    foreach ($questions as $text) {
        $qStmt->execute(['Feedback', $text, 5]);
    }
    echo "Questions seeded.\n";

    // 3. Seed Evaluation Period (Active Period)
    $month = 'APRIL';
    $year = 2026;
    $periodStmt = $pdo->prepare("INSERT IGNORE INTO evaluation_period (eval_month, eval_year, is_processed) VALUES (?, ?, ?)");
    $periodStmt->execute([$month, $year, 0]);
    echo "Evaluation Period (April 2026) seeded.\n";

    $pdo->commit();
    echo "SUCCESS: Database seeded.\n";

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
