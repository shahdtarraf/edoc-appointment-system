<?php
require __DIR__ . '/_init.php';

$error = '';
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = edoc_t('status_err');
    } else {
        $title = trim((string) $_POST['title']);
        $docid = (int) $_POST['docid'];
        $nop = (int) $_POST['nop'];
        $date = (string) $_POST['date'];
        $time = (string) $_POST['time'];
        if ($title === '' || $docid < 1 || $nop < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $error = edoc_t('invalid_input');
        } else {
            $docidStr = (string) $docid;
            if (edoc_has_column($database, 'schedule', 'is_demo')) {
                $ins = $database->prepare(
                    'INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop, is_demo, record_source, verification_status)
                     VALUES (?,?,?,?,?,1,?,?)'
                );
                $src = 'demo_seed_v1';
                $ver = 'simulation';
                $ins->bind_param('ssssiss', $docidStr, $title, $date, $time, $nop, $src, $ver);
            } else {
                $ins = $database->prepare('INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop) VALUES (?,?,?,?,?)');
                $ins->bind_param('ssssi', $docidStr, $title, $date, $time, $nop);
            }
            if ($ins && $ins->execute()) {
                $ok = true;
            } else {
                $error = edoc_t('status_err');
            }
            if ($ins) {
                $ins->close();
            }
        }
    }
}

$docs = $database->query('SELECT docid, docname FROM doctor ORDER BY docname LIMIT 200');
edoc_app_start(edoc_t('add_session'), 'a', $username, $useremail, 'schedule.php');
if ($ok) {
    edoc_alert('ok', edoc_t('status_ok'));
}
if ($error !== '') {
    edoc_alert('error', $error);
}
echo edoc_demo_badge('session');
echo '<div class="edoc-card"><form class="edoc-form" method="post">';
echo edoc_csrf_field();
echo '<label class="edoc-label">' . edoc_h(edoc_t('session_title')) . '<input class="edoc-field" name="title" required maxlength="120" value="DEMO simulated session"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('doctor_name')) . '<select class="edoc-field" name="docid">';
while ($row = $docs->fetch_assoc()) {
    echo '<option value="' . (int) $row['docid'] . '">' . edoc_h($row['docname']) . '</option>';
}
echo '</select></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('session_date')) . '<input class="edoc-field" type="date" name="date" required></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('session_time')) . '<input class="edoc-field" type="time" name="time" required></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('capacity')) . '<input class="edoc-field" type="number" name="nop" min="1" max="50" value="8"></label>';
echo '<div class="edoc-form-actions"><button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('save')) . '</button>';
echo '<a class="edoc-btn edoc-btn-soft" href="schedule.php">' . edoc_h(edoc_t('prev')) . '</a></div></form></div>';
edoc_app_end();
