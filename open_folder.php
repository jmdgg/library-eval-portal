<?php
// open_folder.php
header('Content-Type: application/json');

$rawData = file_get_contents("php://input");
$request = json_decode($rawData, true);

if (!$request || empty($request['filepath'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request. Filepath is missing."]);
    exit;
}

$relativePath = ltrim($request['filepath'], '/\\');
$absolutePath = realpath(__DIR__ . '/' . $relativePath);

if (!$absolutePath || !file_exists($absolutePath)) {
    echo json_encode(["status" => "error", "message" => "File not found.", "path" => $absolutePath]);
    exit;
}

$dirPath = dirname($absolutePath);

// Detect the OS
$os = php_uname('s');

if (stripos($os, 'Windows') !== false) {
    // Windows
    // Convert slashes to backslashes
    $winPath = str_replace('/', '\\', $dirPath);

    $command = 'start "" explorer.exe "' . $winPath . '"';
    pclose(popen($command, "r"));

    echo json_encode(["status" => "success", "command" => $command]);
} elseif (stripos($os, 'Darwin') !== false) {
    // macOS
    $command = 'open ' . escapeshellarg($dirPath) . ' > /dev/null 2>&1 &';
    exec($command);
    echo json_encode(["status" => "success", "command" => $command]);
} elseif (stripos($os, 'Linux') !== false) {
    // Linux
    $command = 'xdg-open ' . escapeshellarg($dirPath) . ' > /dev/null 2>&1 &';
    exec($command);
    echo json_encode(["status" => "success", "command" => $command]);
} else {
    echo json_encode(["status" => "error", "message" => "Unsupported operating system."]);
}
?>