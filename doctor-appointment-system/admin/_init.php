<?php
require_once dirname(__DIR__) . '/includes/layout.php';
edoc_bootstrap(true);
edoc_require_role('a');
$useremail = $_SESSION['user'];
$username = edoc_t('admin');
