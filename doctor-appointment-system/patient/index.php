<?php
require __DIR__ . '/_init.php';

$today = date('Y-m-d');
$doctors = (int) $database->query('SELECT COUNT(*) c FROM doctor')->fetch_assoc()['c'];
$specs = (int) $database->query('SELECT COUNT(*) c FROM specialties')->fetch_assoc()['c'];
$sessions = (int) $database->query('SELECT COUNT(*) c FROM schedule')->fetch_assoc()['c'];
$mine = 0;
$st = $database->prepare('SELECT COUNT(*) c FROM appointment WHERE pid = ?');
$st->bind_param('i', $userid);
$st->execute();
$mine = (int) $st->get_result()->fetch_assoc()['c'];
$st->close();

edoc_app_start(edoc_t('patient_panel'), 'p', $username, $useremail, 'index.php');
echo '<p class="edoc-muted">' . edoc_h(edoc_t('stats_demo')) . '</p>';
echo '<div class="edoc-stats">';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('doctors_count')) . '</span><b>' . $doctors . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('specs_count')) . '</span><b>' . $specs . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('sessions_count')) . '</span><b>' . $sessions . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('my_bookings')) . '</span><b>' . $mine . '</b></div>';
echo '</div>';
echo '<div class="edoc-grid">';
echo '<a class="edoc-card" href="doctors.php"><h3>' . edoc_h(edoc_t('find_doctor')) . '</h3></a>';
echo '<a class="edoc-card" href="specialties.php"><h3>' . edoc_h(edoc_t('nav_specialties')) . '</h3></a>';
echo '<a class="edoc-card" href="schedule.php"><h3>' . edoc_h(edoc_t('sessions')) . '</h3></a>';
echo '<a class="edoc-card" href="specialty-assist.php"><h3>' . edoc_h(edoc_t('specialty_help')) . '</h3></a>';
echo '</div>';
edoc_app_end();
