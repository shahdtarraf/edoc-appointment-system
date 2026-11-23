<?php

function edoc_noshow_is_configured()
{
    $base = rtrim((string) edoc_noshow_env('NOSHOW_URL'), '/');
    $token = trim((string) edoc_noshow_env('NOSHOW_TOKEN'));
    return $base !== '' && $token !== '';
}

function edoc_noshow_env($name)
{
    if (function_exists('edoc_assistant_env')) {
        $v = edoc_assistant_env($name);
        if ($v !== '') {
            return $v;
        }
    }
    if (function_exists('edoc_env')) {
        $v = edoc_env($name);
        if ($v !== false && $v !== '') {
            return $v;
        }
    }
    $v = getenv($name);
    return ($v === false) ? '' : $v;
}

function edoc_noshow_chat_predict($features, $ref)
{
    $base = rtrim((string) edoc_noshow_env('NOSHOW_URL'), '/');
    $token = trim((string) edoc_noshow_env('NOSHOW_TOKEN'));
    if ($base === '' || $token === '') {
        return array('ok' => false, 'payload' => array('status' => 'not_scored', 'reason' => 'not_configured'));
    }
    $body = json_encode(array(
        'features' => $features,
        'appointment_ref' => (string) $ref,
    ), JSON_UNESCAPED_UNICODE);
    $ch = curl_init($base . '/predict');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-AI-Service-Token: ' . $token,
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code < 200 || $code >= 300) {
        error_log('Noshow transport failed');
        return array('ok' => false, 'payload' => array('status' => 'not_scored', 'reason' => 'http'));
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return array('ok' => false, 'payload' => array('status' => 'not_scored', 'reason' => 'bad_json'));
    }
    return array('ok' => true, 'payload' => $decoded);
}

function edoc_ai_health($envUrl, $path)
{
    $base = rtrim((string) edoc_noshow_env($envUrl), '/');
    if ($base === '') {
        return array('ok' => false, 'error' => 'not_configured');
    }
    $ch = curl_init($base . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code !== 200) {
        return array('ok' => false, 'http' => $code);
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : array('ok' => false);
}
