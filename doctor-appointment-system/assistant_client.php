<?php

function edoc_assistant_env($name)
{
    static $loaded = null;
    if ($loaded === null) {
        $loaded = array();
        if (is_readable('/usr/local/etc/edoc-db.php')) {
            $fromFile = include '/usr/local/etc/edoc-db.php';
            if (is_array($fromFile)) {
                $loaded = $fromFile;
            }
        }
    }
    if (isset($loaded[$name]) && $loaded[$name] !== '') {
        return $loaded[$name];
    }
    if (function_exists('edoc_env')) {
        $value = edoc_env($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return $value;
    }
    return '';
}

function edoc_assistant_url()
{
    $url = edoc_assistant_env('ASSISTANT_URL');
    if ($url === false || $url === '') {
        return '';
    }
    return rtrim((string) $url, '/');
}

function edoc_assistant_token()
{
    return trim((string) edoc_assistant_env('ASSISTANT_TOKEN'));
}

function edoc_assistant_history($raw)
{
    if (!is_array($raw)) {
        return array();
    }
    $out = array();
    foreach ($raw as $item) {
        if (count($out) >= 8) {
            break;
        }
        if (!is_array($item)) {
            continue;
        }
        $role = isset($item['role']) ? (string) $item['role'] : '';
        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }
        $text = isset($item['text']) ? trim((string) $item['text']) : '';
        if ($text === '') {
            continue;
        }
        if (function_exists('mb_substr')) {
            $text = mb_substr($text, 0, 400, 'UTF-8');
        } else {
            $text = substr($text, 0, 400);
        }
        $out[] = array('role' => $role, 'text' => $text);
    }
    return $out;
}

function edoc_assistant_chat($message, $role, $lang, $history = array())
{
    $base = edoc_assistant_url();
    if ($base === '') {
        return array('ok' => false, 'transport' => 'not_configured', 'payload' => null);
    }
    $token = edoc_assistant_token();
    if ($token === '') {
        return array('ok' => false, 'transport' => 'not_configured', 'payload' => null);
    }
    $payload = json_encode(array(
        'message' => (string) $message,
        'role' => $role,
        'lang' => $lang,
        'history' => edoc_assistant_history($history),
    ), JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return array('ok' => false, 'transport' => 'encode_failed', 'payload' => null);
    }
    $url = $base . '/chat';
    $headers = array(
        'Content-Type: application/json; charset=utf-8',
        'X-Assistant-Token: ' . $token,
    );
    $timeout = 8;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code < 200 || $code >= 300) {
            error_log('Assistant transport failed');
            return array('ok' => false, 'transport' => 'http', 'payload' => null);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['answer'])) {
            return array('ok' => false, 'transport' => 'bad_json', 'payload' => null);
        }
        return array('ok' => true, 'transport' => 'ok', 'payload' => $decoded);
    }
    return array('ok' => false, 'transport' => 'no_curl', 'payload' => null);
}
