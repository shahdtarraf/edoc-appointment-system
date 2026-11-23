<?php
require __DIR__ . '/_init.php';
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $database->prepare(
        'SELECT d.docid, d.docname, d.docemail, s.sname FROM doctor d
         LEFT JOIN specialties s ON s.id = d.specialties WHERE d.docname LIKE ? ORDER BY d.docid LIMIT 200'
    );
    $stmt->bind_param('s', $like);
} else {
    $stmt = $database->prepare(
        'SELECT d.docid, d.docname, d.docemail, s.sname FROM doctor d
         LEFT JOIN specialties s ON s.id = d.specialties ORDER BY d.docid LIMIT 200'
    );
}
$stmt->execute();
$res = $stmt->get_result();
edoc_app_start(edoc_t('find_doctor'), 'a', $username, $useremail, 'doctors.php');
echo '<p><a class="edoc-btn edoc-btn-primary" href="add-new.php">' . edoc_h(edoc_t('add_doctor')) . '</a></p>';
echo '<form class="edoc-filters" method="get"><input name="q" value="' . edoc_h($q) . '"><button class="edoc-btn edoc-btn-primary">' . edoc_h(edoc_t('search_btn')) . '</button></form>';
echo '<div class="edoc-table-wrap"><table class="edoc-table"><tr><th>' . edoc_h(edoc_t('doctor_name')) . '</th><th>' . edoc_h(edoc_t('specialty')) . '</th><th>' . edoc_h(edoc_t('email')) . '</th><th></th></tr>';
while ($row = $res->fetch_assoc()) {
    echo '<tr><td>' . edoc_demo_badge() . ' ' . edoc_h($row['docname']) . '</td><td>' . edoc_h($row['sname']) . '</td><td>' . edoc_h($row['docemail']) . '</td><td>';
    echo '<a class="edoc-btn edoc-btn-soft" href="edit-doc.php?id=' . (int) $row['docid'] . '">' . edoc_h(edoc_t('edit')) . '</a> ';
    echo '<form method="post" action="delete-doctor.php" style="display:inline" onsubmit="return confirm(\'?\');">';
    echo edoc_csrf_field();
    echo '<input type="hidden" name="id" value="' . (int) $row['docid'] . '">';
    echo '<button class="edoc-btn edoc-btn-danger" type="submit">' . edoc_h(edoc_t('delete')) . '</button></form>';
    echo '</td></tr>';
}
echo '</table></div>';
edoc_app_end();
