<?php
/**
 * generate_excel.php
 * Compiles real-time 3NF database records into a downloadable .xlsx file.
 */

session_start();
require_once '../db_connect.php';

// 1. SECURITY: Kick out unauthorized users
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit('Unauthorized access.');
}

// 2. Load your existing PhpSpreadsheet Library
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // 3. Identify Current Period
    $month_str = strtoupper(date('F'));
    $year_int = (int) date('Y');

    $period_stmt = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE eval_month = ? AND eval_year = ? LIMIT 1");
    $period_stmt->execute([$month_str, $year_int]);
    $period = $period_stmt->fetch();

    if (!$period) {
        die("No active evaluation period found for this month.");
    }
    $period_id = $period['period_id'];

    // 4. THE PIVOT QUERY
    // This flattens the 3NF parent/child relationships back into a single row per respondent
    $query = "
        SELECT 
            ss.submission_date,
            d.department_name,
            ss.email,
            ss.role,
            ss.services_availed,
            MAX(CASE WHEN q.question_id = 1 THEN rd.score END) AS q1_resources,
            MAX(CASE WHEN q.question_id = 2 THEN rd.score END) AS q2_staff,
            MAX(CASE WHEN q.question_id = 3 THEN rd.score END) AS q3_process,
            MAX(CASE WHEN q.question_id = 4 THEN rd.score END) AS q4_procedures,
            ss.is_satisfied,
            ss.overall_rating,
            ss.comments
        FROM survey_submission ss
        JOIN department d ON ss.department_id = d.department_id
        JOIN response_detail rd ON ss.submission_id = rd.submission_id
        JOIN question_metric q ON rd.question_id = q.question_id
        WHERE ss.period_id = ?
        GROUP BY ss.submission_id
        ORDER BY ss.submission_date DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$period_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Build the Excel File
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($month_str . ' ' . $year_int . ' Report');

    // Write Headers
    $headers = [
        'Date',
        'Library Branch',
        'Email',
        'Role',
        'Services Availed',
        'Q1: Resources',
        'Q2: Staff Assistance',
        'Q3: Process',
        'Q4: Procedures',
        'Satisfied?',
        'Overall Rating',
        'Comments'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    // Make Headers Bold
    $sheet->getStyle('A1:L1')->getFont()->setBold(true);

    // Write Data
    $rowNumber = 2;
    foreach ($results as $row) {
        $sheet->fromArray(array_values($row), NULL, 'A' . $rowNumber);
        $rowNumber++;
    }

    // Auto-size columns for readability
    foreach (range('A', 'L') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // 6. Stream to Browser (Force Download)
    $filename = "Library_Eval_" . $month_str . "_" . $year_int . ".xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error generating report: " . $e->getMessage());
}