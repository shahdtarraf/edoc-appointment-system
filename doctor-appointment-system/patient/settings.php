<?php
require __DIR__ . '/_init.php';
edoc_app_start(edoc_t('settings'), 'p', $username, $useremail, 'settings.php');
echo '<div class="edoc-card">';
echo '<p><strong>' . edoc_h($username) . '</strong></p>';
echo '<p class="edoc-muted">' . edoc_h($useremail) . '</p>';
echo '<p><a class="edoc-btn edoc-btn-soft" href="edit-user.php">' . edoc_h(edoc_t('profile')) . '</a></p>';
echo '</div>';
edoc_app_end();
