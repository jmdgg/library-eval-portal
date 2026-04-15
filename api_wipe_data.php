<?php
// api_wipe_data.php
require_once 'db_connect.php';

header('Content-Type: application/json');

$rawData = file_get_contents("php://input");
$request = json_decode($rawData, true);

if (!$request || empty($request['confirm']) || $request['confirm'] !== true) {
    echo json_encode(["status" => "error", "message" => "Confirmation flag missing or invalid."]);
    exit;
}

// Action 1: Clear Database
try {
    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM GENERATED_REPORT");
    $pdo->exec("DELETE FROM EVALUATION_PERIOD");
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => "Failed to clear database: " . $e->getMessage()]);
    exit;
}

// Action 2: Clear Files (/exports/)
$exportsDir = __DIR__ . '/exports';

if (is_dir($exportsDir)) {
    // using RecursiveDirectoryIterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($exportsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        if ($fileinfo->isFile() && $fileinfo->getExtension() === 'xlsx') {
            unlink($fileinfo->getRealPath());
        } elseif ($fileinfo->isDir()) {
            $dirStr = $fileinfo->getRealPath();
            // Optional: Remove subdirectories if they are empty
            $contents = array_diff(scandir($dirStr), array('.', '..'));
            if (empty($contents)) {
                @rmdir($dirStr);
            }
        }
    }
}

echo json_encode(["status" => "success"]);
?>
