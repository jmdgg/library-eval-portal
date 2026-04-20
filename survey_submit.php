<?php
/**
 * survey_submit.php
 * Flat "Wide Table" Architecture
 */

require_once 'db_connect.php';

// Gatekeeper: Reject non-POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: survey.php?error=invalid_request");
    exit;
}

try {
    // 1. Period Lookup (Automatic mapping based on submission date)
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

    // 2. Extract Demographics & Metadata
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $department_id = (int)($_POST['department_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $college = $_POST['college'] ?? '';
    $academic_department = $_POST['academic_department'] ?? '';
    
    // Process Checkboxes: Convert array to comma-separated string safely
    $services_raw = $_POST['services'] ?? [];
    $services_availed = is_array($services_raw) ? implode(', ', array_map('htmlspecialchars', $services_raw)) : '';

    // 3. Extract Likert Scores
    // Defaulting to 0 if missing, but HTML5 validation should catch this first
    $q1_resources = (int)($_POST['feedback']['resources'] ?? 0);
    $q2_staff = (int)($_POST['feedback']['staff_assistance'] ?? 0);
    $q3_process = (int)($_POST['feedback']['process'] ?? 0);
    $q4_procedures = (int)($_POST['feedback']['procedures'] ?? 0);

    // 4. Extract Overall Ratings & Open Text
    $is_satisfied = $_POST['satisfied'] ?? '';
    $overall_rating = $_POST['overall_rating'] ?? '';
    $recommendations = htmlspecialchars($_POST['recommendations'] ?? '');
    $comments = htmlspecialchars($_POST['comments'] ?? '');

    // 5. The Single, Clean Insert
    $stmt = $pdo->prepare("
        INSERT INTO survey_submission (
            period_id, department_id, email, submission_date, role, college, academic_department, 
            services_availed, q1_resources, q2_staff_assistance, q3_process, q4_procedures, 
            is_satisfied, overall_rating, recommendations, comments
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $period_id, 
        $department_id, 
        $email, 
        $submission_date, 
        $role, 
        $college, 
        $academic_department, 
        $services_availed, 
        $q1_resources, 
        $q2_staff, 
        $q3_process, 
        $q4_procedures, 
        $is_satisfied, 
        $overall_rating, 
        $recommendations, 
        $comments
    ]);

    // Success -> Redirect
    header("Location: thank_you.php?status=success");
    exit;

} catch (Exception $e) {
    // Log the error securely for debugging
    error_log("Survey Submission Error: " . $e->getMessage());

    // TEMPORARY DEBUGGING: To see the error on screen during testing, uncomment the line below.
    // die("Database Error: " . $e->getMessage());

    // Redirect to error state
    header("Location: survey.php?error=submission_failed");
    exit;
}