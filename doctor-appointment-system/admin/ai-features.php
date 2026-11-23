<?php
require __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/assistant_client.php';
require_once dirname(__DIR__) . '/noshow_client.php';
require_once dirname(__DIR__) . '/noshow_adapter.php';

$noshowHosted = edoc_noshow_is_configured();
if ($noshowHosted) {
    edoc_noshow_ensure_table($database);
}

$notice = '';
if ($noshowHosted && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shadow_scan'])) {
    if (!edoc_csrf_verify()) {
        $notice = edoc_t('status_err');
    } else {
        $res = $database->query('SELECT appoid FROM appointment ORDER BY appoid DESC LIMIT 25');
        $n = 0;
        while ($res && ($row = $res->fetch_assoc())) {
            $derived = edoc_noshow_derive_features($database, (int) $row['appoid']);
            $payload = array(
                'status' => 'not_scored',
                'reason' => 'missing_features',
                'missing' => $derived['missing'],
            );
            if ($derived['ok'] === true) {
                $call = edoc_noshow_chat_predict($derived['features'], 'demo-' . (int) $row['appoid']);
                $payload = $call['payload'];
                if (empty($payload['null_inputs']) && !empty($derived['missing']) && is_array($derived['missing'])) {
                    $payload['null_inputs'] = $derived['missing'];
                }
            } else {
                $payload['reason'] = isset($payload['reason']) ? $payload['reason'] : 'missing_features';
                $payload['missing'] = $derived['missing'];
            }
            $status = isset($payload['status']) ? substr((string) $payload['status'], 0, 32) : 'not_scored';
            $reason = isset($payload['reason']) ? (string) $payload['reason'] : '';
            if (isset($payload['missing']) && is_array($payload['missing']) && $status !== 'scored') {
                $reason = 'missing:' . implode(',', $payload['missing']);
            }
            if ($status === 'scored' && isset($payload['null_inputs']) && is_array($payload['null_inputs']) && $payload['null_inputs']) {
                $reason = 'null_inputs:' . implode(',', $payload['null_inputs']) . ' (training imputer; not PHP defaults)';
            }
            $raw = (isset($payload['raw_score']) && is_numeric($payload['raw_score'])) ? (string) $payload['raw_score'] : '';
            $cal = (isset($payload['calibrated_score']) && is_numeric($payload['calibrated_score'])) ? (string) $payload['calibrated_score'] : '';
            $band = isset($payload['risk_band']) ? (string) $payload['risk_band'] : '';
            $action = isset($payload['recommended_admin_action']) ? (string) $payload['recommended_admin_action'] : '';
            $ver = isset($payload['model_version']) ? (string) $payload['model_version'] : '';
            $ood = isset($payload['ood_warnings']) ? json_encode($payload['ood_warnings']) : '';
            $aid = (int) $row['appoid'];
            $scoredAt = date('Y-m-d H:i:s');
            $ins = $database->prepare(
                'INSERT INTO ai_noshow_shadow (appoid, status, skip_reason, raw_score, calibrated_score, risk_band, recommended_admin_action, model_version, ood_warnings, scored_at)
                 VALUES (?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), ?)'
            );
            if ($ins === false) {
                error_log('Noshow shadow prepare failed');
                continue;
            }
            $ins->bind_param(
                'isssssssss',
                $aid,
                $status,
                $reason,
                $raw,
                $cal,
                $band,
                $action,
                $ver,
                $ood,
                $scoredAt
            );
            if (!$ins->execute()) {
                error_log('Noshow shadow execute failed');
                $ins->close();
                continue;
            }
            $ins->close();
            $n++;
        }
        $notice = edoc_t('ai_shadow_ran') . ' ' . $n;
    }
}

$h1 = edoc_ai_health('AI_SERVICE_URL', '/health');
$h2 = edoc_ai_health('ASSISTANT_URL', '/health');
$h3 = $noshowHosted
    ? edoc_ai_health('NOSHOW_URL', '/health')
    : array('ok' => false, 'error' => 'not_configured');

edoc_app_start(edoc_t('ai_board_title'), 'a', $username, $useremail, 'ai-features.php');
echo '<p class="edoc-muted">' . edoc_h(edoc_t('ai_admin_intro')) . '</p>';
if ($notice !== '') {
    edoc_alert('ok', $notice);
}

function edoc_ai_status_card($title, $payload, $kind)
{
    $ok = is_array($payload) && !empty($payload['ok']);
    $label = $ok ? 'Active / Experimental' : 'Unavailable';
    if ($kind === 'noshow') {
        if (!empty($payload['error']) && $payload['error'] === 'not_configured') {
            $label = edoc_t('ai_f5_not_hosted');
        } else {
            $label = $ok ? 'Experimental / Not Approved' : 'Unavailable — bundle missing or down';
        }
    }
    echo '<div class="edoc-card">';
    echo '<span class="edoc-badge ' . ($ok ? 'edoc-badge-live' : 'edoc-badge-wip') . '">' . edoc_h($label) . '</span>';
    echo '<h3>' . edoc_h($title) . '</h3>';
    echo '<p class="edoc-muted">' . edoc_h(edoc_t('ai_checked_at')) . ': ' . edoc_h(date('H:i:s')) . '</p>';
    if (isset($payload['documents'])) {
        echo '<p>docs: ' . (int) $payload['documents'] . '</p>';
    }
    if (isset($payload['n_specialties'])) {
        echo '<p>specialties: ' . (int) $payload['n_specialties'] . '</p>';
    }
    if (isset($payload['model_version'])) {
        echo '<p>model: ' . edoc_h((string) $payload['model_version']) . '</p>';
    }
    if (isset($payload['license'])) {
        echo '<p class="edoc-muted">' . edoc_h((string) $payload['license']) . '</p>';
    }
    if (isset($payload['fairness_note'])) {
        echo '<p class="edoc-muted">' . edoc_h((string) $payload['fairness_note']) . '</p>';
    }
    if (isset($payload['calibration']['method'])) {
        echo '<p class="edoc-muted">calibrator: ' . edoc_h((string) $payload['calibration']['method']) . '</p>';
    }
    if (array_key_exists('ready_for_inference', $payload)) {
        echo '<p class="edoc-muted">inference ready: ' . (!empty($payload['ready_for_inference']) ? 'yes' : 'no') . '</p>';
    }
    echo '</div>';
}

echo '<div class="edoc-grid">';
edoc_ai_status_card(edoc_t('ai_f1'), $h1, 'f1');
edoc_ai_status_card(edoc_t('ai_assist_card'), $h2, 'assist');
edoc_ai_status_card(edoc_t('ai_f5'), $h3, 'noshow');
echo '</div>';

echo '<div class="edoc-card" style="margin-top:16px">';
echo '<h2>' . edoc_h(edoc_t('ai_f2')) . ' / ' . edoc_h(edoc_t('ai_f3')) . ' / ' . edoc_h(edoc_t('ai_f4')) . '</h2>';
echo '<p>' . edoc_h(edoc_t('ai_no_fake')) . '</p></div>';

if (!$noshowHosted) {
    echo '<div class="edoc-card" style="margin-top:16px">';
    echo '<h2>' . edoc_h(edoc_t('ai_shadow_title')) . '</h2>';
    echo '<p>' . edoc_h(edoc_t('ai_f5_not_hosted_help')) . '</p></div>';
    edoc_app_end();
    return;
}

echo '<div class="edoc-card" style="margin-top:16px">';
echo '<h2>' . edoc_h(edoc_t('ai_shadow_title')) . '</h2>';
echo '<p>' . edoc_h(edoc_t('ai_shadow_help')) . '</p>';
$cnt = $database->query("SELECT COUNT(*) AS n, SUM(status='not_scored') AS skipped FROM ai_noshow_shadow");
if ($cnt) {
    $c = $cnt->fetch_assoc();
    echo '<p class="edoc-muted">shadow rows: ' . (int) $c['n'] . ' · not_scored: ' . (int) $c['skipped'] . '</p>';
}
echo '<form method="post">';
echo edoc_csrf_field();
echo '<button class="edoc-btn edoc-btn-primary" type="submit" name="shadow_scan" value="1">' . edoc_h(edoc_t('ai_shadow_run')) . '</button>';
echo '</form>';
$shadow = $database->query('SELECT shadow_id, appoid, status, skip_reason, calibrated_score, risk_band, recommended_admin_action, model_version, ood_warnings, scored_at FROM ai_noshow_shadow ORDER BY shadow_id DESC LIMIT 30');
echo '<div class="edoc-table-wrap" style="margin-top:12px"><table class="edoc-table"><tr>';
echo '<th>id</th><th>appt</th><th>status</th><th>reason</th><th>score</th><th>band</th><th>research hint (not executed)</th><th>model</th><th>ood</th><th>at</th></tr>';
if ($shadow) {
    while ($s = $shadow->fetch_assoc()) {
        echo '<tr><td>' . (int) $s['shadow_id'] . '</td><td>' . (int) $s['appoid'] . '</td>';
        echo '<td>' . edoc_h($s['status']) . '</td><td>' . edoc_h($s['skip_reason']) . '</td>';
        echo '<td>' . edoc_h((string) $s['calibrated_score']) . '</td><td>' . edoc_h((string) $s['risk_band']) . '</td>';
        echo '<td>' . edoc_h((string) $s['recommended_admin_action']) . '</td>';
        echo '<td>' . edoc_h((string) $s['model_version']) . '</td><td>' . edoc_h((string) $s['ood_warnings']) . '</td>';
        echo '<td>' . edoc_h((string) $s['scored_at']) . '</td></tr>';
    }
}
echo '</table></div></div>';
edoc_app_end();
