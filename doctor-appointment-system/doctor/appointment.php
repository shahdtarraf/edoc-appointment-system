<?php
require __DIR__ . '/_init.php';
$docidStr = (string) $userid;
$stmt = $database->prepare(
    'SELECT a.appoid, a.apponum, a.appodate, s.title, s.scheduledate, s.scheduletime, p.pname
     FROM appointment a
     INNER JOIN schedule s ON a.scheduleid = s.scheduleid
     INNER JOIN patient p ON a.pid = p.pid
     WHERE s.docid = ?
     ORDER BY s.scheduledate DESC'
);
$stmt->bind_param('s', $docidStr);
$stmt->execute();
$res = $stmt->get_result();
edoc_app_start(edoc_t('booked_appts'), 'd', $username, $useremail, 'appointment.php');
echo edoc_demo_badge('appointment');
echo '<table class="edoc-table"><tr><th>' . edoc_h(edoc_t('appt_number')) . '</th><th>' . edoc_h(edoc_t('patients')) . '</th><th>' . edoc_h(edoc_t('session_title')) . '</th><th>' . edoc_h(edoc_t('session_date')) . '</th></tr>';
$n = 0;
while ($row = $res->fetch_assoc()) {
    $n++;
    echo '<tr><td>' . (int) $row['apponum'] . '</td><td>' . edoc_h($row['pname']) . '</td><td>' . edoc_h($row['title']) . '</td><td>' . edoc_h($row['scheduledate']) . '</td></tr>';
}
echo '</table>';
if ($n === 0) {
    edoc_empty(edoc_t('empty_bookings'), edoc_t('demo_notice'));
}
edoc_app_end();
