<?php
/**
 * survey_submit.php
 * Production-ready submission handler for Path A (Denormalized Demographics)
 * AUF Library Service Evaluation Portal
 */

require_once 'db_connect.php';

// 1. Gatekeeper: Reject non-POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: survey.php?error=invalid_request");
    exit;
}

try {
    // 2. Begin Transaction (All-or-Nothing)
    $pdo->beginTransaction();

    // 3. Extract Demographics & Period
    // We look up the active evaluation period automatically based on the submission date
    $submission_date = $_POST['date'] ?? date('Y-m-d');
    $month_str = strtoupper(date('F', strtotime($submission_date)));
    $year_int = (int)date('Y', strtotime($submission_date));

    $period_query = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE eval_month = ? AND eval_year = ? LIMIT 1");
    $period_query->execute([$month_str, $year_int]);
    $period = $period_query->fetch();

    if (!$period) {
        throw new Exception("Evaluation period for $month_str $year_int is not open. Please contact the administrator.");
    }
    $period_id = $period['period_id'];

    $dept_id     = (int)($_POST['department_id'] ?? 0);
    $full_name   = trim($_POST['full_name'] ?? '');
    $role        = trim($_POST['role'] ?? '');
    $college     = trim($_POST['college'] ?? '');
    $dept_name   = trim($_POST['academic_department'] ?? $_POST['department'] ?? ''); // Snapshot text

    // 5. Capture Qualitative Feedback
    $services    = isset($_POST['services']) ? implode(', ', $_POST['services']) : '';
    $satisfied   = $_POST['satisfied'] ?? '';
    $rating      = $_POST['overall_rating'] ?? '';
    $recoms      = trim($_POST['recommendations'] ?? '');
    $comments    = trim($_POST['comments'] ?? '');

    // Validation: Ensure required fields aren't empty
    if (!$dept_id || empty($full_name) || empty($role) || empty($satisfied) || empty($rating)) {
        throw new Exception("Missing required form fields.");
    }

    // 6. Insert Parent Record (SURVEY_SUBMISSION)
    $sql_parent = "INSERT INTO SURVEY_SUBMISSION 
        (period_id, department_id, full_name, role, college, academic_department, services_availed, satisfied, overall_rating, recommendations, comments, submission_timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt_parent = $pdo->prepare($sql_parent);
    $stmt_parent->execute([
        $period_id,
        $dept_id,
        $full_name,
        $role,
        $college,
        $dept_name,
        $services,
        $satisfied,
        $rating,
        $recoms,
        $comments
    ]);

    $submission_id = $pdo->lastInsertId();

    // 7. Insert Child Records (RESPONSE_DETAIL)
    // Map the feedback[key] => score array
    if (isset($_POST['feedback']) && is_array($_POST['feedback'])) {
        // Fetch all questions from the database to map keys to IDs
        // Mapping assumes the order seeded in seed_database.php or matches by text
        $q_stmt = $pdo->query("SELECT question_id, question_text FROM question_metric");
        $questions = $q_stmt->fetchAll();
        
        // Define a mapping between form keys and question text snippets
        $key_map = [
            'resources'         => 'sufficient resources',
            'staff_assistance'  => 'staff provided assistance',
            'process'           => 'borrowing, returning and renewal',
            'procedures'        => 'easy to understand'
        ];

        $detail_stmt = $pdo->prepare("INSERT INTO RESPONSE_DETAIL (submission_id, question_id, given_score) VALUES (?, ?, ?)");

        foreach ($_POST['feedback'] as $key => $score) {
            $score_int = (int)$score;
            if ($score_int < 1 || $score_int > 5) continue;

            $found_qid = null;
            $search_term = $key_map[$key] ?? '';

            foreach ($questions as $q) {
                if (stripos($q['question_text'], $search_term) !== false) {
                    $found_qid = $q['question_id'];
                    break;
                }
            }

            if ($found_qid) {
                $detail_stmt->execute([$submission_id, $found_qid, $score_int]);
            }
        }
    }

    // 8. Success!
    $pdo->commit();
    header("Location: thank_you.php?status=success");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Survey Submission Error: " . $e->getMessage());
    header("Location: survey.php?error=submission_failed&msg=" . urlencode($e->getMessage()));
    exit;
}
?>