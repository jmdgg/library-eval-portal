<?php
/**
 * survey_submit.php
 * Normalized 3NF Architecture (Parent -> Child Insertions)
 */

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: survey.php?error=invalid_request");
    exit;
}

try {
    // 1. ALL-OR-NOTHING TRANSACTION
    $pdo->beginTransaction();

    // 2. Fetch Active Period
    $submission_date = $_POST['date'] ?? date('Y-m-d');
    $month_str = strtoupper(date('F', strtotime($submission_date)));
    $year_int = (int)date('Y', strtotime($submission_date));

    $period_query = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE eval_month = ? AND eval_year = ? LIMIT 1");
    $period_query->execute([$month_str, $year_int]);
    $period = $period_query->fetch();

    if (!$period) {
        throw new Exception("Evaluation period for $month_str $year_int is not active.");
    }
    $period_id = $period['period_id'];

    // 3. Prepare Parent Data (Demographics & Text)
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $department_id = (int)($_POST['department_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $college = $_POST['college'] ?? '';
    $academic_department = $_POST['academic_department'] ?? '';
    
    $services_raw = $_POST['services'] ?? [];
    $services_availed = is_array($services_raw) ? implode(', ', array_map('htmlspecialchars', $services_raw)) : '';
    
    $is_satisfied = $_POST['satisfied'] ?? '';
    $overall_rating = $_POST['overall_rating'] ?? '';
    $recommendations = htmlspecialchars($_POST['recommendations'] ?? '');
    $comments = htmlspecialchars($_POST['comments'] ?? '');

    // 4. Insert PARENT Record (`survey_submission`)
    $stmt = $pdo->prepare("
        INSERT INTO survey_submission (
            period_id, department_id, email, submission_date, role, college, 
            academic_department, services_availed, is_satisfied, overall_rating, 
            recommendations, comments
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $period_id, $department_id, $email, $submission_date, $role, $college,
        $academic_department, $services_availed, $is_satisfied, $overall_rating,
        $recommendations, $comments
    ]);

    $submission_id = $pdo->lastInsertId(); // Capture the new ID!

    // 5. Insert CHILD Records (`response_detail`)
    // Map the HTML form input names to the Database question_id
    $question_map = [
        'resources' => 1,
        'staff_assistance' => 2,
        'process' => 3,
        'procedures' => 4
    ];

    $detail_stmt = $pdo->prepare("INSERT INTO response_detail (submission_id, question_id, score) VALUES (?, ?, ?)");

    $feedback = $_POST['feedback'] ?? [];
    foreach ($question_map as $html_key => $db_question_id) {
        // If they skipped a question, default to 0, though HTML5 validation should catch it
        $score = isset($feedback[$html_key]) ? (int)$feedback[$html_key] : 0; 
        
        $detail_stmt->execute([$submission_id, $db_question_id, $score]);
    }

    // 6. Success! Commit everything to the database.
    $pdo->commit();
    header("Location: thank_you.php?status=success");
    exit;

} catch (Exception $e) {
    // 7. Disaster Recovery
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Survey Submission Error: " . $e->getMessage());
    // TEMPORARY DEBUGGING: Uncomment below to see the error on screen
    // die("Database Error: " . $e->getMessage());

    header("Location: survey.php?error=submission_failed");
    exit;
}