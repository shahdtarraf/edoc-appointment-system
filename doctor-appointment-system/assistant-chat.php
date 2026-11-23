<?php
require_once __DIR__ . '/includes/layout.php';
edoc_bootstrap(false);
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/assistant_client.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false));
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}
if (is_array($data) && isset($data['csrf_token'])) {
    $_POST['csrf_token'] = $data['csrf_token'];
}
if (!edoc_csrf_verify()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'answer' => edoc_t('status_err')));
    exit;
}
$message = isset($data['message']) ? trim((string) $data['message']) : '';
$messageLen = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
if ($message === '' || $messageLen > 800) {
    http_response_code(400);
    echo json_encode(array('ok' => false));
    exit;
}

$role = 'guest';
if (isset($_SESSION['usertype']) && in_array($_SESSION['usertype'], array('p', 'd', 'a'), true) && !empty($_SESSION['user'])) {
    $role = $_SESSION['usertype'];
}
$lang = edoc_lang();

$history = isset($data['history']) ? $data['history'] : array();
$result = edoc_assistant_chat($message, $role, $lang, $history);
if ($result['ok'] !== true) {
    http_response_code(503);
    $msg = edoc_lang() === 'en'
        ? 'The assistant is unavailable. You can still use the menus to book and log in.'
        : 'المساعد غير متاح حالياً. يمكنك متابعة التصفح والحجز من القوائم.';
    echo json_encode(array('ok' => false, 'answer' => $msg));
    exit;
}
$payload = $result['payload'];
echo json_encode(array(
    'ok' => true,
    'answer' => isset($payload['answer']) ? $payload['answer'] : '',
    'source_label' => isset($payload['source_label']) ? $payload['source_label'] : null,
    'source' => isset($payload['source']) ? $payload['source'] : null,
    'status' => isset($payload['status']) ? $payload['status'] : 'ok',
    'followups' => isset($payload['followups']) && is_array($payload['followups']) ? $payload['followups'] : array(),
), JSON_UNESCAPED_UNICODE);
