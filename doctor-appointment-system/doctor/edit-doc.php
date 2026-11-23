<?php
require __DIR__ . '/_init.php';

$error = '';
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = edoc_t('status_err');
    } else {
        $name = trim((string) $_POST['name']);
        $email = trim((string) $_POST['email']);
        $oldemail = $useremail;
        $tele = trim((string) $_POST['Tele']);
        $spec = (int) $userfetch['specialties'];
        $password = (string) $_POST['password'];
        $cpassword = (string) $_POST['cpassword'];
        $id = $userid;
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = edoc_t('invalid_input');
        } elseif ($password !== $cpassword) {
            $error = edoc_t('password_mismatch');
        } else {
            $chk = $database->prepare('SELECT docid FROM doctor WHERE docemail = ? LIMIT 1');
            $chk->bind_param('s', $email);
            $chk->execute();
            $found = $chk->get_result()->fetch_assoc();
            $chk->close();
            if ($found && (int) $found['docid'] !== $id) {
                $error = edoc_t('email_taken');
            } else {
                if ($password !== '') {
                    $stored = edoc_store_password($password);
                    if ($stored === false) {
                        $error = edoc_t('status_err');
                        $up = null;
                    } else {
                        $up = $database->prepare('UPDATE doctor SET docemail=?, docname=?, docpassword=?, doctel=? WHERE docid=?');
                        $up->bind_param('ssssi', $email, $name, $stored, $tele, $id);
                    }
                } else {
                    $up = $database->prepare('UPDATE doctor SET docemail=?, docname=?, doctel=? WHERE docid=?');
                    $up->bind_param('sssi', $email, $name, $tele, $id);
                }
                if ($error === '' && $up && $up->execute()) {
                    $wu = $database->prepare('UPDATE webuser SET email=? WHERE email=? AND usertype=\'d\'');
                    $wu->bind_param('ss', $email, $oldemail);
                    $wu->execute();
                    $wu->close();
                    $_SESSION['user'] = $email;
                    $useremail = $email;
                    $ok = true;
                } else {
                    $error = edoc_t('status_err');
                }
                if ($up) {
                    $up->close();
                }
            }
        }
    }
}

$stmt = $database->prepare('SELECT * FROM doctor WHERE docid = ? LIMIT 1');
$stmt->bind_param('i', $userid);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

edoc_app_start(edoc_t('profile'), 'd', $doc['docname'], $doc['docemail'], 'settings.php');
echo edoc_demo_badge();
echo '<p class="edoc-muted">' . edoc_h(edoc_t('no_license')) . '</p>';
if ($ok) {
    edoc_alert('ok', edoc_t('status_ok'));
}
if ($error !== '') {
    edoc_alert('error', $error);
}
echo '<div class="edoc-card"><form class="edoc-form" method="post">';
echo edoc_csrf_field();
echo '<label class="edoc-label">' . edoc_h(edoc_t('doctor_name')) . '<input class="edoc-field" name="name" required value="' . edoc_h($doc['docname']) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('email')) . '<input class="edoc-field" type="email" name="email" required value="' . edoc_h($doc['docemail']) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('phone')) . '<input class="edoc-field" name="Tele" value="' . edoc_h($doc['doctel']) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('password')) . '<input class="edoc-field" type="password" name="password" autocomplete="new-password"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('confirm_password')) . '<input class="edoc-field" type="password" name="cpassword"></label>';
echo '<div class="edoc-form-actions"><button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('save')) . '</button></div></form></div>';
edoc_app_end();
