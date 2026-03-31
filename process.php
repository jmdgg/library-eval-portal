<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
    
    // 1. THE DATA STRUCTURE (Granular Tracking)
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

    // Build the empty buckets for every department
    foreach ($departments as $dept => $config) {
        $stats[$dept] = [
            'count' => 0,
            'q1' => ['Strongly Agree'=>0, 'Agree'=>0, 'Neither'=>0, 'Disagree'=>0, 'Strongly Disagree'=>0],
            'q2' => ['Strongly Agree'=>0, 'Agree'=>0, 'Neither'=>0, 'Disagree'=>0, 'Strongly Disagree'=>0],
            'q3' => ['Strongly Agree'=>0, 'Agree'=>0, 'Neither'=>0, 'Disagree'=>0, 'Strongly Disagree'=>0],
            'q4' => ['Strongly Agree'=>0, 'Agree'=>0, 'Neither'=>0, 'Disagree'=>0, 'Strongly Disagree'=>0],
            'sat' => ['Yes'=>0, 'No'=>0],
            'overall' => ['Excellent'=>0, 'Very Good'=>0, 'Good'=>0, 'Fair'=>0, 'Needs Improvement'=>0],
            'recoms' => [],
            'comments' => []
        ];
    }

    $likertVals = ['Strongly Agree'=>5, 'Agree'=>4, 'Neither'=>3, 'Disagree'=>2, 'Strongly Disagree'=>1];
    $overallVals = ['Excellent'=>5, 'Very Good'=>4, 'Good'=>3, 'Fair'=>2, 'Needs Improvement'=>1];
    $reportDate = "UNKNOWN DATE";

    // 2. DATA EXTRACTION AND ROUTING
    if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
        $rowCounter = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($rowCounter == 0) { $rowCounter++; continue; }
            
            if ($rowCounter == 1 && !empty($data[2])) {
                $reportDate = strtoupper(date('F Y', strtotime(trim($data[2]))));
            }

            $librarySection = trim($data[7]); 
            $service = trim($data[8]); 
            $q1 = trim($data[9]); $q2 = trim($data[10]); $q3 = trim($data[11]); $q4 = trim($data[12]); 
            $sat = trim($data[13]); $overall = trim($data[14]); 
            $recom = trim($data[15]); $comment = trim($data[16]); 

            // Translators
            $dict = ['Professional School Library' => 'PS Library', 'CBA Library' => 'College of Business and Accountancy Library', 'CMS Library' => 'Computer and Multimedia Services (CMS)'];
            if (array_key_exists($librarySection, $dict)) { $librarySection = $dict[$librarySection]; }
            if (stripos($service, 'Similarity') !== false) { $librarySection = 'General Reference Section'; }

            if (array_key_exists($librarySection, $stats)) {
                $stats[$librarySection]['count']++;
                
                // Tally Likert Responses
                if (isset($stats[$librarySection]['q1'][$q1])) $stats[$librarySection]['q1'][$q1]++;
                if (isset($stats[$librarySection]['q2'][$q2])) $stats[$librarySection]['q2'][$q2]++;
                if (isset($stats[$librarySection]['q3'][$q3])) $stats[$librarySection]['q3'][$q3]++;
                if (isset($stats[$librarySection]['q4'][$q4])) $stats[$librarySection]['q4'][$q4]++;
                
                if (strcasecmp($sat, 'Yes') == 0) $stats[$librarySection]['sat']['Yes']++;
                elseif (strcasecmp($sat, 'No') == 0) $stats[$librarySection]['sat']['No']++;

                // Case-insensitive matching for Overall
                foreach($stats[$librarySection]['overall'] as $key => $val) {
                    if(strcasecmp($overall, $key) == 0) $stats[$librarySection]['overall'][$key]++;
                }

                // Collect Text Responses
                if (!empty($recom)) {
                    $stats[$librarySection]['recoms'][] = $recom;
                    $globalRecommendations[] = $recom;
                }
                if (!empty($comment)) {
                    $stats[$librarySection]['comments'][] = $comment;
                    $globalComments[] = $comment;
                }
            }
            $rowCounter++;
        }
        fclose($handle);
    }

    // 3. MULTI-SHEET INJECTION
    $templatePath = 'master_template.xlsx';
    if (!file_exists($templatePath)) { die("Error: master_template.xlsx is missing!"); }

    $spreadsheet = IOFactory::load($templatePath);

    // --- A. INJECT DEPARTMENT SHEETS ---
    foreach ($departments as $dept => $config) {
        $tabName = $config['tab'];
        $dStat = $stats[$dept];
        $total = $dStat['count'];

        if ($total == 0) continue; // Skip if no data for this department

        $sheet = $spreadsheet->getSheetByName($tabName);
        if (!$sheet) continue; // Safety check if tab is missing

        $sheet->setCellValue('B9', $reportDate);

        // Helper function to calculate mean
        $calcMean = function($arr, $vals) use ($total) {
            $sum = 0;
            foreach ($arr as $k => $count) { $sum += ($count * $vals[$k]); }
            return $total > 0 ? ($sum / $total) : 0;
        };

        $qMeans = [];
        $qRows = [14 => 'q1', 15 => 'q2', 16 => 'q3', 17 => 'q4'];
        
        // Loop Rows 14 to 17
        foreach ($qRows as $row => $qKey) {
            $sheet->setCellValue('C'.$row, $dStat[$qKey]['Strongly Agree']);
            $sheet->setCellValue('D'.$row, ($dStat[$qKey]['Strongly Agree'] / $total) * 100);
            
            $sheet->setCellValue('E'.$row, $dStat[$qKey]['Agree']);
            $sheet->setCellValue('F'.$row, ($dStat[$qKey]['Agree'] / $total) * 100);
            
            $sheet->setCellValue('G'.$row, $dStat[$qKey]['Neither']);
            $sheet->setCellValue('H'.$row, ($dStat[$qKey]['Neither'] / $total) * 100);
            
            $sheet->setCellValue('I'.$row, $dStat[$qKey]['Disagree']);
            $sheet->setCellValue('J'.$row, ($dStat[$qKey]['Disagree'] / $total) * 100);
            
            $sheet->setCellValue('K'.$row, $dStat[$qKey]['Strongly Disagree']);
            $sheet->setCellValue('L'.$row, ($dStat[$qKey]['Strongly Disagree'] / $total) * 100);
            
            $sheet->setCellValue('M'.$row, $total);
            $sheet->setCellValue('N'.$row, 100); // 100%
            
            $mean = $calcMean($dStat[$qKey], $likertVals);
            $qMeans[] = $mean;
            $sheet->setCellValue('O'.$row, round($mean, 2));
        }

        // Row 18: Overall Mean Rating for Part 1
        $sheet->setCellValue('O18', round(array_sum($qMeans) / 4, 2));

        // Row 23: Satisfaction
        $sheet->setCellValue('C23', $dStat['sat']['Yes']);
        $sheet->setCellValue('D23', ($dStat['sat']['Yes'] / $total) * 100);
        $sheet->setCellValue('E23', $dStat['sat']['No']);
        $sheet->setCellValue('F23', ($dStat['sat']['No'] / $total) * 100);
        $sheet->setCellValue('G23', ($dStat['sat']['Yes'] / $total) * 5); // Assuming Yes=5

        // Row 29: Overall Rating
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

        // Part IV: Department Specific Comments
        $cRow = 43;
        foreach ($dStat['recoms'] as $i => $rec) {
            $sheet->setCellValue('B'.($cRow+$i), $rec);
        }
        foreach ($dStat['comments'] as $i => $com) {
            $sheet->setCellValue('I'.($cRow+$i), $com);
        }
    }

    // --- B. INJECT SUMMARY SHEET ---
    $summarySheet = $spreadsheet->getSheetByName('SUMMARY');
    // --- NEW: Initialize array for Frontend JSON ---
        $frontendJSON = [];
        $dateParts = explode(' ', $reportDate); 
        $monthMap = ['JANUARY'=>'01', 'FEBRUARY'=>'02', 'MARCH'=>'03', 'APRIL'=>'04', 'MAY'=>'05', 'JUNE'=>'06', 'JULY'=>'07', 'AUGUST'=>'08', 'SEPTEMBER'=>'09', 'OCTOBER'=>'10', 'NOVEMBER'=>'11', 'DECEMBER'=>'12'];
        $jsonYear = isset($dateParts[1]) ? $dateParts[1] : date('Y');
        $jsonMonth = isset($dateParts[0]) && isset($monthMap[$dateParts[0]]) ? $monthMap[$dateParts[0]] : date('m');
        
        // Map PHP department names to the shortcodes your JS expects
        $jsBranchMap = [
            'Circulation Section' => 'CIRC',
            'General Reference Section' => 'GEN REF',
            'Computer and Multimedia Services (CMS)' => 'CMS',
            'Health Sciences Library' => 'HSL',
            'Filipiniana Section' => 'FILNA',
            'College of Business and Accountancy Library' => 'CBA',
            'PS Library' => 'PS'
        ];
    if ($summarySheet) {
        $summarySheet->setCellValue('D10', $reportDate);

        
        
        // --- NEW: Master aggregators for the Overall Rating rows ---
        $grandTotal = 0;
        $sumP1_Q1 = 0; $sumP1_Q2 = 0; $sumP1_Q3 = 0; $sumP1_Q4 = 0; $sumP1_Mean = 0;
        $sumP2_Sat = 0;
        $sumP3_Over = 0;

        foreach ($departments as $dept => $config) {
            $total = $stats[$dept]['count'];
            $p1Row = $config['sum_row'];
            $p2Row = $p1Row + 12; // e.g. CIRC is 15 for P1, 27 for P2
            $p3Row = $p1Row + 25; // e.g. CIRC is 15 for P1, 40 for P3

            if ($total > 0) {
                // Calculate P1 Means
                $calcM = function($arr) use ($total, $likertVals) {
                    $s = 0; foreach($arr as $k=>$v) $s+=($v*$likertVals[$k]); return $s/$total;
                };
                $m1 = $calcM($stats[$dept]['q1']); $m2 = $calcM($stats[$dept]['q2']);
                $m3 = $calcM($stats[$dept]['q3']); $m4 = $calcM($stats[$dept]['q4']);
                $rowMean = ($m1+$m2+$m3+$m4)/4;

                $summarySheet->setCellValue('C'.$p1Row, $total);
                $summarySheet->setCellValue('D'.$p1Row, round($m1, 2));
                $summarySheet->setCellValue('E'.$p1Row, round($m2, 2));
                $summarySheet->setCellValue('F'.$p1Row, round($m3, 2));
                $summarySheet->setCellValue('G'.$p1Row, round($m4, 2));
                $summarySheet->setCellValue('H'.$p1Row, round($rowMean, 2));

                $satMean = ($stats[$dept]['sat']['Yes'] / $total) * 5;
                $summarySheet->setCellValue('C'.$p2Row, round($satMean, 2));

                $overS = 0; foreach($stats[$dept]['overall'] as $k=>$v) $overS+=($v*$overallVals[$k]);
                $p3Mean = $overS/$total;
                $summarySheet->setCellValue('C'.$p3Row, round($p3Mean, 2));
                
                // --- NEW: Add to master aggregators (Weighted by respondents) ---
                $grandTotal += $total;
                $sumP1_Q1 += ($m1 * $total);
                $sumP1_Q2 += ($m2 * $total);
                $sumP1_Q3 += ($m3 * $total);
                $sumP1_Q4 += ($m4 * $total);
                $sumP1_Mean += ($rowMean * $total);
                $sumP2_Sat += ($satMean * $total);
                $sumP3_Over += ($p3Mean * $total);
                // --- NEW: Push Department Data to Frontend JSON ---
                $jsBranch = isset($jsBranchMap[$dept]) ? $jsBranchMap[$dept] : $dept;
                $frontendJSON[] = [
                    'branch' => $jsBranch,
                    'Year' => $jsonYear,
                    'Month' => $jsonMonth,
                    'respondents' => $total,
                    'partIMean1' => round($m1, 2),
                    'partIMean2' => round($m2, 2),
                    'partIMean3' => round($m3, 2),
                    'partIMean4' => round($m4, 2),
                    'partIISatisfaction' => round($satMean, 2),
                    'partIIIOverallRating' => round($p3Mean, 2)
                ];
            } else {
                $summarySheet->setCellValue('C'.$p1Row, '-');
                $summarySheet->setCellValue('C'.$p2Row, '-');
                $summarySheet->setCellValue('C'.$p3Row, '-');
            }
        }
        
        // --- NEW: Calculate and Inject OVERALL RATING rows ---
        if ($grandTotal > 0) {
            // Part I Overall (Row 22 based on your PS library ending at row 21)
            $summarySheet->setCellValue('C22', $grandTotal);
            $summarySheet->setCellValue('D22', round($sumP1_Q1 / $grandTotal, 2));
            $summarySheet->setCellValue('E22', round($sumP1_Q2 / $grandTotal, 2));
            $summarySheet->setCellValue('F22', round($sumP1_Q3 / $grandTotal, 2));
            $summarySheet->setCellValue('G22', round($sumP1_Q4 / $grandTotal, 2));
            $summarySheet->setCellValue('H22', round($sumP1_Mean / $grandTotal, 2));

            // Part II Overall (Row 34)
            $summarySheet->setCellValue('C34', round($sumP2_Sat / $grandTotal, 2));

            // Part III Overall (Row 47)
            $summarySheet->setCellValue('C47', round($sumP3_Over / $grandTotal, 2));
            // --- NEW: Add Master Overall Row to Frontend JSON ---
            $frontendJSON[] = [
                'branch' => 'OVERALL RATING',
                'Year' => $jsonYear,
                'Month' => $jsonMonth,
                'respondents' => $grandTotal,
                'partIMean1' => round($sumP1_Q1 / $grandTotal, 2),
                'partIMean2' => round($sumP1_Q2 / $grandTotal, 2),
                'partIMean3' => round($sumP1_Q3 / $grandTotal, 2),
                'partIMean4' => round($sumP1_Q4 / $grandTotal, 2),
                'partIISatisfaction' => round($sumP2_Sat / $grandTotal, 2),
                'partIIIOverallRating' => round($sumP3_Over / $grandTotal, 2)
            ];
        }
    }


    // --- C. INJECT GLOBAL COMMENTS SHEET ---
    $commentsSheet = $spreadsheet->getSheetByName('Comments (Overall)');
    if ($commentsSheet) {
        // Recommendations (C2 to C51)
        $rRow = 2;
        foreach ($globalRecommendations as $rec) {
            if ($rRow > 51) break; // Hard limit based on your mapping
            $commentsSheet->setCellValue('C'.$rRow, $rec);
            $rRow++;
        }

        // Suggestions/Comments (C55 to C104)
        $cRow = 55;
        foreach ($globalComments as $com) {
            if ($cRow > 104) break; 
            $commentsSheet->setCellValue('C'.$cRow, $com);
            $cRow++;
        }
    }

   // 4. SAVE FILE AND EXPORT JSON FOR FRONTEND
    $fileName = 'EVAL_REPORT_' . str_replace(' ', '_', $reportDate) . '.xlsx';
    $saveDir = __DIR__ . '/exports/';
    
    // Safety check: Create the directory if it doesn't exist
    if (!is_dir($saveDir)) {
        mkdir($saveDir, 0755, true);
    }
    
    $savePath = $saveDir . $fileName;
    $downloadUrl = 'exports/' . $fileName;

    // Save the Excel file to the server instead of forcing a download
    $writer = new Xlsx($spreadsheet);
    $writer->save($savePath);

    // Send the JSON payload back to the browser
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'reportDate' => $reportDate,
        'downloadUrl' => $downloadUrl,
        'data' => $frontendJSON 
    ]);
    exit;

} else {
    echo "Error processing file.";
}
?>