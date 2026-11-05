<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$dbname = 'lol_sms';
$dbuser = 'root';
$dbpass = ''; 

try {
    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed. Check config/database.php and import database.sql.');
}
