<?php
require __DIR__ . '/_init.php';
$docidStr = (string) $userid;
$stmt = $database->prepare(
    'SELECT DISTINCT p.pid, p.pname, p.pemail
     FROM patient p
     INNER JOIN appointment a ON a.pid = p.pid
     INNER JOIN schedule s ON a.scheduleid = s.scheduleid
     WHERE s.docid = ?
     ORDER BY p.pname'
);
$stmt->bind_param('s', $docidStr);
$stmt->execute();
$res = $stmt->get_result();
edoc_app_start(edoc_t('patients'), 'd', $username, $useremail, 'patient.php');
echo '<p class="edoc-muted">' . edoc_h(edoc_t('demo_notice')) . '</p>';
echo '<table class="edoc-table"><tr><th>' . edoc_h(edoc_t('patients')) . '</th><th>' . edoc_h(edoc_t('email')) . '</th></tr>';
while ($row = $res->fetch_assoc()) {
    echo '<tr><td>' . edoc_h($row['pname']) . '</td><td>' . edoc_h($row['pemail']) . '</td></tr>';
}
echo '</table>';
edoc_app_end();
