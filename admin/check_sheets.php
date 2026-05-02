<?php
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$templatePath = '../master_template.xlsx';
$spreadsheet = IOFactory::load($templatePath);
echo "Sheet names:\n";
print_r($spreadsheet->getSheetNames());
?>
