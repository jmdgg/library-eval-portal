<?php
// delete_record.php
header('Content-Type: application/json');

// Get raw POST data
$rawData = file_get_contents("php://input");
$request = json_decode($rawData, true);

if (!$request || !isset($request['id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request. ID is missing."]);
    exit;
}

$idToDelete = $request['id'];
$historyFile = 'history_log.json';

if (!file_exists($historyFile)) {
    echo json_encode(["status" => "error", "message" => "History log file not found."]);
    exit;
}

$json = file_get_contents($historyFile);
$data = json_decode($json, true);

if (!is_array($data)) {
    echo json_encode(["status" => "error", "message" => "Invalid complete history log format."]);
    exit;
}

$recordFound = false;
$newArray = [];

foreach ($data as $entry) {
    if (isset($entry['id']) && $entry['id'] === $idToDelete) {
        $recordFound = true;
        
        // File Cleanup: Delete the associated .xlsx file
        if (!empty($entry['downloadUrl'])) {
            // Remove leading slashes/backslashes if any to prevent direct absolute path injections
            $safePath = ltrim($entry['downloadUrl'], '/\\');
            $filePath = __DIR__ . '/' . $safePath;
            
            if (file_exists($filePath) && is_file($filePath)) {
                // Additional safety to make sure it's strictly within the exports directory
                if (strpos(realpath($filePath), realpath(__DIR__ . '/exports')) === 0) {
                    unlink($filePath);
                }
            }
        }
        // Do not add this entry to $newArray (effectively deleting it)
    } else {
        $newArray[] = $entry; // Re-indexes automatically
    }
}

if (!$recordFound) {
    echo json_encode(["status" => "error", "message" => "Record not found in the ledger."]);
    exit;
}

// Save the re-indexed array back to JSON file
if (file_put_contents($historyFile, json_encode($newArray, JSON_PRETTY_PRINT)) !== false) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update the history log file."]);
}
?>
