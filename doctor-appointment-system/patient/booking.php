<?php
require __DIR__ . '/_init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $database->prepare(
    'SELECT schedule.scheduleid, schedule.title, schedule.scheduledate, schedule.scheduletime, schedule.nop,
            doctor.docid, doctor.docname, doctor.docemail
     FROM schedule INNER JOIN doctor ON schedule.docid = doctor.docid
     WHERE schedule.scheduleid = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    header('location: schedule.php');
    exit;
}

$scheduleid = (int) $row['scheduleid'];
$apponum = 1;
$maxStmt = $database->prepare('SELECT COALESCE(MAX(apponum), 0) + 1 AS next_apponum FROM appointment WHERE scheduleid = ?');
if ($maxStmt !== false) {
    $maxStmt->bind_param('i', $scheduleid);
    if ($maxStmt->execute()) {
        $maxRow = $maxStmt->get_result()->fetch_assoc();
        if ($maxRow) {
            $apponum = max(1, (int) $maxRow['next_apponum']);
        }
    }
    $maxStmt->close();
}

edoc_app_start(edoc_t('confirm_booking'), 'p', $username, $useremail, 'schedule.php');
echo '<div class="edoc-card">';
echo edoc_demo_badge('appointment');
echo '<div class="edoc-doc" style="margin-top:12px">' . edoc_avatar_html($row['docname']) . '<div>';
echo '<h2>' . edoc_h($row['docname']) . '</h2>';
echo edoc_demo_badge('doctor');
echo '<p>' . edoc_h($row['title']) . '</p>';
echo '<p class="edoc-muted">' . edoc_h($row['scheduledate']) . ' · ' . edoc_h(substr($row['scheduletime'], 0, 5)) . '</p>';
echo '</div></div>';
echo '<p>' . edoc_h(edoc_t('appt_number')) . ': <strong>' . (int) $apponum . '</strong></p>';
echo '<form method="post" action="booking-complete.php">';
echo edoc_csrf_field();
echo '<input type="hidden" name="scheduleid" value="' . $scheduleid . '">';
echo '<button class="edoc-btn edoc-btn-primary" type="submit" name="booknow" value="1">' . edoc_h(edoc_t('confirm_booking')) . '</button> ';
echo '<a class="edoc-btn edoc-btn-soft" href="schedule.php">' . edoc_h(edoc_t('prev')) . '</a>';
echo '</form></div>';
edoc_app_end();
