<?php
require __DIR__ . '/_init.php';
$res = $database->query(
    'SELECT s.scheduleid, s.title, s.scheduledate, s.scheduletime, d.docname
     FROM schedule s INNER JOIN doctor d ON s.docid = d.docid
     ORDER BY s.scheduledate LIMIT 150'
);
edoc_app_start(edoc_t('sessions'), 'a', $username, $useremail, 'schedule.php');
echo '<p><a class="edoc-btn edoc-btn-primary" href="add-session.php">' . edoc_h(edoc_t('add_session')) . '</a></p>';
echo edoc_demo_badge('session');
echo '<table class="edoc-table"><tr><th>' . edoc_h(edoc_t('session_title')) . '</th><th>' . edoc_h(edoc_t('doctor_name')) . '</th><th>' . edoc_h(edoc_t('session_date')) . '</th></tr>';
while ($row = $res->fetch_assoc()) {
    echo '<tr><td>' . edoc_h($row['title']) . '</td><td>' . edoc_h($row['docname']) . '</td><td>' . edoc_h($row['scheduledate']) . '</td></tr>';
}
echo '</table>';
edoc_app_end();
