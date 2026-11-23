<?php
require __DIR__ . '/_init.php';
edoc_app_start(edoc_t('settings'), 'd', $username, $useremail, 'settings.php');
echo '<div class="edoc-card">';
echo edoc_demo_badge();
echo '<h3>' . edoc_h($username) . '</h3>';
echo '<p class="edoc-muted">' . edoc_h($useremail) . '</p>';
echo '<p class="edoc-muted">' . edoc_h(edoc_t('synthetic')) . ' · ' . edoc_h(edoc_t('no_license')) . '</p>';
echo '<p><a class="edoc-btn edoc-btn-soft" href="edit-doc.php">' . edoc_h(edoc_t('profile')) . '</a></p>';
echo '</div>';
edoc_app_end();
