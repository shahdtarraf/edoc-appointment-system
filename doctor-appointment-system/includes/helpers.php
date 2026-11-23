<?php

function edoc_prefix()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    if (preg_match('#/(patient|doctor|admin)/#', $script)) {
        return '../';
    }
    return '';
}

function edoc_has_column($database, $table, $column)
{
    static $cache = array();
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $cache[$key] = false;
    if (!($database instanceof mysqli) || $database->connect_error) {
        return false;
    }
    $stmt = $database->prepare(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    if ($stmt->execute()) {
        $row = $stmt->get_result()->fetch_assoc();
        $cache[$key] = isset($row['c']) && (int) $row['c'] > 0;
    }
    $stmt->close();
    return $cache[$key];
}

function edoc_avatar_html($name)
{
    $name = trim((string) $name);
    $parts = preg_split('/\s+/', $name);
    $first = isset($parts[0]) ? $parts[0] : 'E';
    $second = isset($parts[1]) ? $parts[1] : 'D';
    $a = strtoupper(substr($first, 0, 1));
    $b = strtoupper(substr($second, 0, 1));
    if ($a === '') {
        $a = 'E';
    }
    if ($b === '') {
        $b = 'D';
    }
    $hue = abs(crc32($name)) % 50 + 150;
    $bg = 'hsl(' . $hue . ',42%,88%)';
    $fg = 'hsl(' . $hue . ',38%,28%)';
    return '<span class="edoc-avatar" style="background:' . $bg . ';color:' . $fg . '">' . edoc_h($a . $b) . '</span>';
}

function edoc_demo_badge($kind = 'doctor')
{
    if ($kind === 'appointment') {
        return '<span class="edoc-badge edoc-badge-demo">' . edoc_h(edoc_t('badge_demo_appt')) . '</span>';
    }
    if ($kind === 'session') {
        return '<span class="edoc-badge edoc-badge-demo">' . edoc_h(edoc_t('badge_demo_session')) . '</span>';
    }
    return '<span class="edoc-badge edoc-badge-demo">' . edoc_h(edoc_t('badge_demo_doctor')) . '</span>';
}

function edoc_empty($title, $text, $href = '', $cta = '')
{
    echo '<div class="edoc-empty">';
    echo '<div class="edoc-empty-icon">◇</div>';
    echo '<h3>' . edoc_h($title) . '</h3>';
    echo '<p>' . edoc_h($text) . '</p>';
    if ($href !== '') {
        echo '<a class="edoc-btn edoc-btn-primary" href="' . edoc_h($href) . '">' . edoc_h($cta) . '</a>';
    }
    echo '</div>';
}

function edoc_alert($type, $message)
{
    echo '<div class="edoc-alert edoc-alert-' . edoc_h($type) . '">' . edoc_h($message) . '</div>';
}

function edoc_require_role($role)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $prefix = edoc_prefix();
    if (!isset($_SESSION['user']) || $_SESSION['user'] === '' || $_SESSION['usertype'] !== $role) {
        header('location: ' . $prefix . 'login.php');
        exit;
    }
}
