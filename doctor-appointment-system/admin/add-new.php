<?php
require __DIR__ . '/_init.php';

$error = '';
$ok = false;
$name = '';
$email = '';
$tele = '000-DEMO-NEW';
$spec = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = edoc_t('status_err');
    } else {
        $name = trim((string) $_POST['name']);
        $email = trim((string) $_POST['email']);
        $tele = trim((string) $_POST['Tele']);
        $spec = (int) $_POST['spec'];
        $password = (string) $_POST['password'];
        $cpassword = (string) $_POST['cpassword'];
        $nic = 'NOT-A-LICENSE';
        if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $spec < 1) {
            $error = edoc_t('invalid_input');
        } elseif ($password !== $cpassword) {
            $error = edoc_t('password_mismatch');
        } else {
            $chk = $database->prepare('SELECT email FROM webuser WHERE email = ? LIMIT 1');
            $chk->bind_param('s', $email);
            $chk->execute();
            if ($chk->get_result()->num_rows === 1) {
                $error = edoc_t('email_taken');
            } else {
                $stored = edoc_store_password($password);
                $ins = null;
                if ($stored === false) {
                    $error = edoc_t('status_err');
                } elseif (edoc_has_column($database, 'doctor', 'is_demo')) {
                    $ins = $database->prepare(
                        'INSERT INTO doctor (docemail, docname, docpassword, docnic, doctel, specialties, is_demo, record_source, verification_status)
                         VALUES (?,?,?,?,?,?,1,?,?)'
                    );
                    $src = 'demo_seed_v1';
                    $ver = 'synthetic_unverified';
                    $ins->bind_param('sssssiss', $email, $name, $stored, $nic, $tele, $spec, $src, $ver);
                } else {
                    $ins = $database->prepare(
                        'INSERT INTO doctor (docemail, docname, docpassword, docnic, doctel, specialties) VALUES (?,?,?,?,?,?)'
                    );
                    $ins->bind_param('sssssi', $email, $name, $stored, $nic, $tele, $spec);
                }
                $okIns = $ins && $ins->execute();
                if ($ins) {
                    $ins->close();
                }
                if ($okIns) {
                    if (edoc_has_column($database, 'webuser', 'is_demo')) {
                        $w = $database->prepare('INSERT INTO webuser (email, usertype, is_demo, record_source) VALUES (?,\'d\',1,\'demo_seed_v1\')');
                        $w->bind_param('s', $email);
                    } else {
                        $w = $database->prepare('INSERT INTO webuser (email, usertype) VALUES (?,\'d\')');
                        $w->bind_param('s', $email);
                    }
                    $w->execute();
                    $w->close();
                    $ok = true;
                } else {
                    $error = edoc_t('status_err');
                }
            }
            $chk->close();
        }
    }
}

$specs = $database->query('SELECT id, sname FROM specialties ORDER BY id');
edoc_app_start(edoc_t('add_doctor'), 'a', $username, $useremail, 'doctors.php');
if ($ok) {
    edoc_alert('ok', edoc_t('status_ok'));
}
if ($error !== '') {
    edoc_alert('error', $error);
}
echo '<div class="edoc-card"><form class="edoc-form" method="post">';
echo edoc_csrf_field();
echo '<p class="edoc-muted">' . edoc_h(edoc_t('synthetic')) . ' · ' . edoc_h(edoc_t('no_license')) . '</p>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('doctor_name')) . '<input class="edoc-field" name="name" required maxlength="120" value="' . edoc_h($name) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('email')) . '<input class="edoc-field" type="email" name="email" required maxlength="255" value="' . edoc_h($email) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('phone')) . '<input class="edoc-field" name="Tele" maxlength="32" value="' . edoc_h($tele) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('specialty')) . '<select class="edoc-field" name="spec">';
while ($row = $specs->fetch_assoc()) {
    $sel = ((int) $row['id'] === $spec) ? ' selected' : '';
    echo '<option value="' . (int) $row['id'] . '"' . $sel . '>' . edoc_h($row['sname']) . '</option>';
}
echo '</select></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('password')) . '<input class="edoc-field" type="password" name="password" required autocomplete="new-password"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('confirm_password')) . '<input class="edoc-field" type="password" name="cpassword" required></label>';
echo '<div class="edoc-form-actions"><button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('save')) . '</button>';
echo '<a class="edoc-btn edoc-btn-soft" href="doctors.php">' . edoc_h(edoc_t('prev')) . '</a></div>';
echo '</form></div>';
edoc_app_end();
