<?php
require_once 'db_connect.php';

echo "TABLES:\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "- $table\n";
    echo "  COLUMNS:\n";
    $columns = $pdo->query("DESCRIBE $table")->fetchAll();
    foreach ($columns as $column) {
        echo "    - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
}
?>
