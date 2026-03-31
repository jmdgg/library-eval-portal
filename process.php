<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $fileTmpPath = $_FILES['csv_file']['tmp_name'];

    // 1. THE DATA STRUCTURE (Row Mapping & Math Buckets)
    // We map each department to its specific rows for Part 1, Part 2, and Part 3
    $departments = [
        'Circulation Section' => ['p1' => 15, 'p2' => 27, 'p3' => 40],
        'General Reference Section' => ['p1' => 16, 'p2' => 28, 'p3' => 41],
        'Computer and Multimedia Services (CMS)' => ['p1' => 17, 'p2' => 29, 'p3' => 42],
        'Health Sciences Library' => ['p1' => 18, 'p2' => 30, 'p3' => 43],
        'Filipiniana Section' => ['p1' => 19, 'p2' => 31, 'p3' => 44],
        'College of Business and Accountancy Library' => ['p1' => 20, 'p2' => 32, 'p3' => 45],
        'PS Library' => ['p1' => 21, 'p2' => 33, 'p3' => 46]
    ];

    // Initialize scoring arrays to track totals
    $stats = [];
    foreach ($departments as $dept => $rows) {
        $stats[$dept] = [
            'count' => 0,
            'q1_sum' => 0,
            'q2_sum' => 0,
            'q3_sum' => 0,
            'q4_sum' => 0,
            'sat_sum' => 0,
            'overall_sum' => 0
        ];
    }

    // Scoring Dictionaries (Translating words to numbers)
    $likert = ['Strongly Agree' => 5, 'Agree' => 4, 'Neither' => 3, 'Disagree' => 2, 'Strongly Disagree' => 1];
    $satScore = ['Yes' => 5, 'No' => 1]; // Assuming binary satisfaction is 5 for yes.
    $overallScore = ['Excellent' => 5, 'Very Good' => 4, 'Good' => 3, 'Fair' => 2, 'Needs Improvement' => 1];

    $reportDate = "UNKNOWN DATE";

    // 2. READ AND CALCULATE
    if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
        $rowCounter = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($rowCounter == 0) {
                $rowCounter++;
                continue;
            }

            // Extract Date from the first valid row to inject into D10
            if ($rowCounter == 1 && !empty($data[2])) {
                $reportDate = strtoupper(date('F Y', strtotime(trim($data[2]))));
            }

            $librarySection = trim($data[7]);
            $service = trim($data[8]);

            // Extract the 4 specific questions, plus satisfaction and overall
            $q1 = trim($data[9]);
            $q2 = trim($data[10]);
            $q3 = trim($data[11]);
            $q4 = trim($data[12]);
            $sat = trim($data[13]);
            $overall = trim($data[14]);

            // Translators
            $dict = ['Professional School Library' => 'PS Library', 'CBA Library' => 'College of Business and Accountancy Library', 'CMS Library' => 'Computer and Multimedia Services (CMS)'];
            if (array_key_exists($librarySection, $dict)) {
                $librarySection = $dict[$librarySection];
            }
            if (stripos($service, 'Similarity') !== false) {
                $librarySection = 'General Reference Section';
            }

            // Apply Math
            if (array_key_exists($librarySection, $stats)) {
                $stats[$librarySection]['count']++;

                if (isset($likert[$q1]))
                    $stats[$librarySection]['q1_sum'] += $likert[$q1];
                if (isset($likert[$q2]))
                    $stats[$librarySection]['q2_sum'] += $likert[$q2];
                if (isset($likert[$q3]))
                    $stats[$librarySection]['q3_sum'] += $likert[$q3];
                if (isset($likert[$q4]))
                    $stats[$librarySection]['q4_sum'] += $likert[$q4];

                // Case-insensitive checks for Part 2 and 3
                foreach ($satScore as $key => $val) {
                    if (strcasecmp($sat, $key) == 0)
                        $stats[$librarySection]['sat_sum'] += $val;
                }
                foreach ($overallScore as $key => $val) {
                    if (strcasecmp($overall, $key) == 0)
                        $stats[$librarySection]['overall_sum'] += $val;
                }
            }
            $rowCounter++;
        }
        fclose($handle);
    }

    // TEMPLATE INJECTION
    $templatePath = 'master_template.xlsx';
    if (!file_exists($templatePath)) {
        die("Error: master_template.xlsx is missing from the folder!");
    }

    // Open the client's formatted template
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet(); // Assumes 'SUMMARY' is the first tab

    // Inject the Date (D10)
    $sheet->setCellValue('D10', $reportDate);

    // Variables to track Grand Totals for Row 22, 34, 47
    $grandCount = 0;
    $grandQ1 = 0;
    $grandQ2 = 0;
    $grandQ3 = 0;
    $grandQ4 = 0;
    $grandSat = 0;
    $grandOverall = 0;

    // Inject data for each department
    foreach ($departments as $dept => $rows) {
        $count = $stats[$dept]['count'];
        $p1 = $rows['p1']; // Row number for Part 1
        $p2 = $rows['p2']; // Row number for Part 2
        $p3 = $rows['p3']; // Row number for Part 3

        if ($count > 0) {
            // Calculate Averages
            $avgQ1 = $stats[$dept]['q1_sum'] / $count;
            $avgQ2 = $stats[$dept]['q2_sum'] / $count;
            $avgQ3 = $stats[$dept]['q3_sum'] / $count;
            $avgQ4 = $stats[$dept]['q4_sum'] / $count;
            $rowMean = ($avgQ1 + $avgQ2 + $avgQ3 + $avgQ4) / 4;

            $avgSat = $stats[$dept]['sat_sum'] / $count;
            $avgOver = $stats[$dept]['overall_sum'] / $count;

            // Inject Part 1
            $sheet->setCellValue('C' . $p1, $count);
            $sheet->setCellValue('D' . $p1, round($avgQ1, 2));
            $sheet->setCellValue('E' . $p1, round($avgQ2, 2));
            $sheet->setCellValue('F' . $p1, round($avgQ3, 2));
            $sheet->setCellValue('G' . $p1, round($avgQ4, 2));
            $sheet->setCellValue('H' . $p1, round($rowMean, 2));

            // Inject Part 2 & 3
            $sheet->setCellValue('C' . $p2, round($avgSat, 2));
            $sheet->setCellValue('C' . $p3, round($avgOver, 2));

            // Add to Grand Totals
            $grandCount += $count;
            $grandQ1 += $stats[$dept]['q1_sum'];
            $grandQ2 += $stats[$dept]['q2_sum'];
            $grandQ3 += $stats[$dept]['q3_sum'];
            $grandQ4 += $stats[$dept]['q4_sum'];
            $grandSat += $stats[$dept]['sat_sum'];
            $grandOverall += $stats[$dept]['overall_sum'];

        } else {
            // If nobody used this library, put a dash "-" like the client does
            $sheet->setCellValue('C' . $p1, '-');
            $sheet->setCellValue('C' . $p2, '-');
            $sheet->setCellValue('C' . $p3, '-');
        }
    }

    // 4. INJECT OVERALL GRAND RATINGS
    if ($grandCount > 0) {
        $oQ1 = $grandQ1 / $grandCount;
        $oQ2 = $grandQ2 / $grandCount;
        $oQ3 = $grandQ3 / $grandCount;
        $oQ4 = $grandQ4 / $grandCount;
        $oMean = ($oQ1 + $oQ2 + $oQ3 + $oQ4) / 4;

        // Part 1 Overall (Row 22)
        $sheet->setCellValue('C22', $grandCount);
        $sheet->setCellValue('D22', round($oQ1, 2));
        $sheet->setCellValue('E22', round($oQ2, 2));
        $sheet->setCellValue('F22', round($oQ3, 2));
        $sheet->setCellValue('G22', round($oQ4, 2));
        $sheet->setCellValue('H22', round($oMean, 2));

        // Part 2 & 3 Overall (Row 34, 47)
        $sheet->setCellValue('C34', round($grandSat / $grandCount, 2));
        $sheet->setCellValue('C47', round($grandOverall / $grandCount, 2));
    }

    // Inject blank space for Analysis paragraph (Cell B58)
    $sheet->setCellValue('B58', '[Please type your manual analysis here]');

    // 4.5. APP DATA FOR PREVIEW DASHBOARD
    
    // Extract Year and Month from reportDate (e.g. "DECEMBER 2022")
    $p = explode(' ', $reportDate);
    $rMonthName = count($p) > 0 ? $p[0] : '';
    $rYear = count($p) > 1 ? $p[1] : '';
    
    $mMap = ['JANUARY'=>'01', 'FEBRUARY'=>'02', 'MARCH'=>'03', 'APRIL'=>'04', 'MAY'=>'05', 'JUNE'=>'06', 'JULY'=>'07', 'AUGUST'=>'08', 'SEPTEMBER'=>'09', 'OCTOBER'=>'10', 'NOVEMBER'=>'11', 'DECEMBER'=>'12'];
    $rMonth = isset($mMap[$rMonthName]) ? $mMap[$rMonthName] : '';
    
    $analyzedData = [];
    $deptMap = [
        'Circulation Section' => 'CIRC',
        'General Reference Section' => 'GEN REF',
        'Computer and Multimedia Services (CMS)' => 'CMS',
        'Health Sciences Library' => 'HSL',
        'Filipiniana Section' => 'FILNA',
        'College of Business and Accountancy Library' => 'CBA',
        'PS Library' => 'PS'
    ];
    foreach ($departments as $dept => $rows) {
        $count = $stats[$dept]['count'];
        if ($count > 0) {
            $analyzedData[] = [
                'Year' => $rYear,
                'Month' => $rMonth,
                'branch' => $deptMap[$dept],
                'respondents' => $count,
                'partIMean1' => round($stats[$dept]['q1_sum'] / $count, 2),
                'partIMean2' => round($stats[$dept]['q2_sum'] / $count, 2),
                'partIMean3' => round($stats[$dept]['q3_sum'] / $count, 2),
                'partIMean4' => round($stats[$dept]['q4_sum'] / $count, 2),
                'partIISatisfaction' => round($stats[$dept]['sat_sum'] / $count, 2),
                'partIIIOverallRating' => round($stats[$dept]['overall_sum'] / $count, 2)
            ];
        } else {
            $analyzedData[] = [
                'Year' => $rYear,
                'Month' => $rMonth,
                'branch' => $deptMap[$dept],
                'respondents' => '-',
                'partIMean1' => '-',
                'partIMean2' => '-',
                'partIMean3' => '-',
                'partIMean4' => '-',
                'partIISatisfaction' => '-',
                'partIIIOverallRating' => '-'
            ];
        }
    }
    if ($grandCount > 0) {
            $analyzedData[] = [
                'Year' => $rYear,
                'Month' => $rMonth,
                'branch' => 'OVERALL RATING',
                'respondents' => $grandCount,
                'partIMean1' => round($grandQ1 / $grandCount, 2),
                'partIMean2' => round($grandQ2 / $grandCount, 2),
                'partIMean3' => round($grandQ3 / $grandCount, 2),
                'partIMean4' => round($grandQ4 / $grandCount, 2),
                'partIISatisfaction' => round($grandSat / $grandCount, 2),
                'partIIIOverallRating' => round($grandOverall / $grandCount, 2)
            ];
        } else {
            $analyzedData[] = [
                'Year' => $rYear,
                'Month' => $rMonth,
                'branch' => 'OVERALL RATING',
                'respondents' => '-',
                'partIMean1' => '-',
                'partIMean2' => '-',
                'partIMean3' => '-',
                'partIMean4' => '-',
                'partIISatisfaction' => '-',
                'partIIIOverallRating' => '-'
            ];
        }

    // 5. EXPORT AND DOWNLOAD (VIA JSON AJAX)
    $fileName = 'EVAL_REPORT_' . str_replace(' ', '_', $reportDate) . '.xlsx';

    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    $excelContent = ob_get_contents();
    ob_end_clean();

    $base64Excel = base64_encode($excelContent);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'fileName' => $fileName,
        'excelBase64' => $base64Excel,
        'analyzedSummaryData' => $analyzedData,
        'reportDate' => $reportDate
    ]);
    exit;

} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error processing file. Please ensure you selected a CSV.']);
    exit;
}
?>