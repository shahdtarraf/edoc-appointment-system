<?php

function edoc_head($title)
{
    $dir = edoc_dir();
    $lang = edoc_lang();
    $prefix = edoc_prefix();
    echo '<!DOCTYPE html><html lang="' . edoc_h($lang) . '" dir="' . edoc_h($dir) . '">';
    echo '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . edoc_h($title) . '</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="' . edoc_h($prefix) . 'css/edoc-ui.css">';
    echo '<link rel="stylesheet" href="' . edoc_h($prefix) . 'css/edoc-assistant.css">';
    echo '</head><body class="edoc-body">';
}

function edoc_public_nav()
{
    $p = edoc_prefix();
    echo '<header class="edoc-container edoc-public-nav">';
    echo '<a class="edoc-brand" href="' . $p . 'index.php">' . edoc_h(edoc_t('brand')) . '<span>' . edoc_h(edoc_t('tagline')) . '</span></a>';
    echo '<input type="checkbox" id="edoc-pub-nav" class="edoc-nav-check">';
    echo '<label class="edoc-public-toggle" for="edoc-pub-nav">' . edoc_h(edoc_t('menu')) . '</label>';
    echo '<nav class="edoc-nav-links" id="edoc-public-links">';
    echo '<a href="' . $p . 'index.php">' . edoc_h(edoc_t('nav_home')) . '</a>';
    echo '<a href="' . $p . 'specialties.php">' . edoc_h(edoc_t('nav_specialties')) . '</a>';
    echo '<a href="' . $p . 'index.php#doctors">' . edoc_h(edoc_t('nav_doctors')) . '</a>';
    echo '<a href="' . $p . 'index.php#how">' . edoc_h(edoc_t('nav_how')) . '</a>';
    echo '<a href="' . $p . 'login.php">' . edoc_h(edoc_t('nav_login')) . '</a>';
    echo '<a class="edoc-btn edoc-btn-primary" href="' . $p . 'signup.php">' . edoc_h(edoc_t('nav_signup')) . '</a>';
    echo '<a href="' . edoc_h(edoc_lang_toggle_url()) . '">' . edoc_h(edoc_t('lang_switch')) . '</a>';
    echo '</nav></header>';
}

function edoc_page_end()
{
    require_once __DIR__ . '/assistant_widget.php';
    edoc_assistant_widget();
    echo '</body></html>';
}

function edoc_public_footer()
{
    $p = edoc_prefix();
    echo '<footer class="edoc-container edoc-footer"><p>' . edoc_h(edoc_t('footer_demo')) . '</p>';
    echo '<p><a href="' . $p . 'index.legacy.html">' . edoc_h(edoc_t('footer_legacy')) . '</a></p></footer>';
    edoc_page_end();
}

function edoc_menu_items($role)
{
    if ($role === 'p') {
        return array(
            'index.php' => edoc_t('dashboard'),
            'specialties.php' => edoc_t('nav_specialties'),
            'doctors.php' => edoc_t('find_doctor'),
            'schedule.php' => edoc_t('sessions'),
            'appointment.php' => edoc_t('my_bookings'),
            'specialty-assist.php' => edoc_t('specialty_help'),
            'ai-features.php' => edoc_t('ai_features'),
            'settings.php' => edoc_t('settings'),
        );
    }
    if ($role === 'd') {
        return array(
            'index.php' => edoc_t('dashboard'),
            'schedule.php' => edoc_t('my_sessions'),
            'appointment.php' => edoc_t('booked_appts'),
            'patient.php' => edoc_t('patients'),
            'settings.php' => edoc_t('settings'),
        );
    }
    return array(
        'index.php' => edoc_t('dashboard'),
        'doctors.php' => edoc_t('find_doctor'),
        'specialties.php' => edoc_t('nav_specialties'),
        'facilities.php' => edoc_t('facilities'),
        'schedule.php' => edoc_t('sessions'),
        'appointment.php' => edoc_t('booked_appts'),
        'patient.php' => edoc_t('users'),
        'ai-features.php' => edoc_t('ai_features'),
    );
}

function edoc_app_start($title, $role, $username, $useremail, $active)
{
    edoc_head($title);
    $p = edoc_prefix();
    echo '<div class="edoc-shell">';
    echo '<aside class="edoc-sidebar">';
    echo '<input type="checkbox" id="edoc-nav" class="edoc-nav-check">';
    echo '<div class="edoc-side-user">';
    echo '<strong>' . edoc_h($username) . '</strong><span>' . edoc_h($useremail) . '</span>';
    echo '<label class="edoc-menu-toggle" for="edoc-nav">' . edoc_h(edoc_t('menu')) . '</label></div>';
    echo '<div class="edoc-nav-block">';
    foreach (edoc_menu_items($role) as $href => $label) {
        $cls = ($href === $active) ? ' class="active"' : '';
        echo '<a' . $cls . ' href="' . edoc_h($href) . '">' . edoc_h($label) . '</a>';
    }
    echo '<a href="' . edoc_h(edoc_lang_toggle_url()) . '">' . edoc_h(edoc_t('lang_switch')) . '</a>';
    echo '<a href="' . $p . 'logout.php">' . edoc_h(edoc_t('nav_logout')) . '</a>';
    echo '</div></aside><main class="edoc-main">';
    echo '<div class="edoc-notice-bar">' . edoc_h(edoc_t('demo_notice')) . '</div>';
    echo '<div class="edoc-topbar"><h1>' . edoc_h($title) . '</h1>';
    echo '<span class="edoc-muted">' . edoc_h(edoc_t('today_date')) . ': ' . edoc_h(date('Y-m-d')) . '</span></div>';
}

function edoc_app_end()
{
    echo '</main></div>';
    edoc_page_end();
}

function edoc_bootstrap($needDb = true)
{
    global $database, $servername, $port, $useSsl;
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once __DIR__ . '/i18n.php';
    require_once __DIR__ . '/helpers.php';
    require_once __DIR__ . '/password.php';
    edoc_lang();
    if ($needDb) {
        require_once dirname(__DIR__) . '/connection.php';
        require_once dirname(__DIR__) . '/csrf.php';
    }
}
