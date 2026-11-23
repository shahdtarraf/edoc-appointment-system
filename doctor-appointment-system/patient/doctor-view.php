<?php
require __DIR__ . '/_init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $database->prepare(
    'SELECT d.*, s.sname FROM doctor d LEFT JOIN specialties s ON s.id = d.specialties WHERE d.docid = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doc) {
    header('location: doctors.php');
    exit;
}

$facilityName = '';
if (edoc_has_column($database, 'doctor', 'facility_id') && !empty($doc['facility_id'])) {
    $fid = (int) $doc['facility_id'];
    $fs = $database->prepare('SELECT name, city, area_label FROM facility WHERE facility_id = ?');
    if ($fs) {
        $fs->bind_param('i', $fid);
        $fs->execute();
        $frow = $fs->get_result()->fetch_assoc();
        $fs->close();
        if ($frow) {
            $facilityName = $frow['name'] . ' — ' . $frow['city'] . ' / ' . $frow['area_label'];
        }
    }
}

$docidStr = (string) $id;
$sess = $database->prepare('SELECT scheduleid, title, scheduledate, scheduletime, nop FROM schedule WHERE docid = ? ORDER BY scheduledate, scheduletime');
$sess->bind_param('s', $docidStr);
$sess->execute();
$sessions = $sess->get_result();

edoc_app_start(edoc_t('view_details'), 'p', $username, $useremail, 'doctors.php');
echo '<div class="edoc-card"><div class="edoc-doc">' . edoc_avatar_html($doc['docname']) . '<div>';
echo edoc_demo_badge();
echo '<h2>' . edoc_h($doc['docname']) . '</h2>';
echo '<p>' . edoc_h($doc['sname']) . '</p>';
if ($facilityName !== '') {
    echo '<p class="edoc-muted">' . edoc_h($facilityName) . '</p>';
}
echo '<p class="edoc-muted">' . edoc_h(edoc_t('synthetic')) . '</p>';
echo '<p class="edoc-muted">' . edoc_h(edoc_t('no_license')) . ' ' . edoc_h(edoc_t('no_ratings')) . '</p>';
echo '</div></div></div>';
echo '<h2>' . edoc_h(edoc_t('available_sessions')) . ' ' . edoc_demo_badge('session') . '</h2>';
if ($sessions->num_rows === 0) {
    edoc_empty(edoc_t('empty_sessions'), edoc_t('demo_notice'), 'schedule.php', edoc_t('sessions'));
} else {
    echo '<table class="edoc-table"><tr><th>' . edoc_h(edoc_t('session_title')) . '</th><th>' . edoc_h(edoc_t('session_date')) . '</th><th>' . edoc_h(edoc_t('session_time')) . '</th><th></th></tr>';
    while ($s = $sessions->fetch_assoc()) {
        echo '<tr><td>' . edoc_h($s['title']) . '</td><td>' . edoc_h($s['scheduledate']) . '</td><td>' . edoc_h($s['scheduletime']) . '</td>';
        echo '<td><a class="edoc-btn edoc-btn-primary" href="booking.php?id=' . (int) $s['scheduleid'] . '">' . edoc_h(edoc_t('book')) . '</a></td></tr>';
    }
    echo '</table>';
}
edoc_app_end();
