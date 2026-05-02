<?php
/**
 * initial_setup.php
 * Initializes the AUF Library Evaluation Portal with all necessary lookup data 
 * and a default administrator account. Run this on any new deployment.
 */

require_once 'db_connect.php';

echo "<h2>Initializing AUF Library System...</h2>";

try {
    $pdo->beginTransaction();

    // 1. Seed Patron Types
    $roles = ['Student', 'Faculty', 'NTP', 'Alumni', 'Other Researcher', 'Other'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO patron_type (type_name) VALUES (?)");
    foreach ($roles as $r) { $stmt->execute([$r]); }
    echo "✓ Patron Types initialized.<br>";

    // 2. Seed Colleges & Academic Departments
    $collegeDepts = [
        "Integrated School (IS)" => ["Pre-Kinder", "Kindergarten", "Grade School", "Junior High School", "Senior High School"],
        "College of Allied Medical Professions (CAMP)" => ["BS Medical Technology", "BS Occupational Therapy", "BS Pharmacy", "BS Clinical Pharmacy", "BS Radiologic Technology", "BS Physical Therapy"],
        "College of Arts and Sciences (CAS)" => ["AB Communication", "BS Biology", "BS Psychology", "AB Psychology", "BS in Human Biology"],
        "College of Business and Accountancy (CBA)" => ["BS Accountancy", "BS Management Accounting", "BS Business Administration", "BS Hospitality Management", "BS Tourism Management"],
        "College of Computer Studies (CCS)" => ["Bachelor of Multimedia Arts", "BS Computer Science", "BS Information Technology"],
        "College of Criminal Justice Education (CCJE)" => ["BS Criminology"],
        "College of Engineering and Architecture (CEA)" => ["BS Architecture", "BS Civil Engineering", "BS Computer Engineering", "BS Electronics Engineering"],
        "College of Education (CED)" => ["Bachelor of Elementary Education", "Bachelor of Secondary Education", "Bachelor of Early Childhood Education"],
        "College of Nursing (CON)" => ["BS Nursing"],
        "School of Law (SOL)" => ["Juris Doctor", "Other"],
        "School of Medicine (SOM)" => ["Doctor of Medicine", "Other"],
        "Graduate School (GS)" => ["Education Programs", "Business Programs", "Information Technology Programs", "Nursing Programs", "Other"]
    ];

    $collegeStmt = $pdo->prepare("INSERT IGNORE INTO college (college_name) VALUES (?)");
    $deptStmt = $pdo->prepare("INSERT IGNORE INTO academic_department (college_id, dept_name) VALUES (?, ?)");

    foreach ($collegeDepts as $collegeName => $depts) {
        $collegeStmt->execute([$collegeName]);
        // Get ID even if IGNORE was triggered (fetch existing)
        $fetchStmt = $pdo->prepare("SELECT college_id FROM college WHERE college_name = ?");
        $fetchStmt->execute([$collegeName]);
        $cId = $fetchStmt->fetchColumn();
        
        foreach ($depts as $deptName) {
            $deptStmt->execute([$cId, $deptName]);
        }
    }
    echo "✓ Colleges and Academic Departments initialized.<br>";

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
    $stmt = $pdo->prepare("INSERT IGNORE INTO library_department (dept_name) VALUES (?)");
    foreach ($libDepts as $d) { $stmt->execute([$d]); }
    echo "✓ Library Sections initialized.<br>";

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
        'Other'
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO library_service (service_name) VALUES (?)");
    foreach ($services as $s) { $stmt->execute([$s]); }
    echo "✓ Library Services initialized.<br>";

    // 5. Seed Question Metrics
    $questions = [
        ['Feedback', 'The library has sufficient resources for my research and information needs', 5],
        ['Feedback', 'Library staff provided assistance in a timely and helpful manner', 5],
        ['Feedback', 'The process of borrowing, returning and renewal of library resources is convenient', 5],
        ['Feedback', 'The information/procedure provided by the library staff were easy to understand', 5]
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO question_metric (category, question_text, max_score) VALUES (?, ?, ?)");
    foreach ($questions as $q) { $stmt->execute($q); }
    echo "✓ Survey Questions initialized.<br>";

    // 6. Seed Default Administrator (if none exists)
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM admin_user");
    if ($checkAdmin->fetchColumn() == 0) {
        $user = 'admin';
        $pass = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO admin_user (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$user, $pass]);
        echo "✓ Default Admin Created: <b>admin</b> / <b>admin123</b><br>";
    }

    $pdo->commit();
    echo "<br><p style='color:green; font-weight:bold;'>SYSTEM READY: You can now access the Evaluation Form and Admin Portal.</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo "<p style='color:red;'>Initialization Failed: " . $e->getMessage() . "</p>";
}
