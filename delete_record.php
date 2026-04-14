<?php
// delete_record.php
require_once 'db_connect.php';

header('Content-Type: application/json');

// Get raw POST data
$rawData = file_get_contents("php://input");
$request = json_decode($rawData, true);

if (!$request || !isset($request['id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request. ID is missing."]);
    exit;
}

$idToDelete = $request['id'];

try {
    // 1. Find the file (download_url)
    $stmtFind = $pdo->prepare("SELECT download_url FROM GENERATED_REPORT WHERE report_id = :id");
    $stmtFind->execute([':id' => $idToDelete]);
    $report = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if ($report) {
        // Physical File Cleanup
        $downloadUrl = $report['download_url'];
        if (!empty($downloadUrl)) {
            $safePath = ltrim($downloadUrl, '/\\');
            $filePath = __DIR__ . '/' . $safePath;

            if (file_exists($filePath) && is_file($filePath)) {
                // Additional safety to make sure it's strictly within the exports directory
                if (strpos(realpath($filePath), realpath(__DIR__ . '/exports')) === 0) {
                    unlink($filePath);
                }
            }
        }

        // 2. Delete the record from GENERATED_REPORT
        $stmtDeleteReport = $pdo->prepare("DELETE FROM GENERATED_REPORT WHERE report_id = :id");
        $stmtDeleteReport->execute([':id' => $idToDelete]);

        // 3. Orphan Cleanup: Delete EVALUATION_PERIOD if no remaining reports
        $stmtCleanupPeriods = $pdo->prepare("DELETE FROM EVALUATION_PERIOD WHERE period_id NOT IN (SELECT period_id FROM GENERATED_REPORT)");
        $stmtCleanupPeriods->execute();

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Record not found in the database."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
