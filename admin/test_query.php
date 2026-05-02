<?php
require '../db_connect.php';
try {
    $stmt = $pdo->query("
        SELECT 
            ss.submission_id, 
            ss.created_at as submission_date, 
            pt.type_name as role, 
            c.college_name as college, 
            ss.email as respondent_name, 
            ld.dept_name as department, 
            ss.overall_rating,
            ss.recommendations,
            ss.comments,
            GROUP_CONCAT(rd.score ORDER BY rd.question_id ASC) as likert_scores
        FROM survey_submission ss
        LEFT JOIN library_department ld ON ss.lib_dept_id = ld.lib_dept_id
        LEFT JOIN patron_type pt ON ss.patron_type_id = pt.patron_type_id
        LEFT JOIN college c ON ss.college_id = c.college_id
        LEFT JOIN response_detail rd ON ss.submission_id = rd.submission_id
        GROUP BY ss.submission_id
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $res = $stmt->fetchAll();
    echo "Count: " . count($res) . "\n";
    print_r($res);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
