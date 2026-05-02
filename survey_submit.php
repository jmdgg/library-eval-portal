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

    // 2. Evaluation Period Pivot: We no longer manage periods manually.
    // We hardcode ID 1 for legacy DB compatibility and rely on created_at for filtering.
    $period_id = 1;
    $submission_date = $_POST['date'] ?? date('Y-m-d');
    $timestamp = strtotime($submission_date);

    // 3. Extract Demographics & Inputs
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $lib_dept_id = (int) ($_POST['lib_dept_id'] ?? 0);
    $patron_type_id = (int) ($_POST['patron_type_id'] ?? 0);
    $other_patron_details = !empty($_POST['other_patron_details']) ? htmlspecialchars($_POST['other_patron_details']) : null;

    // College might be omitted (e.g. for NTPs)
    $college_id = !empty($_POST['college_id']) ? (int) $_POST['college_id'] : null;
    $acad_dept_id = !empty($_POST['acad_dept_id']) ? (int) $_POST['acad_dept_id'] : null;

    $is_satisfied = (int) ($_POST['is_satisfied'] ?? 0);
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
        $total_score += (int) $score;
    }
    $overall_rating = round($total_score / $question_count, 2);
    $created_at = date('Y-m-d H:i:s', $timestamp); // Or use current timestamp

    // 5. Insert PARENT Record (`survey_submission`)
    $stmt = $pdo->prepare("
        INSERT INTO survey_submission (
            period_id, lib_dept_id, patron_type_id, other_patron_details, college_id, acad_dept_id, email, 
            is_satisfied, overall_rating, recommendations, comments, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $period_id,
        $lib_dept_id,
        $patron_type_id,
        $other_patron_details,
        $college_id,
        $acad_dept_id,
        $email,
        $is_satisfied,
        $overall_rating,
        $recommendations,
        $comments,
        $created_at
    ]);

    $submission_id = $pdo->lastInsertId();

    // 6. Insert Junction Records (`submission_service`)
    $services = $_POST['services'] ?? [];
    if (!empty($services)) {
        // Find the 'Other' service ID
        $other_service_id = $pdo->query("SELECT service_id FROM library_service WHERE service_name = 'Other' LIMIT 1")->fetchColumn();
        $other_service_details = !empty($_POST['other_service_details']) ? htmlspecialchars($_POST['other_service_details']) : null;

        $service_stmt = $pdo->prepare("INSERT INTO submission_service (submission_id, service_id, other_service_details) VALUES (?, ?, ?)");
        foreach ($services as $service_id) {
            $details = ($service_id == $other_service_id) ? $other_service_details : null;
            $service_stmt->execute([$submission_id, (int) $service_id, $details]);
        }
    }

    // 7. Insert CHILD Records (`response_detail`)
    $detail_stmt = $pdo->prepare("INSERT INTO response_detail (submission_id, question_id, score) VALUES (?, ?, ?)");
    foreach ($feedback as $question_id => $score) {
        $detail_stmt->execute([$submission_id, (int) $question_id, (int) $score]);
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