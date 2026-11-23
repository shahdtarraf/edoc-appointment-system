<?php
require __DIR__ . '/_init.php';
$res = $database->query('SELECT pid, pname, pemail FROM patient ORDER BY pid');
edoc_app_start(edoc_t('users'), 'a', $username, $useremail, 'patient.php');
echo '<table class="edoc-table"><tr><th>ID</th><th>' . edoc_h(edoc_t('patients')) . '</th><th>' . edoc_h(edoc_t('email')) . '</th></tr>';
while ($row = $res->fetch_assoc()) {
    echo '<tr><td>' . (int) $row['pid'] . '</td><td>' . edoc_h($row['pname']) . '</td><td>' . edoc_h($row['pemail']) . '</td></tr>';
}
echo '</table>';
edoc_app_end();
