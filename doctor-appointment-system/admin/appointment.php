<?php
require __DIR__ . '/_init.php';
$res = $database->query(
    'SELECT a.appoid, a.apponum, p.pname, d.docname, s.title, s.scheduledate
     FROM appointment a
     INNER JOIN patient p ON a.pid = p.pid
     INNER JOIN schedule s ON a.scheduleid = s.scheduleid
     INNER JOIN doctor d ON s.docid = d.docid
     ORDER BY a.appoid DESC LIMIT 150'
);
edoc_app_start(edoc_t('booked_appts'), 'a', $username, $useremail, 'appointment.php');
echo edoc_demo_badge('appointment');
echo '<table class="edoc-table"><tr><th>ID</th><th>' . edoc_h(edoc_t('appt_number')) . '</th><th>' . edoc_h(edoc_t('patients')) . '</th><th>' . edoc_h(edoc_t('doctor_name')) . '</th><th>' . edoc_h(edoc_t('session_date')) . '</th></tr>';
while ($row = $res->fetch_assoc()) {
    echo '<tr><td>' . (int) $row['appoid'] . '</td><td>' . (int) $row['apponum'] . '</td><td>' . edoc_h($row['pname']) . '</td><td>' . edoc_h($row['docname']) . '</td><td>' . edoc_h($row['scheduledate']) . '</td></tr>';
}
echo '</table>';
edoc_app_end();
