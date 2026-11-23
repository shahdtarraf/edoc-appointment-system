<?php
require __DIR__ . '/_init.php';

$spec = isset($_GET['spec']) ? (int) $_GET['spec'] : 0;
$facility = isset($_GET['facility']) ? (int) $_GET['facility'] : 0;
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$available = isset($_GET['available']) && $_GET['available'] === '1';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per = 12;
$offset = ($page - 1) * $per;
$hasFacility = edoc_has_column($database, 'doctor', 'facility_id');

$specOptions = $database->query('SELECT id, sname FROM specialties ORDER BY id');
$facOptions = $hasFacility ? $database->query('SELECT facility_id, name FROM facility ORDER BY facility_id') : null;

$sql = 'SELECT d.docid, d.docname, d.specialties, s.sname';
if ($hasFacility) {
    $sql .= ', d.facility_id, f.name AS facility_name';
}
$sql .= ' FROM doctor d LEFT JOIN specialties s ON s.id = d.specialties';
if ($hasFacility) {
    $sql .= ' LEFT JOIN facility f ON f.facility_id = d.facility_id';
}
$where = array();
$types = '';
$params = array();
if ($spec > 0) {
    $where[] = 'd.specialties = ?';
    $types .= 'i';
    $params[] = $spec;
}
if ($hasFacility && $facility > 0) {
    $where[] = 'd.facility_id = ?';
    $types .= 'i';
    $params[] = $facility;
}
if ($q !== '') {
    $where[] = 'd.docname LIKE ?';
    $types .= 's';
    $params[] = '%' . $q . '%';
}
if ($available) {
    $where[] = 'EXISTS (SELECT 1 FROM schedule sch WHERE sch.docid = d.docid)';
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$countSql = 'SELECT COUNT(*) c FROM (' . $sql . ') t';
$sql .= ' ORDER BY d.docid ASC LIMIT ? OFFSET ?';

function edoc_bind($stmt, $types, $params)
{
    if ($types === '') {
        return;
    }
    $bind = array($types);
    foreach ($params as $k => $v) {
        $bind[] = &$params[$k];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind);
}

$countStmt = $database->prepare($countSql);
edoc_bind($countStmt, $types, $params);
$countStmt->execute();
$total = (int) $countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

$types2 = $types . 'ii';
$params2 = $params;
$params2[] = $per;
$params2[] = $offset;
$stmt = $database->prepare($sql);
edoc_bind($stmt, $types2, $params2);
$stmt->execute();
$res = $stmt->get_result();
$pages = max(1, (int) ceil($total / $per));

edoc_app_start(edoc_t('find_doctor'), 'p', $username, $useremail, 'doctors.php');
echo '<form class="edoc-filters" method="get">';
echo '<input type="search" name="q" value="' . edoc_h($q) . '" placeholder="' . edoc_h(edoc_t('doctor_name')) . '">';
echo '<select name="spec"><option value="0">' . edoc_h(edoc_t('filter_spec')) . '</option>';
if ($specOptions) {
    while ($sp = $specOptions->fetch_assoc()) {
        $sel = ((int) $sp['id'] === $spec) ? ' selected' : '';
        echo '<option value="' . (int) $sp['id'] . '"' . $sel . '>' . edoc_h($sp['sname']) . '</option>';
    }
}
echo '</select>';
if ($facOptions) {
    echo '<select name="facility"><option value="0">' . edoc_h(edoc_t('filter_facility')) . '</option>';
    while ($fc = $facOptions->fetch_assoc()) {
        $sel = ((int) $fc['facility_id'] === $facility) ? ' selected' : '';
        echo '<option value="' . (int) $fc['facility_id'] . '"' . $sel . '>' . edoc_h($fc['name']) . '</option>';
    }
    echo '</select>';
}
echo '<label><input type="checkbox" name="available" value="1"' . ($available ? ' checked' : '') . '> ' . edoc_h(edoc_t('has_sessions')) . '</label>';
echo '<button class="edoc-btn edoc-btn-primary">' . edoc_h(edoc_t('apply_filters')) . '</button>';
echo '<a class="edoc-btn edoc-btn-soft" href="doctors.php">' . edoc_h(edoc_t('reset_filters')) . '</a></form>';

echo '<div class="edoc-grid">';
$n = 0;
while ($row = $res->fetch_assoc()) {
    $n++;
    echo '<div class="edoc-card"><div class="edoc-doc">' . edoc_avatar_html($row['docname']) . '<div>';
    echo edoc_demo_badge();
    echo '<h3>' . edoc_h($row['docname']) . '</h3>';
    echo '<p class="edoc-muted">' . edoc_h($row['sname']);
    if (!empty($row['facility_name'])) {
        echo ' · ' . edoc_h($row['facility_name']);
    }
    echo '</p>';
    $sid = (int) $row['docid'];
    $sess = $database->prepare('SELECT COUNT(*) c FROM schedule WHERE docid = ?');
    $docidStr = (string) $sid;
    $sess->bind_param('s', $docidStr);
    $sess->execute();
    $sc = (int) $sess->get_result()->fetch_assoc()['c'];
    $sess->close();
    echo '<p class="edoc-muted">' . edoc_h(edoc_t('available_sessions')) . ': ' . $sc . '</p>';
    echo '<a class="edoc-btn edoc-btn-primary" href="doctor-view.php?id=' . $sid . '">' . edoc_h(edoc_t('view_details')) . '</a> ';
    echo '<a class="edoc-btn edoc-btn-soft" href="schedule.php?docid=' . $sid . '">' . edoc_h(edoc_t('sessions')) . '</a>';
    echo '</div></div></div>';
}
echo '</div>';
if ($n === 0) {
    edoc_empty(edoc_t('empty_doctors'), edoc_t('demo_notice'), 'doctors.php', edoc_t('show_all'));
}
echo '<div class="edoc-pager">';
$qs = http_build_query(array('spec' => $spec, 'facility' => $facility, 'q' => $q, 'available' => $available ? '1' : ''));
if ($page > 1) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?' . $qs . '&page=' . ($page - 1) . '">' . edoc_h(edoc_t('prev')) . '</a>';
}
echo '<span>' . edoc_h(edoc_t('page')) . ' ' . $page . ' / ' . $pages . '</span>';
if ($page < $pages) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?' . $qs . '&page=' . ($page + 1) . '">' . edoc_h(edoc_t('next')) . '</a>';
}
echo '</div>';
edoc_app_end();
