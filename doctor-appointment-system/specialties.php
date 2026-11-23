<?php
require_once __DIR__ . '/includes/layout.php';
edoc_bootstrap(true);

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per = 12;
$offset = ($page - 1) * $per;
$rows = array();
$total = 0;

if (isset($database) && $database instanceof mysqli && empty($database->connect_error)) {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $c = $database->prepare('SELECT COUNT(*) AS c FROM specialties WHERE sname LIKE ?');
        $c->bind_param('s', $like);
        $c->execute();
        $total = (int) $c->get_result()->fetch_assoc()['c'];
        $c->close();
        $stmt = $database->prepare(
            'SELECT s.id, s.sname, COUNT(d.docid) AS docs
             FROM specialties s
             LEFT JOIN doctor d ON d.specialties = s.id
             WHERE s.sname LIKE ?
             GROUP BY s.id, s.sname
             ORDER BY s.id ASC LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('sii', $like, $per, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $totalRow = $database->query('SELECT COUNT(*) AS c FROM specialties');
        $total = (int) $totalRow->fetch_assoc()['c'];
        $stmt = $database->prepare(
            'SELECT s.id, s.sname, COUNT(d.docid) AS docs
             FROM specialties s
             LEFT JOIN doctor d ON d.specialties = s.id
             GROUP BY s.id, s.sname
             ORDER BY s.id ASC LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('ii', $per, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
    }
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
}

$pages = max(1, (int) ceil($total / $per));
edoc_head(edoc_t('nav_specialties'));
edoc_public_nav();
echo '<div class="edoc-container edoc-section">';
echo '<h1>' . edoc_h(edoc_t('all_56')) . '</h1>';
echo '<p class="edoc-muted">' . edoc_h(edoc_t('demo_notice')) . '</p>';
echo '<form class="edoc-filters" method="get"><input type="search" name="q" value="' . edoc_h($q) . '" placeholder="' . edoc_h(edoc_t('search_placeholder')) . '">';
echo '<button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('search_btn')) . '</button></form>';
echo '<div class="edoc-grid">';
foreach ($rows as $row) {
    $href = 'patient/doctors.php?spec=' . (int) $row['id'];
    echo '<a class="edoc-card" href="' . $href . '">';
    echo '<div class="edoc-avatar" style="background:var(--edoc-soft);color:var(--edoc-primary-dark)">' . (int) $row['id'] . '</div>';
    echo '<h3>' . edoc_h($row['sname']) . '</h3>';
    echo '<p class="edoc-muted">' . (int) $row['docs'] . ' ' . edoc_h(edoc_t('doctors_in_spec')) . ' ' . edoc_demo_badge('doctor') . '</p>';
    echo '</a>';
}
echo '</div>';
if (!$rows) {
    edoc_empty(edoc_t('empty_doctors'), edoc_t('demo_notice'), 'specialties.php', edoc_t('show_all'));
}
echo '<div class="edoc-pager">';
if ($page > 1) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?q=' . urlencode($q) . '&page=' . ($page - 1) . '">' . edoc_h(edoc_t('prev')) . '</a>';
}
echo '<span>' . edoc_h(edoc_t('page')) . ' ' . $page . ' / ' . $pages . '</span>';
if ($page < $pages) {
    echo '<a class="edoc-btn edoc-btn-soft" href="?q=' . urlencode($q) . '&page=' . ($page + 1) . '">' . edoc_h(edoc_t('next')) . '</a>';
}
echo '</div></div>';
edoc_public_footer();
