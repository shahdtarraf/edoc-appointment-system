<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (!@session_start()) {
        error_log('Unable to start session');
        http_response_code(500);
        exit('Unable to start session.');
    }
}

$savedLang = isset($_SESSION['edoc_lang']) ? $_SESSION['edoc_lang'] : null;
$_SESSION['user'] = '';
$_SESSION['usertype'] = '';
if (is_string($savedLang) && ($savedLang === 'ar' || $savedLang === 'en')) {
    $_SESSION['edoc_lang'] = $savedLang;
}

date_default_timezone_set('Asia/Damascus');
$_SESSION['date'] = date('Y-m-d');

require_once __DIR__ . '/includes/layout.php';
edoc_bootstrap(true);

function edoc_login_success($email, $usertype, $location)
{
    if (!session_regenerate_id(true)) {
        error_log('session_regenerate_id failed');
        return false;
    }
    $_SESSION['user'] = $email;
    $_SESSION['usertype'] = $usertype;
    header('location: ' . $location);
    exit;
}

function edoc_login_check($database, $role, $email, $password)
{
    $map = array(
        'p' => array('patient', 'ppassword', 'pemail'),
        'a' => array('admin', 'apassword', 'aemail'),
        'd' => array('doctor', 'docpassword', 'docemail'),
    );
    if (!isset($map[$role])) {
        return false;
    }
    list($table, $column, $emailColumn) = $map[$role];
    $sql = 'SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $emailColumn . '` = ? LIMIT 1';
    $stmt = $database->prepare($sql);
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('s', $email);
    $ok = false;
    $stored = '';
    if ($stmt->execute()) {
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && isset($row[$column])) {
            $stored = (string) $row[$column];
            $ok = edoc_password_ok($password, $stored);
        }
    }
    $stmt->close();
    if ($ok === true) {
        edoc_password_upgrade_after_success($database, $password, $stored, $table, $column, $emailColumn, $email);
    }
    return $ok;
}

$loginFailed = false;
if ($_POST) {
    $email = isset($_POST['useremail']) ? trim((string) $_POST['useremail']) : '';
    $password = isset($_POST['userpassword']) ? (string) $_POST['userpassword'] : '';
    $genericFail = true;
    if (!edoc_csrf_verify()) {
        $loginFailed = true;
    } elseif ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $loginFailed = true;
    } elseif (!isset($database) || !($database instanceof mysqli) || $database->connect_error) {
        error_log('Login aborted: database unavailable');
        $loginFailed = true;
    } else {
        $stmt = $database->prepare('SELECT email, usertype FROM webuser WHERE email = ? LIMIT 1');
        if ($stmt === false) {
            error_log('Login prepare failed');
            $loginFailed = true;
        } else {
            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                error_log('Login execute failed');
                $loginFailed = true;
                $stmt->close();
            } else {
                $result = $stmt->get_result();
                $stmt->close();
                if ($result === false || $result->num_rows !== 1) {
                    $loginFailed = true;
                } else {
                    $utype = $result->fetch_assoc()['usertype'];
                    if ($utype == 'p' && edoc_login_check($database, 'p', $email, $password)) {
                        edoc_login_success($email, 'p', 'patient/index.php');
                    } elseif ($utype == 'a' && edoc_login_check($database, 'a', $email, $password)) {
                        edoc_login_success($email, 'a', 'admin/index.php');
                    } elseif ($utype == 'd' && edoc_login_check($database, 'd', $email, $password)) {
                        edoc_login_success($email, 'd', 'doctor/index.php');
                    } else {
                        $loginFailed = true;
                    }
                }
            }
        }
    }
    if ($genericFail && $loginFailed) {
        // generic only
    }
}

edoc_head(edoc_t('login_title'));
edoc_public_nav();
echo '<div class="edoc-auth"><div class="edoc-auth-card">';
echo '<a class="edoc-brand" href="index.php">' . edoc_h(edoc_t('brand')) . '</a>';
echo '<h1>' . edoc_h(edoc_t('login_title')) . '</h1>';
echo '<p class="edoc-muted">' . edoc_h(edoc_t('login_sub')) . '</p>';
if ($loginFailed) {
    edoc_alert('error', edoc_t('wrong_credentials'));
}
echo '<form method="post" action="login.php" style="display:grid;gap:12px;margin-top:16px">';
echo edoc_csrf_field();
echo '<label class="edoc-label">' . edoc_h(edoc_t('email')) . '<input class="edoc-field" type="email" name="useremail" required maxlength="255" autocomplete="username"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('password')) . '<input class="edoc-field" type="password" name="userpassword" required autocomplete="current-password"></label>';
echo '<button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('login_btn')) . '</button>';
echo '</form>';
echo '<p>' . edoc_h(edoc_t('no_account')) . ' <a href="signup.php">' . edoc_h(edoc_t('nav_signup')) . '</a></p>';
echo '<p><a href="' . edoc_h(edoc_lang_toggle_url()) . '">' . edoc_h(edoc_t('lang_switch')) . '</a> · <a href="index.php">' . edoc_h(edoc_t('nav_home')) . '</a></p>';
echo '</div></div>';
edoc_page_end();
