<?php
/**
 * data_seeder.php
 * Generates realistic test data for the 3NF Library DB.
 * * WARNING: DELETE THIS FILE BEFORE GIVING THE SYSTEM TO THE CLIENT.
 */

require_once __DIR__ . '/../db_connect.php';

$recordsToGenerate = 150;
$startDate = strtotime('2024-01-01');
$endDate = strtotime('2026-04-10');

echo "<h2>Starting Database Seeder...</h2>";

try {
    // We disable foreign key checks temporarily to clear data
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE response_detail");
    $pdo->exec("TRUNCATE TABLE submission_service");
    $pdo->exec("TRUNCATE TABLE survey_submission");
    $pdo->exec("TRUNCATE TABLE evaluation_period");
    $pdo->exec("TRUNCATE TABLE patron_type");
    $pdo->exec("TRUNCATE TABLE college");
    $pdo->exec("TRUNCATE TABLE academic_department");
    $pdo->exec("TRUNCATE TABLE library_department");
    $pdo->exec("TRUNCATE TABLE library_service");
    $pdo->exec("TRUNCATE TABLE question_metric");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->beginTransaction();

    // 1. Seed Patron Types (Student first)
    $roles = ['Student', 'Faculty', 'NTP', 'Alumni', 'Other Researcher'];
    $roleIds = [];
    $stmt = $pdo->prepare("INSERT INTO patron_type (type_name) VALUES (?)");
    foreach ($roles as $r) { $stmt->execute([$r]); $roleIds[] = $pdo->lastInsertId(); }

    // 2. Seed Colleges & Academic Departments
    $collegeDepts = [
        "Integrated School (IS)" => ["Pre-Kinder", "Kindergarten", "Grade School", "Junior High School", "Senior High School"],
        "College of Allied Medical Professions (CAMP)" => ["BS Medical Technology", "BS Occupational Therapy", "BS Pharmacy", "BS Clinical Pharmacy", "BS Radiologic Technology", "BS Physical Therapy", "BS Physical Therapy Professional Enhancement Program"],
        "College of Arts and Sciences (CAS)" => ["AB Communication", "BS Biology", "BS Biology Three-Year Accelerated Program", "BS Psychology", "AB Psychology", "BS in Human Biology", "Straight AB Psychology - MA Psychology Program"],
        "College of Business and Accountancy (CBA)" => ["BS Accountancy", "BS Management Accounting", "BS Business Administration", "BS Hospitality Management", "BS Tourism Management"],
        "College of Computer Studies (CCS)" => ["Bachelor of Multimedia Arts", "BS Computer Science", "BS Information Technology"],
        "College of Criminal Justice Education (CCJE)" => ["BS Criminology"],
        "College of Engineering and Architecture (CEA)" => ["BS Architecture", "BS Civil Engineering", "BS Computer Engineering", "BS Electronics Engineering"],
        "College of Education (CED)" => ["Bachelor of Elementary Education", "Bachelor of Secondary Education", "Bachelor of Early Childhood Education", "Bachelor of Special Needs Education", "Professional Certificate Course in Teaching"],
        "College of Nursing (CON)" => ["BS Nursing"],
        "School of Law (SOL)" => ["Juris Doctor", "Other"],
        "School of Medicine (SOM)" => ["Doctor of Medicine", "Other"],
        "Graduate School (GS)" => ["Education Programs", "Psychology Program", "Business Programs", "Information Technology Programs", "Public Health Programs", "Medical Laboratory Science Programs", "Nursing Programs", "Criminal Justice Program", "Other"]
    ];

    $collegeIds = [];
    $acadDeptIds = []; // college_id => [dept_id, dept_id, ...]

    $collegeStmt = $pdo->prepare("INSERT INTO college (college_name) VALUES (?)");
    $deptStmt = $pdo->prepare("INSERT INTO academic_department (college_id, dept_name) VALUES (?, ?)");

    foreach ($collegeDepts as $collegeName => $depts) {
        $collegeStmt->execute([$collegeName]);
        $cId = $pdo->lastInsertId();
        $collegeIds[] = $cId;
        $acadDeptIds[$cId] = [];
        
        foreach ($depts as $deptName) {
            $deptStmt->execute([$cId, $deptName]);
            $acadDeptIds[$cId][] = $pdo->lastInsertId();
        }
    }

    // 3. Seed Library Departments
    $libDepts = [
        'Circulation Section',
        'General Reference Section',
        'Computer and Multimedia Services (CMS)',
        'Health Sciences Library',
        'Filipiniana Section',
        'College of Business and Accountancy Library',
        'PS Library'
    ];
    $libDeptIds = [];
    $stmt = $pdo->prepare("INSERT INTO library_department (dept_name) VALUES (?)");
    foreach ($libDepts as $d) { $stmt->execute([$d]); $libDeptIds[] = $pdo->lastInsertId(); }

    // 4. Seed Library Services
    $services = [
        'Borrowing / Renewal / Returning library material',
        'Document Delivery (Scanned Documents)',
        'Reference Service (includes request for booklist, resources relative to a query/topic, etc)',
        'One-on-one Library Online Tutorial Service',
        'Library Instruction Service (Class / Embedded Session)',
        'Clearance Request',
        'Similarity Scanning Service (Turnitin)',
        'Login Credentials (user name and password)',
        'Book Recommendation for Purchase',
        'Others'
    ];
    $serviceIds = [];
    $stmt = $pdo->prepare("INSERT INTO library_service (service_name) VALUES (?)");
    foreach ($services as $s) { $stmt->execute([$s]); $serviceIds[] = $pdo->lastInsertId(); }

    // 5. Seed Question Metrics
    $questions = [
        ['Feedback', 'The library has sufficient resources for my research and information needs', 5],
        ['Feedback', 'Library staff provided assistance in a timely and helpful manner', 5],
        ['Feedback', 'The process of borrowing, returning and renewal of library resources is convenient', 5],
        ['Feedback', 'The information/procedure provided by the library staff were easy to understand', 5]
    ];
    $qIds = [];
    $stmt = $pdo->prepare("INSERT INTO question_metric (category, question_text, max_score) VALUES (?, ?, ?)");
    foreach ($questions as $q) { $stmt->execute($q); $qIds[] = $pdo->lastInsertId(); }

    // 6. Insertion Preparation
    $parent_stmt = $pdo->prepare("
        INSERT INTO survey_submission 
        (period_id, lib_dept_id, patron_type_id, college_id, acad_dept_id, email, is_satisfied, overall_rating, recommendations, comments, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $child_stmt = $pdo->prepare("INSERT INTO response_detail (submission_id, question_id, score) VALUES (?, ?, ?)");
    $junction_stmt = $pdo->prepare("INSERT INTO submission_service (submission_id, service_id) VALUES (?, ?)");

    // Helper: Find or Create Evaluation Period
    function getPeriodId($pdo, $timestamp) {
        $start_date = date('Y-m-01', $timestamp);
        $end_date = date('Y-m-t', $timestamp);

        $stmt = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE start_date = ? AND end_date = ?");
        $stmt->execute([$start_date, $end_date]);
        $row = $stmt->fetch();
        if ($row) return $row['period_id'];

        $insert = $pdo->prepare("INSERT INTO evaluation_period (start_date, end_date, is_processed) VALUES (?, ?, 0)");
        $insert->execute([$start_date, $end_date]);
        return $pdo->lastInsertId();
    }

    $satisfaction = [1, 1, 1, 1, 0]; // 80% chance of Yes
    $comments = ['Great service!', 'Too cold in CMS.', 'Very helpful.', 'More sockets please.', '', '', ''];

    for ($i = 0; $i < $recordsToGenerate; $i++) {
        $randomTimestamp = mt_rand($startDate, $endDate);
        $created_at = date('Y-m-d H:i:s', $randomTimestamp);
        $period_id = getPeriodId($pdo, $randomTimestamp);

        $dept_id = $libDeptIds[array_rand($libDeptIds)];
        $role_id = $roleIds[array_rand($roleIds)];
        
        // NTPs might have NULL college
        $college_id = ($role_id == $roleIds[2]) ? null : $collegeIds[array_rand($collegeIds)];
        $acad_dept_id = ($college_id && isset($acadDeptIds[$college_id])) ? $acadDeptIds[$college_id][array_rand($acadDeptIds[$college_id])] : null;
        
        $email = "test_user_" . rand(1000, 9999) . "@auf.edu.ph";
        $is_sat = $satisfaction[array_rand($satisfaction)];
        $comment = $comments[array_rand($comments)];

        // Generate scores
        $scores = [];
        for ($q = 0; $q < 4; $q++) {
            $scores[] = ($is_sat == 1) ? rand(3, 5) : rand(1, 3);
        }
        $overall = round(array_sum($scores) / count($scores), 2);

        $parent_stmt->execute([
            $period_id, $dept_id, $role_id, $college_id, $acad_dept_id, $email, $is_sat, $overall, '', $comment, $created_at
        ]);
        $submission_id = $pdo->lastInsertId();

        // Insert Child Records
        foreach ($qIds as $idx => $qId) {
            $child_stmt->execute([$submission_id, $qId, $scores[$idx]]);
        }

        // Insert Junction Records (Services)
        $num_services = rand(1, 3);
        $shuffled_services = $serviceIds;
        shuffle($shuffled_services);
        $selected_services = array_slice($shuffled_services, 0, $num_services);
        foreach ($selected_services as $sId) {
            $junction_stmt->execute([$submission_id, $sId]);
        }
    }

    $pdo->commit();
    echo "<p style='color:green; font-weight:bold;'>Successfully seeded {$recordsToGenerate} 3NF-compliant records with full original names!</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo "<p style='color:red;'>Seeding Failed: " . $e->getMessage() . "</p>";
}
?>