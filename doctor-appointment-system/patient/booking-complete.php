<?php
require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booknow'])) {
    header('location: schedule.php');
    exit;
}
if (!edoc_csrf_verify()) {
    header('location: schedule.php');
    exit;
}

$scheduleid = filter_var(isset($_POST['scheduleid']) ? $_POST['scheduleid'] : 0, FILTER_VALIDATE_INT);
if ($userid < 1 || $scheduleid === false || $scheduleid < 1) {
    header('location: schedule.php');
    exit;
}

$scheduleStmt = $database->prepare('SELECT schedule.scheduleid, schedule.nop FROM schedule INNER JOIN doctor ON schedule.docid = doctor.docid WHERE schedule.scheduleid = ? LIMIT 1');
if ($scheduleStmt === false) {
    header('location: schedule.php');
    exit;
}
$scheduleStmt->bind_param('i', $scheduleid);
if (!$scheduleStmt->execute()) {
    $scheduleStmt->close();
    header('location: schedule.php');
    exit;
}
$scheduleResult = $scheduleStmt->get_result();
$scheduleStmt->close();
if ($scheduleResult === false || $scheduleResult->num_rows !== 1) {
    header('location: schedule.php');
    exit;
}
$scheduleRow = $scheduleResult->fetch_assoc();
$nop = (int) $scheduleRow['nop'];

$countStmt = $database->prepare('SELECT COUNT(*) AS booked, COALESCE(MAX(apponum), 0) AS max_apponum FROM appointment WHERE scheduleid = ?');
if ($countStmt === false) {
    header('location: schedule.php');
    exit;
}
$countStmt->bind_param('i', $scheduleid);
if (!$countStmt->execute()) {
    $countStmt->close();
    header('location: schedule.php');
    exit;
}
$countRow = $countStmt->get_result()->fetch_assoc();
$countStmt->close();
$booked = isset($countRow['booked']) ? (int) $countRow['booked'] : 0;
$maxApponum = isset($countRow['max_apponum']) ? (int) $countRow['max_apponum'] : 0;
if ($nop < 1 || $booked >= $nop) {
    header('location: schedule.php');
    exit;
}

$apponum = $maxApponum + 1;
$appodate = date('Y-m-d');
if (edoc_has_column($database, 'appointment', 'is_demo')) {
    $insertStmt = $database->prepare('INSERT INTO appointment (pid, apponum, scheduleid, appodate, is_demo, record_source, verification_status) VALUES (?, ?, ?, ?, 1, ?, ?)');
    $src = 'demo_seed_v1';
    $ver = 'simulation';
    $insertStmt->bind_param('iiisss', $userid, $apponum, $scheduleid, $appodate, $src, $ver);
} else {
    $insertStmt = $database->prepare('INSERT INTO appointment (pid, apponum, scheduleid, appodate) VALUES (?, ?, ?, ?)');
    $insertStmt->bind_param('iiis', $userid, $apponum, $scheduleid, $appodate);
}
if ($insertStmt === false || !$insertStmt->execute()) {
    if ($insertStmt) {
        $insertStmt->close();
    }
    header('location: schedule.php');
    exit;
}
$insertStmt->close();

edoc_app_start(edoc_t('status_ok'), 'p', $username, $useremail, 'appointment.php');
echo '<div class="edoc-card">';
edoc_alert('ok', edoc_t('status_ok'));
echo edoc_demo_badge('appointment');
echo '<p>' . edoc_h(edoc_t('appt_number')) . '</p>';
echo '<p style="font-size:3rem;font-weight:700;color:var(--edoc-primary-dark);margin:8px 0">' . (int) $apponum . '</p>';
echo '<a class="edoc-btn edoc-btn-primary" href="appointment.php">' . edoc_h(edoc_t('my_bookings')) . '</a> ';
echo '<a class="edoc-btn edoc-btn-soft" href="index.php">' . edoc_h(edoc_t('go_dashboard')) . '</a>';
echo '</div>';
edoc_app_end();
