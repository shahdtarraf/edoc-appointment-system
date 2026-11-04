<?php
$servername = getenv('DB_HOST');
if ($servername === false || $servername === '') {
    $servername = 'localhost';
}

$portValue = getenv('DB_PORT');
if ($portValue === false || $portValue === '') {
    $portValue = '3306';
}
$port = (int) $portValue;

$username = getenv('DB_USER');
if ($username === false || $username === '') {
    $username = 'root';
}

$password = getenv('DB_PASSWORD');
if ($password === false) {
    $password = '';
}

$dbname = getenv('DB_NAME');
if ($dbname === false || $dbname === '') {
    $dbname = 'SQL_Database_edoc';
}

mysqli_report(MYSQLI_REPORT_OFF);

try {
    $database = @new mysqli($servername, $username, $password, $dbname, $port);
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed');
    $database = null;
}

if ($database === null || $database->connect_error) {
    error_log('Database connection failed');
    if (!defined('EDOC_HEALTH_CHECK')) {
        http_response_code(500);
        die('Database connection failed.');
    }
} else {
    $database->set_charset('utf8mb4');
}
?>
