<?php
/**
 * demo_data_seeder.php
 * Generates human-like test data for the AUF Library System.
 * Uses realistic name and email generation.
 */

require_once __DIR__ . '/../db_connect.php';

$recordsToGenerate = 200; // Increased for better density
$startDate = strtotime('2024-01-01');
$endDate = time();

echo "<h2>Populating Human-Like Survey Data...</h2>";

try {
    $pdo->beginTransaction();

    // Fetch IDs for lookups
    $patronTypes = $pdo->query("SELECT patron_type_id, type_name FROM patron_type")->fetchAll(PDO::FETCH_ASSOC);
    $colleges = $pdo->query("SELECT college_id, college_name FROM college")->fetchAll(PDO::FETCH_ASSOC);
    $libDepts = $pdo->query("SELECT lib_dept_id, dept_name FROM library_department")->fetchAll(PDO::FETCH_ASSOC);
    $services = $pdo->query("SELECT service_id, service_name FROM library_service")->fetchAll(PDO::FETCH_ASSOC);
    $questions = $pdo->query("SELECT question_id FROM question_metric")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($patronTypes) || empty($libDepts)) {
        throw new Exception("Structure not seeded. Please run initial_setup.php first.");
    }

    // Name Pools
    $firstNames = ['Juan', 'Maria', 'Jose', 'Liza', 'Antonio', 'Cristina', 'Ricardo', 'Elena', 'Fernando', 'Sofia', 'Miguel', 'Isabella', 'Gabriel', 'Angelica', 'Paolo', 'Teresa', 'Eduardo', 'Patricia', 'Roberto', 'Monica'];
    $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Bautista', 'Ocampo', 'Mendoza', 'Pascual', 'Aquino', 'Gonzales', 'Villanueva', 'Ramos', 'Castro', 'Del Rosario', 'Sarmiento'];
    
    $commentsPool = [
        'Excellent service, the staff is very accommodating.',
        'The library environment is very conducive for studying.',
        'Could we have more charging stations in the CMS area?',
        'The borrowing process is much faster now, thank you!',
        'Maybe extend the library hours during final exam week?',
        'Very helpful staff at the Filipiniana section.',
        'The internet connection is a bit slow sometimes.',
        'Love the new quiet zone rules.',
        'Thank you for providing the scanned documents quickly.',
        'More current editions for nursing textbooks would be great.',
        'I appreciate the assistance with my thesis research.'
    ];

    $recommendationsPool = [
        'Install more power outlets.',
        'Add more ergonomic chairs.',
        'Provide a water fountain nearby.',
        'Update the computer hardware in CMS.',
        'Improve the air conditioning in the General Reference section.',
        'Acquire more digital resource subscriptions.',
        'Conduct more library orientation for freshmen.'
    ];

    // Helper: Find or Create Evaluation Period
    function getOrCreatePeriod($pdo, $timestamp) {
        $start_date = date('Y-m-01', $timestamp);
        $end_date = date('Y-m-t', $timestamp);
        $stmt = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE start_date = ? AND end_date = ?");
        $stmt->execute([$start_date, $end_date]);
        $id = $stmt->fetchColumn();
        if ($id) return $id;

        $insert = $pdo->prepare("INSERT INTO evaluation_period (start_date, end_date) VALUES (?, ?)");
        $insert->execute([$start_date, $end_date]);
        return $pdo->lastInsertId();
    }

    $parentStmt = $pdo->prepare("
        INSERT INTO survey_submission 
        (period_id, lib_dept_id, patron_type_id, other_patron_details, college_id, acad_dept_id, email, is_satisfied, overall_rating, recommendations, comments, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $childStmt = $pdo->prepare("INSERT INTO response_detail (submission_id, question_id, score) VALUES (?, ?, ?)");
    $junctionStmt = $pdo->prepare("INSERT INTO submission_service (submission_id, service_id) VALUES (?, ?)");

    for ($i = 0; $i < $recordsToGenerate; $i++) {
        $randomTS = mt_rand($startDate, $endDate);
        $periodId = getOrCreatePeriod($pdo, $randomTS);
        
        $fname = $firstNames[array_rand($firstNames)];
        $lname = $lastNames[array_rand($lastNames)];
        $domain = (rand(0, 1) == 0) ? "@auf.edu.ph" : "@student.auf.edu.ph";
        $email = strtolower($fname . "." . str_replace(' ', '', $lname) . rand(1, 99) . $domain);
        
        $libDept = $libDepts[array_rand($libDepts)];
        $patron = $patronTypes[array_rand($patronTypes)];
        
        // Match college logic
        $collegeId = null;
        $acadDeptId = null;
        if (!in_array($patron['type_name'], ['NTP', 'Other'])) {
            $college = $colleges[array_rand($colleges)];
            $collegeId = $college['college_id'];
            // Get random dept for this college
            $dStmt = $pdo->prepare("SELECT acad_dept_id FROM academic_department WHERE college_id = ?");
            $dStmt->execute([$collegeId]);
            $depts = $dStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($depts)) $acadDeptId = $depts[array_rand($depts)];
        }

        $isSat = (rand(0, 10) > 1) ? 1 : 0; // 90% satisfied
        $scores = [];
        foreach ($questions as $qId) {
            $score = ($isSat == 1) ? rand(4, 5) : rand(1, 3);
            $scores[$qId] = $score;
        }
        $avgRating = array_sum($scores) / count($scores);
        
        $comm = (rand(0, 3) > 0) ? $commentsPool[array_rand($commentsPool)] : '';
        $reco = (rand(0, 3) == 0) ? $recommendationsPool[array_rand($recommendationsPool)] : '';
        
        $parentStmt->execute([
            $periodId, $libDept['lib_dept_id'], $patron['patron_type_id'], null, $collegeId, $acadDeptId, $email, $isSat, $avgRating, $reco, $comm, date('Y-m-d H:i:s', $randomTS)
        ]);
        $subId = $pdo->lastInsertId();

        foreach ($scores as $qId => $score) {
            $childStmt->execute([$subId, $qId, $score]);
        }

        // Random services
        $numS = rand(1, 2);
        $shuffled = $services; shuffle($shuffled);
        for($j=0; $j<$numS; $j++) {
            $junctionStmt->execute([$subId, $shuffled[$j]['service_id']]);
        }
    }

    $pdo->commit();
    echo "<p style='color:green; font-weight:bold;'>SUCCESS: Generated {$recordsToGenerate} human-like records.</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<p style='color:red;'>Seeding Failed: " . $e->getMessage() . "</p>";
}
