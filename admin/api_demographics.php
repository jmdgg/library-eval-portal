<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['labels' => [], 'data' => []]);
    exit;
}

$labels = [];
$data = [];

try {
    $stmt = $pdo->query("
        SELECT pt.type_name as role, COUNT(*) as count 
        FROM survey_submission ss
        JOIN patron_type pt ON ss.patron_type_id = pt.patron_type_id 
        GROUP BY pt.patron_type_id
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $labels[] = $row['role'];
        $data[] = (int)$row['count'];
    }

    echo json_encode(['labels' => $labels, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['labels' => [], 'data' => []]);
}
?>
