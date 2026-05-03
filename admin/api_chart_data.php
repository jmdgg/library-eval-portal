<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['labels' => [], 'data' => []]);
    exit;
}

$view = $_GET['view'] ?? 'yearly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

$labels = [];
$data = [];

try {
    if ($view === 'yearly') {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = array_fill(0, 12, 0);

        $stmt = $pdo->prepare("
            SELECT MONTH(created_at) as m, COUNT(*) as count 
            FROM survey_submission 
            WHERE YEAR(created_at) = ? 
            GROUP BY MONTH(created_at)
        ");
        $stmt->execute([$year]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $m = (int)$row['m'];
            if ($m >= 1 && $m <= 12) {
                $data[$m - 1] = (int)$row['count'];
            }
        }
    } else if ($view === 'monthly') {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = $i;
            $data[] = 0;
        }

        $stmt = $pdo->prepare("
            SELECT DAY(created_at) as d, COUNT(*) as count 
            FROM survey_submission 
            WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
            GROUP BY DAY(created_at)
        ");
        $stmt->execute([$year, $month]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $d = (int)$row['d'];
            if ($d >= 1 && $d <= $daysInMonth) {
                $data[$d - 1] = (int)$row['count'];
            }
        }
    }

    echo json_encode(['labels' => $labels, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['labels' => [], 'data' => []]);
}
?>
