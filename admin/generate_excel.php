<?php
/**
 * generate_excel.php
 * Extracts real-time DB records and injects them into master_template.xlsx
 */

ob_start();
session_start();
require_once '../db_connect.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1. SECURITY
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('Unauthorized access.');
}

// Allow browser to read the filename from header
header('Access-Control-Expose-Headers: Content-Disposition');

// 2. FETCH DATE RANGE
$start_month = $_GET['start_month'] ?? date('F');
$start_year = (int) ($_GET['start_year'] ?? date('Y'));
$end_month = $_GET['end_month'] ?? date('F');
$end_year = (int) ($_GET['end_year'] ?? date('Y'));

// 3. VALIDATE RANGE
$startTS = strtotime("1 $start_month $start_year");
$endTS = strtotime("1 $end_month $end_year");

if (!$startTS || !$endTS) {
    http_response_code(400);
    die('Error: Invalid date parameters.');
}

if ($startTS > $endTS) {
    http_response_code(400);
    die('Error: "From" date cannot be later than "To" date.');
}

$reportDateLabel = strtoupper(date('F Y', $startTS)) . " - " . strtoupper(date('F Y', $endTS));
if (date('m-Y', $startTS) === date('m-Y', $endTS)) {
    $reportDateLabel = strtoupper(date('F Y', $startTS));
}

try {
    // 3. THE 3NF PIVOT QUERY
    // Filtering directly by submission date for accuracy
    $query = "
        SELECT 
            ld.dept_name as department_name,
            ss.is_satisfied,
            ss.overall_rating,
            ss.recommendations,
            ss.comments,
            MAX(CASE WHEN rd.question_id = 1 THEN rd.score END) AS q1,
            MAX(CASE WHEN rd.question_id = 2 THEN rd.score END) AS q2,
            MAX(CASE WHEN rd.question_id = 3 THEN rd.score END) AS q3,
            MAX(CASE WHEN rd.question_id = 4 THEN rd.score END) AS q4
        FROM survey_submission ss
        JOIN library_department ld ON ss.lib_dept_id = ld.lib_dept_id
        LEFT JOIN response_detail rd ON ss.submission_id = rd.submission_id
        WHERE ss.created_at BETWEEN ? AND ?
        GROUP BY ss.submission_id
    ";

    $stmt = $pdo->prepare($query);
    $start_date = date('Y-m-01 00:00:00', $startTS);
    $end_date = date('Y-m-t 23:59:59', $endTS);
    $stmt->execute([$start_date, $end_date]);
    $raw_submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. INITIALIZE YOUR LEGACY BUCKETS
    $departments = [
        'Circulation Section' => ['tab' => 'CIRCULATION', 'sum_row' => 15],
        'General Reference Section' => ['tab' => 'GEN REF', 'sum_row' => 16],
        'Computer and Multimedia Services (CMS)' => ['tab' => 'CMS', 'sum_row' => 17],
        'Health Sciences Library' => ['tab' => 'HSL', 'sum_row' => 18],
        'Filipiniana Section' => ['tab' => 'FILIPINIANA', 'sum_row' => 19],
        'College of Business and Accountancy Library' => ['tab' => 'CBAL', 'sum_row' => 20],
        'PS Library' => ['tab' => 'PSL', 'sum_row' => 21]
    ];

    $stats = [];
    $globalRecommendations = [];
    $globalComments = [];

    foreach ($departments as $dept => $config) {
        $stats[$dept] = [
            'count' => 0,
            'q1' => ['Strongly Agree' => 0, 'Agree' => 0, 'Neither' => 0, 'Disagree' => 0, 'Strongly Disagree' => 0],
            'q2' => ['Strongly Agree' => 0, 'Agree' => 0, 'Neither' => 0, 'Disagree' => 0, 'Strongly Disagree' => 0],
            'q3' => ['Strongly Agree' => 0, 'Agree' => 0, 'Neither' => 0, 'Disagree' => 0, 'Strongly Disagree' => 0],
            'q4' => ['Strongly Agree' => 0, 'Agree' => 0, 'Neither' => 0, 'Disagree' => 0, 'Strongly Disagree' => 0],
            'sat' => ['Yes' => 0, 'No' => 0],
            'overall' => ['Excellent' => 0, 'Very Good' => 0, 'Good' => 0, 'Fair' => 0, 'Needs Improvement' => 0],
            'recoms' => [],
            'comments' => []
        ];
    }

    $scoreMap = [5 => 'Strongly Agree', 4 => 'Agree', 3 => 'Neither', 2 => 'Disagree', 1 => 'Strongly Disagree'];
    $likertVals = ['Strongly Agree' => 5, 'Agree' => 4, 'Neither' => 3, 'Disagree' => 2, 'Strongly Disagree' => 1];
    $overallVals = ['Excellent' => 5, 'Very Good' => 4, 'Good' => 3, 'Fair' => 2, 'Needs Improvement' => 1];

    // 5. PROCESS DB DATA INTO LEGACY FORMAT
    foreach ($raw_submissions as $row) {
        $dept = $row['department_name'];
        if (!isset($stats[$dept]))
            continue;

        $stats[$dept]['count']++;

        // Map Integer Scores back to Strings for your Array
        if ($row['q1'] && isset($scoreMap[$row['q1']]))
            $stats[$dept]['q1'][$scoreMap[$row['q1']]]++;
        if ($row['q2'] && isset($scoreMap[$row['q2']]))
            $stats[$dept]['q2'][$scoreMap[$row['q2']]]++;
        if ($row['q3'] && isset($scoreMap[$row['q3']]))
            $stats[$dept]['q3'][$scoreMap[$row['q3']]]++;
        if ($row['q4'] && isset($scoreMap[$row['q4']]))
            $stats[$dept]['q4'][$scoreMap[$row['q4']]]++;

        $sat = (int)$row['is_satisfied'];
        if ($sat === 1)
            $stats[$dept]['sat']['Yes']++;
        else
            $stats[$dept]['sat']['No']++;

        $overall_map = [5 => 'Excellent', 4 => 'Very Good', 3 => 'Good', 2 => 'Fair', 1 => 'Needs Improvement', 0 => 'Needs Improvement'];
        $rounded = (int)round((float)$row['overall_rating']);
        if ($rounded > 5) $rounded = 5;
        $mapped_overall = $overall_map[$rounded] ?? 'Fair';
        
        $stats[$dept]['overall'][$mapped_overall]++;

        if (!empty($row['recommendations'])) {
            $stats[$dept]['recoms'][] = $row['recommendations'];
            $globalRecommendations[] = $row['recommendations'];
        }
        if (!empty($row['comments'])) {
            $stats[$dept]['comments'][] = $row['comments'];
            $globalComments[] = $row['comments'];
        }
    }

    // 6. OPEN MASTER TEMPLATE
    // Ensure you put master_template.xlsx one directory up, next to db_connect.php
    $templatePath = '../master_template.xlsx';
    if (!file_exists($templatePath)) {
        die("Error: master_template.xlsx is missing! Please place it in the root folder.");
    }

    $spreadsheet = IOFactory::load($templatePath);

    // ========================================================================
    // 7. YOUR EXACT LEGACY INJECTION CODE BEGINS HERE (Unchanged)
    // ========================================================================

    foreach ($departments as $dept => $config) {
        $tabName = $config['tab'];
        $dStat = $stats[$dept];
        $total = $dStat['count'];

        $sheet = $spreadsheet->getSheetByName($tabName);
        if (!$sheet)
            continue;

        // ALWAYS update the period label, even if there's no data
        $sheet->setCellValue('B9', $reportDateLabel);

        if ($total == 0) {
            // Explicitly clear key total/mean cells so they don't show old template data
            $sheet->setCellValue('M14', 0);
            $sheet->setCellValue('M15', 0);
            $sheet->setCellValue('M16', 0);
            $sheet->setCellValue('M17', 0);
            $sheet->setCellValue('O18', 0);
            $sheet->setCellValue('M29', 0);
            $sheet->setCellValue('O29', 0);
            continue;
        }

        $calcMean = function ($arr, $vals) use ($total) {
            $sum = 0;
            foreach ($arr as $k => $count) {
                $sum += ($count * $vals[$k]);
            }
            return $total > 0 ? ($sum / $total) : 0;
        };

        $qMeans = [];
        $qRows = [14 => 'q1', 15 => 'q2', 16 => 'q3', 17 => 'q4'];

        foreach ($qRows as $row => $qKey) {
            $sheet->setCellValue('C' . $row, $dStat[$qKey]['Strongly Agree']);
            $sheet->setCellValue('D' . $row, ($dStat[$qKey]['Strongly Agree'] / $total) * 100);
            $sheet->setCellValue('E' . $row, $dStat[$qKey]['Agree']);
            $sheet->setCellValue('F' . $row, ($dStat[$qKey]['Agree'] / $total) * 100);
            $sheet->setCellValue('G' . $row, $dStat[$qKey]['Neither']);
            $sheet->setCellValue('H' . $row, ($dStat[$qKey]['Neither'] / $total) * 100);
            $sheet->setCellValue('I' . $row, $dStat[$qKey]['Disagree']);
            $sheet->setCellValue('J' . $row, ($dStat[$qKey]['Disagree'] / $total) * 100);
            $sheet->setCellValue('K' . $row, $dStat[$qKey]['Strongly Disagree']);
            $sheet->setCellValue('L' . $row, ($dStat[$qKey]['Strongly Disagree'] / $total) * 100);
            $sheet->setCellValue('M' . $row, $total);
            $sheet->setCellValue('N' . $row, 100);

            $mean = $calcMean($dStat[$qKey], $likertVals);
            $qMeans[] = $mean;
            $sheet->setCellValue('O' . $row, round($mean, 2));
        }

        $sheet->setCellValue('O18', round(array_sum($qMeans) / 4, 2));

        $sheet->setCellValue('C23', $dStat['sat']['Yes']);
        $sheet->setCellValue('D23', ($dStat['sat']['Yes'] / $total) * 100);
        $sheet->setCellValue('E23', $dStat['sat']['No']);
        $sheet->setCellValue('F23', ($dStat['sat']['No'] / $total) * 100);
        $sheet->setCellValue('G23', ($dStat['sat']['Yes'] / $total) * 5);

        $sheet->setCellValue('C29', $dStat['overall']['Excellent']);
        $sheet->setCellValue('D29', ($dStat['overall']['Excellent'] / $total) * 100);
        $sheet->setCellValue('E29', $dStat['overall']['Very Good']);
        $sheet->setCellValue('F29', ($dStat['overall']['Very Good'] / $total) * 100);
        $sheet->setCellValue('G29', $dStat['overall']['Good']);
        $sheet->setCellValue('H29', ($dStat['overall']['Good'] / $total) * 100);
        $sheet->setCellValue('I29', $dStat['overall']['Fair']);
        $sheet->setCellValue('J29', ($dStat['overall']['Fair'] / $total) * 100);
        $sheet->setCellValue('K29', $dStat['overall']['Needs Improvement']);
        $sheet->setCellValue('L29', ($dStat['overall']['Needs Improvement'] / $total) * 100);
        $sheet->setCellValue('M29', $total);
        $sheet->setCellValue('N29', 100);
        $sheet->setCellValue('O29', round($calcMean($dStat['overall'], $overallVals), 2));

        $cRow = 43;
        foreach ($dStat['recoms'] as $i => $rec) {
            $sheet->setCellValue('B' . ($cRow + $i), $rec);
        }
        foreach ($dStat['comments'] as $i => $com) {
            $sheet->setCellValue('I' . ($cRow + $i), $com);
        }
    }

    $summarySheet = $spreadsheet->getSheetByName('SUMMARY');
    if ($summarySheet) {
        $summarySheet->setCellValue('D10', $reportDateLabel);

        $grandTotal = $sumP1_Q1 = $sumP1_Q2 = $sumP1_Q3 = $sumP1_Q4 = $sumP1_Mean = $sumP2_Sat = $sumP3_Over = 0;

        foreach ($departments as $dept => $config) {
            $total = $stats[$dept]['count'];
            $p1Row = $config['sum_row'];
            $p2Row = $p1Row + 12;
            $p3Row = $p1Row + 25;

            if ($total > 0) {
                $calcM = function ($arr) use ($total, $likertVals) {
                    $s = 0;
                    foreach ($arr as $k => $v)
                        $s += ($v * $likertVals[$k]);
                    return $s / $total;
                };
                $m1 = $calcM($stats[$dept]['q1']);
                $m2 = $calcM($stats[$dept]['q2']);
                $m3 = $calcM($stats[$dept]['q3']);
                $m4 = $calcM($stats[$dept]['q4']);
                $rowMean = ($m1 + $m2 + $m3 + $m4) / 4;

                $summarySheet->setCellValue('C' . $p1Row, $total);
                $summarySheet->setCellValue('D' . $p1Row, round($m1, 2));
                $summarySheet->setCellValue('E' . $p1Row, round($m2, 2));
                $summarySheet->setCellValue('F' . $p1Row, round($m3, 2));
                $summarySheet->setCellValue('G' . $p1Row, round($m4, 2));
                $summarySheet->setCellValue('H' . $p1Row, round($rowMean, 2));

                $satMean = ($stats[$dept]['sat']['Yes'] / $total) * 5;
                $summarySheet->setCellValue('C' . $p2Row, round($satMean, 2));

                $overS = 0;
                foreach ($stats[$dept]['overall'] as $k => $v)
                    $overS += ($v * $overallVals[$k]);
                $p3Mean = $overS / $total;
                $summarySheet->setCellValue('C' . $p3Row, round($p3Mean, 2));

                $grandTotal += $total;
                $sumP1_Q1 += ($m1 * $total);
                $sumP1_Q2 += ($m2 * $total);
                $sumP1_Q3 += ($m3 * $total);
                $sumP1_Q4 += ($m4 * $total);
                $sumP1_Mean += ($rowMean * $total);
                $sumP2_Sat += ($satMean * $total);
                $sumP3_Over += ($p3Mean * $total);
            } else {
                $summarySheet->setCellValue('C' . $p1Row, '-');
                $summarySheet->setCellValue('D' . $p1Row, '');
                $summarySheet->setCellValue('E' . $p1Row, '');
                $summarySheet->setCellValue('F' . $p1Row, '');
                $summarySheet->setCellValue('G' . $p1Row, '');
                $summarySheet->setCellValue('H' . $p1Row, '');
                $summarySheet->setCellValue('C' . $p2Row, '-');
                $summarySheet->setCellValue('C' . $p3Row, '-');
            }
        }

        if ($grandTotal > 0) {
            $summarySheet->setCellValue('C22', $grandTotal);
            $summarySheet->setCellValue('D22', round($sumP1_Q1 / $grandTotal, 2));
            $summarySheet->setCellValue('E22', round($sumP1_Q2 / $grandTotal, 2));
            $summarySheet->setCellValue('F22', round($sumP1_Q3 / $grandTotal, 2));
            $summarySheet->setCellValue('G22', round($sumP1_Q4 / $grandTotal, 2));
            $summarySheet->setCellValue('H22', round($sumP1_Mean / $grandTotal, 2));
            $summarySheet->setCellValue('C34', round($sumP2_Sat / $grandTotal, 2));
            $summarySheet->setCellValue('C47', round($sumP3_Over / $grandTotal, 2));
        }
    }

    $commentsSheet = $spreadsheet->getSheetByName('Comments (Overall)');
    if ($commentsSheet) {
        $rRow = 2;
        foreach ($globalRecommendations as $rec) {
            if ($rRow > 51)
                break;
            $commentsSheet->setCellValue('C' . $rRow, $rec);
            $rRow++;
        }
        $cRow = 55;
        foreach ($globalComments as $com) {
            if ($cRow > 104)
                break;
            $commentsSheet->setCellValue('C' . $cRow, $com);
            $cRow++;
        }
    }

    // ========================================================================
    // 7.5. CREATE THE AUDIT LOG ENTRY
    // ========================================================================
    $action_type = "EXPORT_MASTER";
    $action_details = "Exported Master Template for date range: " . $reportDateLabel;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $admin_id = $_SESSION['admin_id'];

    $audit_stmt = $pdo->prepare("
        INSERT INTO audit_log (admin_id, action_type, action_details, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $audit_stmt->execute([$admin_id, $action_type, $action_details, $ip_address]);


    // 8. STREAM THE FILE DIRECTLY TO THE BROWSER
    $safeDate = str_replace([' ', ','], '_', $reportDateLabel);
    $filename = "EVAL_REPORT_{$safeDate}.xlsx";

    ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Access-Control-Expose-Headers: Content-Disposition');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    ob_end_flush();
    exit;

} catch (Exception $e) {
    die("Error compiling Master Template: " . $e->getMessage());
}