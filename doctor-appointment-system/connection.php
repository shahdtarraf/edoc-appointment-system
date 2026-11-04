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

$sslValue = getenv('DB_SSL');
if ($sslValue === false || $sslValue === '') {
    $localHost = (strcasecmp($servername, 'localhost') === 0 || $servername === '127.0.0.1');
    $useSsl = !$localHost;
} else {
    $sslNormalized = strtolower(trim($sslValue));
    $useSsl = in_array($sslNormalized, array('1', 'true', 'yes', 'on', 'required'), true);
}

mysqli_report(MYSQLI_REPORT_OFF);

try {
    if ($useSsl) {
        $database = mysqli_init();
        if ($database === false) {
            $database = null;
        } else {
            mysqli_options($database, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
            $database->ssl_set(null, null, null, null, null);
            $connected = @$database->real_connect(
                $servername,
                $username,
                $password,
                $dbname,
                $port,
                null,
                MYSQLI_CLIENT_SSL
            );
            if ($connected !== true) {
                error_log('Database connection failed');
            }
        }
    } else {
        $database = @new mysqli($servername, $username, $password, $dbname, $port);
    }
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
