<?php

function edoc_lang()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $allowed = array('ar', 'en');
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $_SESSION['edoc_lang'] = $_GET['lang'];
        setcookie('edoc_lang', $_GET['lang'], time() + 31536000, '/', '', false, true);
    }
    if (!empty($_SESSION['edoc_lang']) && in_array($_SESSION['edoc_lang'], $allowed, true)) {
        return $_SESSION['edoc_lang'];
    }
    if (!empty($_COOKIE['edoc_lang']) && in_array($_COOKIE['edoc_lang'], $allowed, true)) {
        return $_COOKIE['edoc_lang'];
    }
    return 'ar';
}

function edoc_dir()
{
    return edoc_lang() === 'ar' ? 'rtl' : 'ltr';
}

function edoc_t($key)
{
    static $catalog = null;
    $lang = edoc_lang();
    if ($catalog === null) {
        $path = dirname(__DIR__) . '/lang/' . $lang . '.php';
        $catalog = is_readable($path) ? include $path : array();
        if (!is_array($catalog)) {
            $catalog = array();
        }
    }
    if (isset($catalog[$key])) {
        return $catalog[$key];
    }
    return $key;
}

function edoc_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function edoc_lang_toggle_url()
{
    $next = edoc_lang() === 'ar' ? 'en' : 'ar';
    $params = $_GET;
    $params['lang'] = $next;
    $path = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
    return $path . '?' . http_build_query($params);
}
