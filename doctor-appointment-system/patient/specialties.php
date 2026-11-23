<?php
require __DIR__ . '/_init.php';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per = 12;
$offset = ($page - 1) * $per;
$like = '%' . $q . '%';
if ($q !== '') {
    $c = $database->prepare('SELECT COUNT(*) c FROM specialties WHERE sname LIKE ?');
    $c->bind_param('s', $like);
    $c->execute();
    $total = (int) $c->get_result()->fetch_assoc()['c'];
    $c->close();
    $stmt = $database->prepare(
        'SELECT s.id, s.sname, COUNT(d.docid) docs FROM specialties s
         LEFT JOIN doctor d ON d.specialties = s.id WHERE s.sname LIKE ?
         GROUP BY s.id, s.sname ORDER BY s.id LIMIT ? OFFSET ?'
    );
    $stmt->bind_param('sii', $like, $per, $offset);
} else {
    $total = (int) $database->query('SELECT COUNT(*) c FROM specialties')->fetch_assoc()['c'];
    $stmt = $database->prepare(
        'SELECT s.id, s.sname, COUNT(d.docid) docs FROM specialties s
         LEFT JOIN doctor d ON d.specialties = s.id
         GROUP BY s.id, s.sname ORDER BY s.id LIMIT ? OFFSET ?'
    );
    $stmt->bind_param('ii', $per, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
$pages = max(1, (int) ceil($total / $per));

edoc_app_start(edoc_t('nav_specialties'), 'p', $username, $useremail, 'specialties.php');
echo '<form class="edoc-filters" method="get"><input type="search" name="q" value="' . edoc_h($q) . '">';
echo '<button class="edoc-btn edoc-btn-primary">' . edoc_h(edoc_t('search_btn')) . '</button></form>';
echo '<div class="edoc-grid">';
while ($row = $res->fetch_assoc()) {
    echo '<a class="edoc-card" href="doctors.php?spec=' . (int) $row['id'] . '">';
    echo '<div class="edoc-avatar" style="background:var(--edoc-soft);color:var(--edoc-primary-dark)">' . (int) $row['id'] . '</div>';
    echo '<h3>' . edoc_h($row['sname']) . '</h3>';
    echo '<p class="edoc-muted">' . (int) $row['docs'] . ' ' . edoc_h(edoc_t('doctors_in_spec')) . ' ' . edoc_demo_badge() . '</p></a>';
}
echo '</div><div class="edoc-pager">';
if ($page > 1) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?q=' . urlencode($q) . '&page=' . ($page - 1) . '">' . edoc_h(edoc_t('prev')) . '</a>';
}
echo '<span>' . $page . ' / ' . $pages . '</span>';
if ($page < $pages) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?q=' . urlencode($q) . '&page=' . ($page + 1) . '">' . edoc_h(edoc_t('next')) . '</a>';
}
echo '</div>';
edoc_app_end();
