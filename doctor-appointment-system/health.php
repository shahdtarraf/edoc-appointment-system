<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

define('EDOC_HEALTH_CHECK', true);

$status = array(
    'ok' => true,
    'app' => 'edoc',
    'php' => true,
    'database' => false
);

include __DIR__ . '/connection.php';

if (isset($database) && $database instanceof mysqli && empty($database->connect_error)) {
    $result = @$database->query('SELECT 1');
    if ($result) {
        $status['database'] = true;
        $result->free();
    }
}

if (!$status['database']) {
    $status['ok'] = false;
    http_response_code(503);
} else {
    http_response_code(200);
}

echo json_encode($status);
