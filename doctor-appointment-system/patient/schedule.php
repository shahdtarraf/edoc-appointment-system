<?php
require __DIR__ . '/_init.php';

$today = date('Y-m-d');
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$docid = isset($_GET['docid']) ? (int) $_GET['docid'] : 0;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per = 12;
$offset = ($page - 1) * $per;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $q = trim((string) $_POST['search']);
}

$countSql = 'SELECT COUNT(*) AS c FROM schedule INNER JOIN doctor ON schedule.docid = doctor.docid WHERE schedule.scheduledate >= ?';
$sql = 'SELECT schedule.scheduleid, schedule.title, schedule.scheduledate, schedule.scheduletime, schedule.nop,
               doctor.docid, doctor.docname, doctor.specialties
        FROM schedule INNER JOIN doctor ON schedule.docid = doctor.docid
        WHERE schedule.scheduledate >= ?';
$types = 's';
$params = array($today);
if ($docid > 0) {
    $sql .= ' AND doctor.docid = ?';
    $countSql .= ' AND doctor.docid = ?';
    $types .= 'i';
    $params[] = $docid;
}
if ($q !== '') {
    $sql .= ' AND (doctor.docname LIKE ? OR schedule.title LIKE ?)';
    $countSql .= ' AND (doctor.docname LIKE ? OR schedule.title LIKE ?)';
    $types .= 'ss';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
$total = 0;
$cstmt = $database->prepare($countSql);
$cbind = array($types);
$cparams = $params;
foreach ($cparams as $k => $v) {
    $cbind[] = &$cparams[$k];
}
call_user_func_array(array($cstmt, 'bind_param'), $cbind);
$cstmt->execute();
$total = (int) $cstmt->get_result()->fetch_assoc()['c'];
$cstmt->close();
$pages = max(1, (int) ceil($total / $per));
if ($page > $pages) {
    $page = $pages;
    $offset = ($page - 1) * $per;
}
$sql .= ' ORDER BY schedule.scheduledate ASC, schedule.scheduletime ASC LIMIT ? OFFSET ?';
$listTypes = $types . 'ii';
$listParams = $params;
$listParams[] = $per;
$listParams[] = $offset;
$stmt = $database->prepare($sql);
$bind = array($listTypes);
foreach ($listParams as $k => $v) {
    $bind[] = &$listParams[$k];
}
call_user_func_array(array($stmt, 'bind_param'), $bind);
$stmt->execute();
$res = $stmt->get_result();

edoc_app_start(edoc_t('sessions'), 'p', $username, $useremail, 'schedule.php');
echo '<form class="edoc-filters" method="get">';
echo '<input type="search" name="q" value="' . edoc_h($q) . '" placeholder="' . edoc_h(edoc_t('search_placeholder')) . '">';
if ($docid) {
    echo '<input type="hidden" name="docid" value="' . $docid . '">';
}
echo '<button class="edoc-btn edoc-btn-primary">' . edoc_h(edoc_t('search_btn')) . '</button></form>';
echo edoc_demo_badge('session');
echo '<div class="edoc-grid" style="margin-top:16px">';
$n = 0;
while ($row = $res->fetch_assoc()) {
    $n++;
    echo '<div class="edoc-card">';
    echo '<div>' . edoc_demo_badge('session') . '</div>';
    echo '<h3>' . edoc_h($row['title']) . '</h3>';
    echo '<p>' . edoc_h($row['docname']) . '</p>';
    echo '<p class="edoc-muted">' . edoc_h($row['scheduledate']) . ' · ' . edoc_h(substr($row['scheduletime'], 0, 5)) . '</p>';
    echo '<a class="edoc-btn edoc-btn-primary" href="booking.php?id=' . (int) $row['scheduleid'] . '">' . edoc_h(edoc_t('book')) . '</a>';
    echo '</div>';
}
echo '</div>';
if ($n === 0) {
    edoc_empty(edoc_t('empty_sessions'), edoc_t('demo_notice'), 'doctors.php', edoc_t('find_doctor'));
}
echo '<div class="edoc-pager">';
$qs = 'q=' . urlencode($q) . ($docid ? '&docid=' . $docid : '');
if ($page > 1) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?' . $qs . '&page=' . ($page - 1) . '">' . edoc_h(edoc_t('prev')) . '</a>';
}
echo '<span>' . edoc_h(edoc_t('page')) . ' ' . $page . ' / ' . $pages . '</span>';
if ($page < $pages) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?' . $qs . '&page=' . ($page + 1) . '">' . edoc_h(edoc_t('next')) . '</a>';
}
echo '</div>';
edoc_app_end();
