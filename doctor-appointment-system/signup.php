<?php
require_once __DIR__ . '/includes/layout.php';
edoc_bootstrap(false);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$savedLang = isset($_SESSION['edoc_lang']) ? $_SESSION['edoc_lang'] : null;
$_SESSION['user'] = '';
$_SESSION['usertype'] = '';
if ($savedLang === 'ar' || $savedLang === 'en') {
    $_SESSION['edoc_lang'] = $savedLang;
}
require_once __DIR__ . '/csrf.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = edoc_t('status_err');
    } else {
        $fname = trim((string) $_POST['fname']);
        $lname = trim((string) $_POST['lname']);
        $address = trim((string) $_POST['address']);
        $nic = 'NOT-A-NATIONAL-ID';
        $dob = (string) $_POST['dob'];
        if ($fname === '' || $lname === '' || strlen($fname) > 60 || strlen($lname) > 60) {
            $error = edoc_t('invalid_input');
        } else {
            $_SESSION['personal'] = array(
                'fname' => $fname,
                'lname' => $lname,
                'address' => $address,
                'nic' => $nic,
                'dob' => $dob,
            );
            header('location: create-account.php');
            exit;
        }
    }
}

edoc_head(edoc_t('signup_title'));
edoc_public_nav();
echo '<div class="edoc-auth"><div class="edoc-auth-card">';
echo '<a class="edoc-brand" href="index.php">' . edoc_h(edoc_t('brand')) . '</a>';
echo '<h1>' . edoc_h(edoc_t('signup_title')) . '</h1>';
if ($error !== '') {
    edoc_alert('error', $error);
}
echo '<form class="edoc-form" method="post">';
echo edoc_csrf_field();
echo '<label class="edoc-label">' . edoc_h(edoc_t('first_name')) . '<input class="edoc-field" name="fname" required maxlength="60"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('last_name')) . '<input class="edoc-field" name="lname" required maxlength="60"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('address')) . '<input class="edoc-field" name="address" maxlength="120" value="' . edoc_h(edoc_t('demo_clinic_area')) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('dob')) . '<input class="edoc-field" type="date" name="dob" required></label>';
echo '<div class="edoc-form-actions"><button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('next_step')) . '</button></div>';
echo '</form>';
echo '<p>' . edoc_h(edoc_t('already_account')) . ' <a href="login.php">' . edoc_h(edoc_t('nav_login')) . '</a></p>';
echo '<p><a href="' . edoc_h(edoc_lang_toggle_url()) . '">' . edoc_h(edoc_t('lang_switch')) . '</a></p>';
echo '</div></div>';
edoc_page_end();
