<?php
require __DIR__ . '/_init.php';

$sql = 'SELECT appointment.appoid, appointment.apponum, appointment.appodate,
               schedule.title, schedule.scheduledate, schedule.scheduletime, doctor.docname
        FROM appointment
        INNER JOIN schedule ON appointment.scheduleid = schedule.scheduleid
        INNER JOIN doctor ON schedule.docid = doctor.docid
        WHERE appointment.pid = ?
        ORDER BY schedule.scheduledate DESC';
$stmt = $database->prepare($sql);
$stmt->bind_param('i', $userid);
$stmt->execute();
$res = $stmt->get_result();

edoc_app_start(edoc_t('my_bookings'), 'p', $username, $useremail, 'appointment.php');
if (isset($_GET['action']) && $_GET['action'] === 'booking-added') {
    edoc_alert('ok', edoc_t('status_ok') . ' #' . (int) (isset($_GET['id']) ? $_GET['id'] : 0));
}
echo edoc_demo_badge('appointment');
if ($res->num_rows === 0) {
    edoc_empty(edoc_t('empty_bookings'), edoc_t('demo_notice'), 'schedule.php', edoc_t('sessions'));
} else {
    echo '<div class="edoc-grid" style="margin-top:16px">';
    while ($row = $res->fetch_assoc()) {
        echo '<div class="edoc-card">';
        echo edoc_demo_badge('appointment');
        echo '<h3>' . edoc_h($row['title']) . '</h3>';
        echo '<p>' . edoc_h($row['docname']) . '</p>';
        echo '<p>' . edoc_h(edoc_t('appt_number')) . ': <strong>' . (int) $row['apponum'] . '</strong></p>';
        echo '<p class="edoc-muted">' . edoc_h($row['scheduledate']) . ' · ' . edoc_h(substr($row['scheduletime'], 0, 5)) . '</p>';
        echo '<form method="post" action="delete-appointment.php" onsubmit="return confirm(\'?\');">';
        echo edoc_csrf_field();
        echo '<input type="hidden" name="id" value="' . (int) $row['appoid'] . '">';
        echo '<button class="edoc-btn edoc-btn-danger" type="submit">' . edoc_h(edoc_t('cancel')) . '</button>';
        echo '</form></div>';
    }
    echo '</div>';
}
edoc_app_end();
