<?php
require __DIR__ . '/_init.php';
$res = $database->query(
    'SELECT s.id, s.sname, COUNT(d.docid) docs FROM specialties s LEFT JOIN doctor d ON d.specialties = s.id GROUP BY s.id, s.sname ORDER BY s.id'
);
edoc_app_start(edoc_t('nav_specialties'), 'a', $username, $useremail, 'specialties.php');
echo '<table class="edoc-table"><tr><th>ID</th><th>' . edoc_h(edoc_t('specialty')) . '</th><th>' . edoc_h(edoc_t('doctors_count')) . '</th></tr>';
while ($row = $res->fetch_assoc()) {
    echo '<tr><td>' . (int) $row['id'] . '</td><td>' . edoc_h($row['sname']) . '</td><td>' . (int) $row['docs'] . '</td></tr>';
}
echo '</table>';
edoc_app_end();
