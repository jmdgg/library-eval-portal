<?php
/**
 * api_dashboard_data.php
 * Provides real-time/cached JSON data for the Chart.js frontend.
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    // 1. Identify the Current Period
    $current_date = time();
    $start_date = date('Y-m-01', $current_date);
    $end_date = date('Y-m-t', $current_date);

    $period_stmt = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE start_date = ? AND end_date = ? LIMIT 1");
    $period_stmt->execute([$start_date, $end_date]);
    $period = $period_stmt->fetch();

    if (!$period) {
        echo json_encode(['status' => 'error', 'message' => 'No active evaluation period.']);
        exit;
    }
    $period_id = $period['period_id'];

    // 2. THE 5-MINUTE LAZY CACHE
    // Check if we have a report generated in the last 5 minutes
    $cache_stmt = $pdo->prepare("
        SELECT dashboard_data 
        FROM generated_report 
        WHERE period_id = ? AND generation_date >= NOW() - INTERVAL 5 MINUTE 
        ORDER BY generation_date DESC LIMIT 1
    ");
    $cache_stmt->execute([$period_id]);
    $cached_report = $cache_stmt->fetch();

    if ($cached_report && !empty($cached_report['dashboard_data'])) {
        // CACHE HIT: Serve the cached JSON instantly
        echo $cached_report['dashboard_data'];
        exit;
    }

    // 3. CACHE MISS: We must calculate the math.
    // Query: Calculate the average score for each question, grouped by library department
    $math_query = "
        SELECT 
            ld.dept_name as department_name,
            q.question_id,
            q.category,
            AVG(rd.score) as average_score
        FROM survey_submission ss
        JOIN library_department ld ON ss.lib_dept_id = ld.lib_dept_id
        JOIN response_detail rd ON ss.submission_id = rd.submission_id
        JOIN question_metric q ON rd.question_id = q.question_id
        WHERE ss.period_id = ?
        GROUP BY ld.lib_dept_id, q.question_id
    ";

    $stmt = $pdo->prepare($math_query);
    $stmt->execute([$period_id]);
    $raw_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Format the Data for Chart.js
    $dashboard_data = [
        'status' => 'success',
        'generated_at' => date('Y-m-d H:i:s'),
        'departments' => []
    ];

    // Restructure the flat SQL results into a clean nested array
    foreach ($raw_results as $row) {
        $dept = $row['department_name'];
        if (!isset($dashboard_data['departments'][$dept])) {
            $dashboard_data['departments'][$dept] = [
                'name' => $dept,
                'scores' => []
            ];
        }

        $dashboard_data['departments'][$dept]['scores']['q' . $row['question_id']] = round($row['average_score'], 2);
    }

    $json_output = json_encode($dashboard_data);

    // 5. Save to Cache (`generated_report`)
    $insert_cache = $pdo->prepare("
        INSERT INTO generated_report (period_id, file_name, download_url, dashboard_data) 
        VALUES (?, 'pending', 'pending', ?)
    ");
    $insert_cache->execute([$period_id, $json_output]);

    // 6. Serve the fresh data to the frontend
    echo $json_output;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}