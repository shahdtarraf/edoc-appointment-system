<?php
$edocDbEnv = array();
if (is_readable('/usr/local/etc/edoc-db.php')) {
    $loadedEnv = include '/usr/local/etc/edoc-db.php';
    if (is_array($loadedEnv)) {
        $edocDbEnv = $loadedEnv;
    }
}

if (!function_exists('edoc_env')) {
    function edoc_env($name) {
        global $edocDbEnv;
        if (isset($edocDbEnv[$name]) && $edocDbEnv[$name] !== '') {
            return $edocDbEnv[$name];
        }
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
        if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
            return $_SERVER[$name];
        }
        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }
        return false;
    }
}

$servername = edoc_env('DB_HOST');
if ($servername === false || $servername === '') {
    $servername = 'localhost';
}

$portValue = edoc_env('DB_PORT');
if ($portValue === false || $portValue === '') {
    $portValue = '3306';
}
$port = (int) $portValue;

$username = edoc_env('DB_USER');
if ($username === false || $username === '') {
    $username = 'root';
}

$password = edoc_env('DB_PASSWORD');
if ($password === false) {
    $password = '';
}

$dbname = edoc_env('DB_NAME');
if ($dbname === false || $dbname === '') {
    $dbname = 'SQL_Database_edoc';
}

$sslValue = edoc_env('DB_SSL');
if ($sslValue === false || $sslValue === '') {
    $localHost = (strcasecmp($servername, 'localhost') === 0 || $servername === '127.0.0.1');
    $useSsl = !$localHost;
} else {
    $sslNormalized = strtolower(trim($sslValue));
    $useSsl = in_array($sslNormalized, array('1', 'true', 'yes', 'on', 'required'), true);
}

$caFile = '';
if (is_readable('/usr/local/etc/edoc-ca.pem')) {
    $caFile = '/usr/local/etc/edoc-ca.pem';
}

mysqli_report(MYSQLI_REPORT_OFF);

try {
    if ($useSsl) {
        $database = mysqli_init();
        if ($database === false) {
            $database = null;
        } else {
            mysqli_options($database, MYSQLI_OPT_CONNECT_TIMEOUT, 15);
            if ($caFile !== '') {
                $database->ssl_set(null, null, $caFile, null, null);
            } else {
                $database->ssl_set(null, null, null, null, null);
            }
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
                $errorText = isset($database->connect_error) ? $database->connect_error : 'unknown';
                error_log('Database connection failed: ' . $errorText);
            }
        }
    } else {
        $database = @new mysqli($servername, $username, $password, $dbname, $port);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    $database = null;
}

if ($database === null || $database->connect_error) {
    if ($database !== null && !empty($database->connect_error)) {
        error_log('Database connection failed: ' . $database->connect_error);
    } else {
        error_log('Database connection failed');
    }
    if (!defined('EDOC_HEALTH_CHECK')) {
        http_response_code(500);
        die('Database connection failed.');
    }
} else {
    $database->set_charset('utf8mb4');
}
?>
