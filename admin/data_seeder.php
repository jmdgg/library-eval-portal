<?php
/**
 * seed_dummy_data.php
 * Generates massive amounts of realistic test data for the 3NF Library DB.
 * * WARNING: DELETE THIS FILE BEFORE GIVING THE SYSTEM TO THE CLIENT.
 */

require_once '../db_connect.php';

// Configuration
$recordsToGenerate = 150;
$startDate = strtotime('2024-01-01');
$endDate = strtotime('2026-04-10');

// Dummy Data Arrays
$roles = ['Student', 'Faculty', 'NTP', 'Alumni', 'Other Researcher'];
$colleges = ['College of Arts and Sciences', 'College of Engineering', 'College of Nursing', 'College of Business', 'CCJE', 'N/A'];
$services = ['Borrowing', 'Reference Service', 'Clearance Request', 'Turnitin', 'Library Instruction', 'Document Delivery'];
$satisfaction = ['Yes', 'Yes', 'Yes', 'Yes', 'No']; // 80% chance of Yes
$ratings = ['Excellent', 'Very Good', 'Good', 'Fair', 'Needs Improvement'];
$comments = [
    'Great service, very fast!',
    'The aircon is too cold in the CMS section.',
    'Librarian was extremely helpful with my thesis.',
    'Please add more sockets for laptops.',
    '',
    '',
    '', // Empty comments are realistic
    'Turnitin processing was quick.'
];

echo "<h2>Starting Database Seeder...</h2>";

try {
    $pdo->beginTransaction();

    $parent_stmt = $pdo->prepare("
        INSERT INTO survey_submission 
        (period_id, department_id, email, submission_date, role, college, academic_department, services_availed, is_satisfied, overall_rating, recommendations, comments) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $child_stmt = $pdo->prepare("INSERT INTO response_detail (submission_id, question_id, score) VALUES (?, ?, ?)");

    // Helper: Find or Create Evaluation Period
    function getPeriodId($pdo, $timestamp)
    {
        $month = strtoupper(date('F', $timestamp));
        $year = (int) date('Y', $timestamp);

        $stmt = $pdo->prepare("SELECT period_id FROM evaluation_period WHERE eval_month = ? AND eval_year = ?");
        $stmt->execute([$month, $year]);
        $row = $stmt->fetch();

        if ($row)
            return $row['period_id'];

        // Doesn't exist? Create it.
        $insert = $pdo->prepare("INSERT INTO evaluation_period (eval_month, eval_year, is_processed) VALUES (?, ?, 0)");
        $insert->execute([$month, $year]);
        return $pdo->lastInsertId();
    }

    // The Generation Loop
    for ($i = 0; $i < $recordsToGenerate; $i++) {

        // 1. Generate Random Demographics
        $randomTimestamp = mt_rand($startDate, $endDate);
        $submission_date = date('Y-m-d', $randomTimestamp);

        $period_id = getPeriodId($pdo, $randomTimestamp);
        $department_id = rand(1, 7); // Libraries 1 through 7

        $email = "test_user_" . rand(1000, 9999) . "@auf.edu.ph";
        $role = $roles[array_rand($roles)];
        $college = $colleges[array_rand($colleges)];

        // Pick 1 to 3 random services
        $random_services = (array) array_rand(array_flip($services), rand(1, 3));
        $services_availed = implode(', ', $random_services);

        $is_sat = $satisfaction[array_rand($satisfaction)];

        // Bias overall rating based on satisfaction
        $overall = ($is_sat == 'Yes') ? $ratings[rand(0, 2)] : $ratings[rand(3, 4)];

        $comment = $comments[array_rand($comments)];

        // 2. Insert Parent Record
        $parent_stmt->execute([
            $period_id,
            $department_id,
            $email,
            $submission_date,
            $role,
            $college,
            'Test Dept',
            $services_availed,
            $is_sat,
            $overall,
            '',
            $comment
        ]);

        $submission_id = $pdo->lastInsertId();

        // 3. Insert Child Records (Questions 1-4)
        for ($q = 1; $q <= 4; $q++) {
            // Generate a realistic score (mostly 3, 4, 5)
            $score = rand(3, 5);
            if ($is_sat == 'No')
                $score = rand(1, 3); // Lower scores if not satisfied

            $child_stmt->execute([$submission_id, $q, $score]);
        }
    }

    $pdo->commit();
    echo "<p style='color:green; font-weight:bold;'>Successfully seeded {$recordsToGenerate} complex records into the database!</p>";
    echo "<p>You can now test your Date Range Filters in the Admin Dashboard.</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color:red;'>Seeding Failed: " . $e->getMessage() . "</p>";
}
?>