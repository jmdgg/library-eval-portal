<?php
/**
 * test_final_submit.php
 * Verifies that the denormalized submission works end-to-end.
 */

require_once 'db_connect.php';

// Mock environment
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock POST data matching the survey.php payload
$_POST = [
    'department_id' => 1,
    'full_name' => 'Test Hybrid User',
    'date' => '2026-04-16',
    'college' => 'Test College',
    'department' => 'Test Academic Dept',
    'role' => 'Student',
    'services' => ['borrowing', 'reference'],
    'feedback' => [
        'resources' => 5,
        'staff_assistance' => 4,
        'process' => 5,
        'procedures' => 4
    ],
    'satisfied' => 'Yes',
    'overall_rating' => 'Excellent',
    'recommendations' => 'More books please',
    'comments' => 'Great service'
];

try {
    // Run the actual submission script logic (simulated by including it)
    // We expect it to redirect on success, so we buffer the redirect headers
    ob_start();
    include 'survey_submit.php';
    ob_end_clean();
    
    echo "TEST: Script executed.\n";
    
    // Check Database
    $sub = $pdo->query("SELECT * FROM SURVEY_SUBMISSION ORDER BY submission_id DESC LIMIT 1")->fetch();
    if ($sub && $sub['full_name'] === 'Test User') {
        echo "SUCCESS: Parent record found with name 'Test User'.\n";
        echo "DEMOGRAPHICS: {$sub['college']}, {$sub['academic_department']}\n";
        echo "QUALITATIVE: Satisfied={$sub['satisfied']}, Rating={$sub['overall_rating']}\n";
        
        $details = $pdo->query("SELECT count(*) as cnt FROM RESPONSE_DETAIL WHERE submission_id = {$sub['submission_id']}")->fetch();
        echo "SCORES: Found {$details['cnt']} score records linked to submission.\n";
    } else {
        echo "FAILURE: Could not find the submitted record.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
