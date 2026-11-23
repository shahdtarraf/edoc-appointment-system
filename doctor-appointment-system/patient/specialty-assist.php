<?php
require __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/config/supported_specialties.php';
require_once dirname(__DIR__) . '/ai_recommend_client.php';

$supported = edoc_supported_specialty_map();
$error = '';
$resultPayload = null;
$submittedText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = edoc_t('status_err');
    } else {
        $submittedText = isset($_POST['symptom_text']) ? trim((string) $_POST['symptom_text']) : '';
        $call = edoc_ai_recommend($submittedText);
        if ($call['ok'] !== true) {
            $error = edoc_t('service_unavailable');
        } else {
            $resultPayload = $call['payload'];
        }
    }
}

edoc_app_start(edoc_t('assist_title'), 'p', $username, $useremail, 'specialty-assist.php');
echo '<div class="edoc-card">';
echo '<p>' . edoc_h(edoc_t('assist_disclaimer')) . ' ' . edoc_h(edoc_t('not_diagnosis')) . '</p>';
echo '<form method="post" action="specialty-assist.php">';
echo edoc_csrf_field();
echo '<label class="edoc-label" for="symptom_text">' . edoc_h(edoc_t('assist_hint')) . '</label>';
echo '<textarea id="symptom_text" name="symptom_text" class="edoc-field" maxlength="1500" placeholder="' . edoc_h(edoc_t('assist_placeholder')) . '">' . edoc_h($submittedText) . '</textarea>';
echo '<p><button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('assist_submit')) . '</button> ';
echo '<a class="edoc-btn edoc-btn-soft" href="specialties.php">' . edoc_h(edoc_t('assist_manual')) . '</a></p>';
echo '</form>';
if ($error !== '') {
    edoc_alert('error', $error);
}
if (is_array($resultPayload)) {
    $status = isset($resultPayload['status']) ? $resultPayload['status'] : '';
    $message = isset($resultPayload['message']) ? (string) $resultPayload['message'] : '';
    if ($message !== '') {
        echo '<p class="edoc-muted">' . edoc_h($message) . '</p>';
    }
    if ($status === 'recommend' && !empty($resultPayload['recommendations']) && is_array($resultPayload['recommendations'])) {
        foreach ($resultPayload['recommendations'] as $rec) {
            $sid = isset($rec['specialty_id']) ? (int) $rec['specialty_id'] : 0;
            $sname = isset($rec['specialty_name']) ? (string) $rec['specialty_name'] : '';
            if (!edoc_is_supported_specialty($sid)) {
                continue;
            }
            if ($sname === '' && isset($supported[$sid])) {
                $sname = $supported[$sid];
            }
            echo '<div class="edoc-card" style="margin-top:12px">';
            echo '<h3>' . edoc_h($sname) . '</h3>';
            echo '<p class="edoc-muted">' . edoc_h(edoc_t('assist_disclaimer')) . '</p>';
            echo '<a class="edoc-btn edoc-btn-primary" href="doctors.php?spec=' . $sid . '">' . edoc_h(edoc_t('view_doctors')) . '</a>';
            echo '</div>';
            break;
        }
    }
}
echo '</div><div class="edoc-card" style="margin-top:16px"><h3>' . edoc_h(edoc_t('assist_manual')) . '</h3><div class="edoc-grid">';
foreach ($supported as $sid => $sname) {
    echo '<a class="edoc-card" href="doctors.php?spec=' . (int) $sid . '">' . edoc_h($sname) . '</a>';
}
echo '</div></div>';
edoc_app_end();
