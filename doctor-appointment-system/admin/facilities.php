<?php
require __DIR__ . '/_init.php';
edoc_app_start(edoc_t('facilities'), 'a', $username, $useremail, 'facilities.php');
if (!edoc_has_column($database, 'facility', 'facility_id')) {
    edoc_empty(edoc_t('facilities'), edoc_t('demo_notice'));
} else {
    $res = $database->query('SELECT facility_id, name, city, area_label FROM facility ORDER BY facility_id');
    echo '<div class="edoc-grid">';
    while ($row = $res->fetch_assoc()) {
        echo '<div class="edoc-card"><h3>' . edoc_h($row['name']) . '</h3>';
        echo '<p class="edoc-muted">' . edoc_h($row['city']) . ' · ' . edoc_h($row['area_label']) . '</p>';
        echo edoc_demo_badge();
        echo '</div>';
    }
    echo '</div>';
}
edoc_app_end();
