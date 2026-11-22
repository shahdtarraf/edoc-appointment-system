<?php

function edoc_ai_service_url()
{
    if (!function_exists('edoc_env')) {
        return '';
    }
    $url = edoc_env('AI_SERVICE_URL');
    if ($url === false || $url === '') {
        return '';
    }
    return rtrim((string) $url, '/');
}

function edoc_ai_timeout_seconds()
{
    if (!function_exists('edoc_env')) {
        return 6;
    }
    $value = edoc_env('AI_SERVICE_TIMEOUT');
    if ($value === false || $value === '') {
        return 6;
    }
    $seconds = (int) $value;
    if ($seconds < 1) {
        return 6;
    }
    if ($seconds > 20) {
        return 20;
    }
    return $seconds;
}

function edoc_ai_service_token()
{
    if (!function_exists('edoc_env')) {
        return '';
    }
    $token = edoc_env('AI_SERVICE_TOKEN');
    if ($token === false) {
        return '';
    }
    return trim((string) $token);
}

function edoc_ai_recommend($text)
{
    $base = edoc_ai_service_url();
    if ($base === '') {
        return array(
            'ok' => false,
            'transport' => 'not_configured',
            'payload' => null,
        );
    }

    $token = edoc_ai_service_token();
    if ($token === '') {
        return array(
            'ok' => false,
            'transport' => 'not_configured',
            'payload' => null,
        );
    }

    $payload = json_encode(array('text' => (string) $text), JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return array(
            'ok' => false,
            'transport' => 'encode_failed',
            'payload' => null,
        );
    }

    $timeout = edoc_ai_timeout_seconds();
    $url = $base . '/recommend';
    $headers = array(
        'Content-Type: application/json; charset=utf-8',
        'X-AI-Service-Token: ' . $token,
    );
    $isHttps = (stripos($url, 'https://') === 0);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $isHttps ? true : false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $isHttps ? 2 : 0);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno !== 0 || $body === false) {
            error_log('AI recommend transport failed');
            return array(
                'ok' => false,
                'transport' => 'unreachable',
                'payload' => null,
            );
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || $http < 200 || $http >= 300) {
            error_log('AI recommend response invalid');
            return array(
                'ok' => false,
                'transport' => 'bad_response',
                'payload' => null,
            );
        }
        return array(
            'ok' => true,
            'transport' => 'ok',
            'payload' => $decoded,
        );
    }

    $ssl = array();
    if ($isHttps) {
        $ssl = array(
            'verify_peer' => true,
            'verify_peer_name' => true,
        );
    }
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $payload,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ),
        'ssl' => $ssl,
    ));
    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        error_log('AI recommend transport failed');
        return array(
            'ok' => false,
            'transport' => 'unreachable',
            'payload' => null,
        );
    }
    $http = 0;
    if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
        if (preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $http = (int) $match[1];
        }
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded) || $http < 200 || $http >= 300) {
        error_log('AI recommend response invalid');
        return array(
            'ok' => false,
            'transport' => 'bad_response',
            'payload' => null,
        );
    }
    return array(
        'ok' => true,
        'transport' => 'ok',
        'payload' => $decoded,
    );
}

function edoc_doctor_count_by_specialty($database, $specialtyId)
{
    if ($database === null) {
        return 0;
    }
    $stmt = $database->prepare('SELECT COUNT(*) AS n FROM doctor WHERE specialties = ?');
    if ($stmt === false) {
        return 0;
    }
    $id = (int) $specialtyId;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ? (int) $row['n'] : 0;
}
