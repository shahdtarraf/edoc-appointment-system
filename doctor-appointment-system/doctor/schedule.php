<?php
require __DIR__ . '/_init.php';
$docidStr = (string) $userid;
$stmt = $database->prepare('SELECT scheduleid, title, scheduledate, scheduletime, nop FROM schedule WHERE docid = ? ORDER BY scheduledate');
$stmt->bind_param('s', $docidStr);
$stmt->execute();
$res = $stmt->get_result();
edoc_app_start(edoc_t('my_sessions'), 'd', $username, $useremail, 'schedule.php');
echo edoc_demo_badge('session');
echo '<table class="edoc-table"><tr><th>' . edoc_h(edoc_t('session_title')) . '</th><th>' . edoc_h(edoc_t('session_date')) . '</th><th>' . edoc_h(edoc_t('session_time')) . '</th><th>' . edoc_h(edoc_t('capacity')) . '</th></tr>';
while ($row = $res->fetch_assoc()) {
    echo '<tr><td>' . edoc_h($row['title']) . '</td><td>' . edoc_h($row['scheduledate']) . '</td><td>' . edoc_h($row['scheduletime']) . '</td><td>' . (int) $row['nop'] . '</td></tr>';
}
echo '</table>';
edoc_app_end();
