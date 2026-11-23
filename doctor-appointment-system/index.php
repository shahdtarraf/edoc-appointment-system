<?php
require_once __DIR__ . '/includes/layout.php';
edoc_bootstrap(true);

$specs = array();
$doctors = array();
if (isset($database) && $database instanceof mysqli && empty($database->connect_error)) {
    $res = $database->query('SELECT id, sname FROM specialties ORDER BY id ASC LIMIT 8');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $specs[] = $row;
        }
    }
    $sql = 'SELECT d.docid, d.docname, d.specialties, s.sname
            FROM doctor d
            LEFT JOIN specialties s ON s.id = d.specialties
            ORDER BY d.docid ASC LIMIT 6';
    $res = $database->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
}

edoc_head(edoc_t('brand'));
edoc_public_nav();
echo '<div class="edoc-container">';
echo '<section class="edoc-hero">';
echo '<p class="edoc-badge edoc-badge-demo">' . edoc_h(edoc_t('demo_notice')) . '</p>';
echo '<h1>' . edoc_h(edoc_t('hero_title')) . '</h1>';
echo '<p>' . edoc_h(edoc_t('hero_sub')) . '</p>';
echo '<form class="edoc-search" method="get" action="specialties.php">';
echo '<input type="search" name="q" placeholder="' . edoc_h(edoc_t('search_placeholder')) . '">';
echo '<button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('search_btn')) . '</button>';
echo '</form>';
echo '<div class="edoc-hero-actions">';
echo '<a class="edoc-btn edoc-btn-ghost" href="login.php">' . edoc_h(edoc_t('hero_cta')) . '</a>';
echo '<a class="edoc-btn edoc-btn-ghost" href="specialties.php">' . edoc_h(edoc_t('hero_secondary')) . '</a>';
echo '</div></section>';

echo '<section class="edoc-section" id="specialties"><h2>' . edoc_h(edoc_t('popular_specs')) . '</h2>';
echo '<div class="edoc-grid">';
foreach ($specs as $sp) {
    echo '<a class="edoc-card" href="specialties.php?q=' . urlencode($sp['sname']) . '">';
    echo '<div class="edoc-avatar" style="background:var(--edoc-soft);color:var(--edoc-primary-dark)">' . (int) $sp['id'] . '</div>';
    echo '<h3>' . edoc_h($sp['sname']) . '</h3></a>';
}
echo '</div><p style="margin-top:12px"><a href="specialties.php">' . edoc_h(edoc_t('all_56')) . '</a></p></section>';

echo '<section class="edoc-section" id="doctors"><h2>' . edoc_h(edoc_t('suggested_docs')) . '</h2>';
echo '<div class="edoc-grid">';
foreach ($doctors as $doc) {
    echo '<div class="edoc-card"><div class="edoc-doc">' . edoc_avatar_html($doc['docname']);
    echo '<div><div>' . edoc_demo_badge('doctor') . '</div><h3>' . edoc_h($doc['docname']) . '</h3>';
    echo '<p class="edoc-muted">' . edoc_h($doc['sname']) . '</p>';
    echo '<a class="edoc-btn edoc-btn-soft" href="login.php">' . edoc_h(edoc_t('view_details')) . '</a>';
    echo '</div></div></div>';
}
echo '</div></section>';

echo '<section class="edoc-section" id="how"><h2>' . edoc_h(edoc_t('how_title')) . '</h2><div class="edoc-steps">';
echo '<div class="edoc-step"><b>1</b><p>' . edoc_h(edoc_t('how_1')) . '</p></div>';
echo '<div class="edoc-step"><b>2</b><p>' . edoc_h(edoc_t('how_2')) . '</p></div>';
echo '<div class="edoc-step"><b>3</b><p>' . edoc_h(edoc_t('how_3')) . '</p></div>';
echo '</div></section>';

echo '<section class="edoc-section" id="ai"><div class="edoc-card">';
echo '<h2>' . edoc_h(edoc_t('ai_highlight')) . '</h2>';
echo '<p>' . edoc_h(edoc_t('ai_highlight_text')) . '</p>';
echo '<a class="edoc-btn edoc-btn-primary" href="login.php">' . edoc_h(edoc_t('ai_cta')) . '</a>';
echo '</div></section>';

echo '<section class="edoc-section"><h2>' . edoc_h(edoc_t('services_title')) . '</h2><div class="edoc-grid">';
echo '<div class="edoc-card"><h3>' . edoc_h(edoc_t('svc_dir')) . '</h3></div>';
echo '<div class="edoc-card"><h3>' . edoc_h(edoc_t('svc_book')) . '</h3></div>';
echo '<div class="edoc-card"><h3>' . edoc_h(edoc_t('svc_roles')) . '</h3></div>';
echo '</div></section></div>';
edoc_public_footer();
