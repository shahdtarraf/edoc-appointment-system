<?php

function edoc_ai_board()
{
    echo '<p class="edoc-muted">' . edoc_h(edoc_t('ai_no_fake')) . '</p>';
    $items = array(
        array('ai_f1', 'ai_live', 'patient/specialty-assist.php', false),
        array('ai_assist_card', 'ai_live', '', false),
        array('ai_f2', 'ai_wip', '', true),
        array('ai_f3', 'ai_wip', '', true),
        array('ai_f4', 'ai_wip', '', true),
    );
    echo '<div class="edoc-grid">';
    foreach ($items as $item) {
        echo '<div class="edoc-card' . ($item[3] ? ' edoc-wip' : '') . '">';
        echo '<span class="edoc-badge ' . ($item[3] ? 'edoc-badge-wip' : 'edoc-badge-live') . '">' . edoc_h(edoc_t($item[1])) . '</span>';
        echo '<h3>' . edoc_h(edoc_t($item[0])) . '</h3>';
        if ($item[0] === 'ai_f1' && isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'p') {
            echo '<a class="edoc-btn edoc-btn-primary" href="specialty-assist.php">' . edoc_h(edoc_t('ai_cta')) . '</a>';
        }
        echo '</div>';
    }
    echo '</div>';
}
