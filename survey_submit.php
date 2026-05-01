<?php
/**
 * survey_submit.php
 * Normalized 3NF Architecture
 */

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: survey.php?error=invalid_request");
    exit;
}

try {
    // 1. ALL-OR-NOTHING TRANSACTION
    $pdo->beginTransaction();

    // 2. Resolve Evaluation Period (Find or Create)
    $submission_date = $_POST['date'] ?? date('Y-m-d');
    $timestamp = strtotime($submission_date);
    $start_date = date('Y-m-01', $timestamp);
    $end_date = date('Y-m-t', $timestamp);

    $period_query = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE start_date = ? AND end_date = ? LIMIT 1");
    $period_query->execute([$start_date, $end_date]);
    $period = $period_query->fetch();

    if ($period) {
        $period_id = $period['period_id'];
    } else {
        $insert_period = $pdo->prepare("INSERT INTO evaluation_period (start_date, end_date, is_processed) VALUES (?, ?, 0)");
        $insert_period->execute([$start_date, $end_date]);
        $period_id = $pdo->lastInsertId();
    }

    // 3. Extract Demographics & Inputs
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $lib_dept_id = (int)($_POST['lib_dept_id'] ?? 0);
    $patron_type_id = (int)($_POST['patron_type_id'] ?? 0);
    
    // College might be omitted (e.g. for NTPs)
    $college_id = !empty($_POST['college_id']) ? (int)$_POST['college_id'] : null;
    $acad_dept_id = !empty($_POST['acad_dept_id']) ? (int)$_POST['acad_dept_id'] : null;

    $is_satisfied = (int)($_POST['is_satisfied'] ?? 0);
    $recommendations = htmlspecialchars($_POST['recommendations'] ?? '');
    $comments = htmlspecialchars($_POST['comments'] ?? '');

    // 4. Calculate Overall Rating from Feedback Array
    $feedback = $_POST['feedback'] ?? [];
    if (empty($feedback)) {
        throw new Exception("No feedback scores submitted.");
    }
    
    $total_score = 0;
    $question_count = count($feedback);
    foreach ($feedback as $question_id => $score) {
        $total_score += (int)$score;
    }
    $overall_rating = round($total_score / $question_count, 2);
    $created_at = date('Y-m-d H:i:s', $timestamp); // Or use current timestamp

    // 5. Insert PARENT Record (`survey_submission`)
    $stmt = $pdo->prepare("
        INSERT INTO survey_submission (
            period_id, lib_dept_id, patron_type_id, college_id, acad_dept_id, email, 
            is_satisfied, overall_rating, recommendations, comments, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $period_id, $lib_dept_id, $patron_type_id, $college_id, $acad_dept_id, $email,
        $is_satisfied, $overall_rating, $recommendations, $comments, $created_at
    ]);

    $submission_id = $pdo->lastInsertId();

    // 6. Insert Junction Records (`submission_service`)
    $services = $_POST['services'] ?? [];
    if (!empty($services)) {
        $service_stmt = $pdo->prepare("INSERT INTO submission_service (submission_id, service_id) VALUES (?, ?)");
        foreach ($services as $service_id) {
            $service_stmt->execute([$submission_id, (int)$service_id]);
        }
    }

    // 7. Insert CHILD Records (`response_detail`)
    $detail_stmt = $pdo->prepare("INSERT INTO response_detail (submission_id, question_id, score) VALUES (?, ?, ?)");
    foreach ($feedback as $question_id => $score) {
        $detail_stmt->execute([$submission_id, (int)$question_id, (int)$score]);
    }

    // 8. Success! Commit everything to the database.
    $pdo->commit();
    header("Location: thank_you.php?status=success");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Survey Submission Error: " . $e->getMessage());
    header("Location: survey.php?error=submission_failed");
    exit;
}