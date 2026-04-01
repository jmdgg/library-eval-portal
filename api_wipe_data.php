<?php
// api_wipe_data.php
header('Content-Type: application/json');

$rawData = file_get_contents("php://input");
$request = json_decode($rawData, true);

if (!$request || empty($request['confirm']) || $request['confirm'] !== true) {
    echo json_encode(["status" => "error", "message" => "Confirmation flag missing or invalid."]);
    exit;
}

// Action 1: Clear Ledger (history_log.json)
$ledgerPath = __DIR__ . '/history_log.json';
if (file_put_contents($ledgerPath, json_encode([], JSON_PRETTY_PRINT)) === false) {
    echo json_encode(["status" => "error", "message" => "Failed to clear ledger."]);
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
