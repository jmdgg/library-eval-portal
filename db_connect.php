<?php
// Database credentials
$host = "localhost";
$dbname = "library_eval_db";
$username = "root";
$password = "";

try {
    // Create PDO connection string (DSN)
    $dsn = "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=utf8mb4";
    
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set the default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Temporarily echo for testing purposes (remove in production)
    //echo "<p>Connected successfully to the database!</p>";
    
} catch (PDOException $e) {
    // Output clean error message on failure
    die("Database Connection Failed: " . $e->getMessage());
}
?>
