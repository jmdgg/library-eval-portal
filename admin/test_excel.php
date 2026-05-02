<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['username'] = 'TestAdmin';
$_GET['start_month'] = 'January';
$_GET['start_year'] = '2024';
$_GET['end_month'] = 'December';
$_GET['end_year'] = '2024';

// Mocking server vars
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

try {
    include 'generate_excel.php';
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage();
}
?>
