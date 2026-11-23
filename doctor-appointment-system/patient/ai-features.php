<?php
require __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/includes/ai_board.php';
edoc_app_start(edoc_t('ai_board_title'), 'p', $username, $useremail, 'ai-features.php');
edoc_ai_board();
edoc_app_end();
