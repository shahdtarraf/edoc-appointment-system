<?php
require __DIR__ . '/_init.php';
$doctors = (int) $database->query('SELECT COUNT(*) c FROM doctor')->fetch_assoc()['c'];
$specs = (int) $database->query('SELECT COUNT(*) c FROM specialties')->fetch_assoc()['c'];
$patients = (int) $database->query('SELECT COUNT(*) c FROM patient')->fetch_assoc()['c'];
$sessions = (int) $database->query('SELECT COUNT(*) c FROM schedule')->fetch_assoc()['c'];
$bookings = (int) $database->query('SELECT COUNT(*) c FROM appointment')->fetch_assoc()['c'];
$facilities = 0;
if (edoc_has_column($database, 'facility', 'facility_id')) {
    $facilities = (int) $database->query('SELECT COUNT(*) c FROM facility')->fetch_assoc()['c'];
}

edoc_app_start(edoc_t('admin'), 'a', $username, $useremail, 'index.php');
echo '<p class="edoc-muted">' . edoc_h(edoc_t('stats_demo')) . '</p>';
echo '<div class="edoc-stats">';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('doctors_count')) . '</span><b>' . $doctors . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('specs_count')) . '</span><b>' . $specs . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('facilities')) . '</span><b>' . $facilities . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('sessions_count')) . '</span><b>' . $sessions . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('bookings_count')) . '</span><b>' . $bookings . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('patients')) . '</span><b>' . $patients . '</b></div>';
echo '</div>';
require_once dirname(__DIR__) . '/includes/ai_board.php';
echo '<h2>' . edoc_h(edoc_t('ai_board_title')) . '</h2>';
edoc_ai_board();
edoc_app_end();
