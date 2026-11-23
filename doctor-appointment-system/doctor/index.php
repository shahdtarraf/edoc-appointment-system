<?php
require __DIR__ . '/_init.php';
$docidStr = (string) $userid;
$st = $database->prepare('SELECT COUNT(*) c FROM schedule WHERE docid = ?');
$st->bind_param('s', $docidStr);
$st->execute();
$sessions = (int) $st->get_result()->fetch_assoc()['c'];
$st->close();
$st = $database->prepare(
    'SELECT COUNT(*) c FROM appointment a INNER JOIN schedule s ON a.scheduleid = s.scheduleid WHERE s.docid = ?'
);
$st->bind_param('s', $docidStr);
$st->execute();
$bookings = (int) $st->get_result()->fetch_assoc()['c'];
$st->close();

edoc_app_start(edoc_t('doctor_panel'), 'd', $username, $useremail, 'index.php');
echo '<p class="edoc-muted">' . edoc_h(edoc_t('stats_demo')) . '</p>';
echo edoc_demo_badge();
echo '<div class="edoc-stats">';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('my_sessions')) . '</span><b>' . $sessions . '</b></div>';
echo '<div class="edoc-stat"><span>' . edoc_h(edoc_t('booked_appts')) . '</span><b>' . $bookings . '</b></div>';
echo '</div>';
$specName = '';
$specId = (int) $userfetch['specialties'];
$sp = $database->prepare('SELECT sname FROM specialties WHERE id = ? LIMIT 1');
if ($sp) {
    $sp->bind_param('i', $specId);
    if ($sp->execute()) {
        $spRow = $sp->get_result()->fetch_assoc();
        if ($spRow) {
            $specName = (string) $spRow['sname'];
        }
    }
    $sp->close();
}
echo '<div class="edoc-card"><h3>' . edoc_h($username) . '</h3><p class="edoc-muted">' . edoc_h($specName !== '' ? $specName : (string) $specId) . '</p></div>';
edoc_app_end();
