<?php
require_once __DIR__ . '/includes/layout.php';
edoc_bootstrap(true);

if (empty($_SESSION['personal']) || !is_array($_SESSION['personal'])) {
    header('location: signup.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = edoc_t('status_err');
    } else {
        $fname = (string) $_SESSION['personal']['fname'];
        $lname = (string) $_SESSION['personal']['lname'];
        $name = trim($fname . ' ' . $lname);
        $address = (string) $_SESSION['personal']['address'];
        $nic = 'NOT-A-NATIONAL-ID';
        $dob = (string) $_SESSION['personal']['dob'];
        $email = trim((string) $_POST['newemail']);
        $tele = trim((string) $_POST['tele']);
        $newpassword = (string) $_POST['newpassword'];
        $cpassword = (string) $_POST['cpassword'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $error = edoc_t('invalid_input');
        } elseif ($newpassword !== $cpassword || $newpassword === '') {
            $error = edoc_t('password_mismatch');
        } else {
            $stmt = $database->prepare('SELECT email FROM webuser WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 1) {
                $error = edoc_t('email_taken');
            } else {
                $storedPassword = edoc_store_password($newpassword);
                $ins = null;
                if ($storedPassword === false) {
                    $error = edoc_t('status_err');
                } elseif (edoc_has_column($database, 'patient', 'is_demo')) {
                    $ins = $database->prepare(
                        'INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel, is_demo, record_source, verification_status)
                         VALUES (?,?,?,?,?,?,?,1,?,?)'
                    );
                    $src = 'demo_seed_v1';
                    $ver = 'synthetic_unverified';
                    $ins->bind_param('sssssssss', $email, $name, $storedPassword, $address, $nic, $dob, $tele, $src, $ver);
                } else {
                    $ins = $database->prepare('INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel) VALUES (?,?,?,?,?,?,?)');
                    $ins->bind_param('sssssss', $email, $name, $storedPassword, $address, $nic, $dob, $tele);
                }
                if ($ins && $ins->execute()) {
                    if (edoc_has_column($database, 'webuser', 'is_demo')) {
                        $w = $database->prepare('INSERT INTO webuser (email, usertype, is_demo, record_source) VALUES (?,\'p\',1,\'demo_seed_v1\')');
                    } else {
                        $w = $database->prepare('INSERT INTO webuser (email, usertype) VALUES (?,\'p\')');
                    }
                    $w->bind_param('s', $email);
                    $w->execute();
                    $w->close();
                    session_regenerate_id(true);
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'p';
                    header('Location: patient/index.php');
                    exit;
                }
                $error = edoc_t('status_err');
            }
            $stmt->close();
        }
    }
}

edoc_head(edoc_t('signup_title'));
edoc_public_nav();
echo '<div class="edoc-auth"><div class="edoc-auth-card">';
echo '<h1>' . edoc_h(edoc_t('signup_title')) . '</h1>';
if ($error !== '') {
    edoc_alert('error', $error);
}
echo '<form class="edoc-form" method="post">';
echo edoc_csrf_field();
echo '<label class="edoc-label">' . edoc_h(edoc_t('email')) . '<input class="edoc-field" type="email" name="newemail" required maxlength="255"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('phone')) . '<input class="edoc-field" name="tele" maxlength="32" value="000-DEMO-NEW"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('password')) . '<input class="edoc-field" type="password" name="newpassword" required autocomplete="new-password"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('confirm_password')) . '<input class="edoc-field" type="password" name="cpassword" required></label>';
echo '<div class="edoc-form-actions"><button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('nav_signup')) . '</button></div>';
echo '</form></div></div>';
edoc_page_end();
