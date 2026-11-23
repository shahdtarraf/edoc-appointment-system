<?php
require __DIR__ . '/_init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id00']) ? (int) $_POST['id00'] : 0);
$error = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = edoc_t('status_err');
    } else {
        $id = (int) $_POST['id00'];
        $name = trim((string) $_POST['name']);
        $email = trim((string) $_POST['email']);
        $oldemail = trim((string) $_POST['oldemail']);
        $tele = trim((string) $_POST['Tele']);
        $spec = (int) $_POST['spec'];
        $password = (string) $_POST['password'];
        $cpassword = (string) $_POST['cpassword'];
        if ($id < 1 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                        $up = $database->prepare('UPDATE doctor SET docemail=?, docname=?, docpassword=?, doctel=?, specialties=? WHERE docid=?');
                        $up->bind_param('ssssii', $email, $name, $stored, $tele, $spec, $id);
                    }
                } else {
                    $up = $database->prepare('UPDATE doctor SET docemail=?, docname=?, doctel=?, specialties=? WHERE docid=?');
                    $up->bind_param('sssii', $email, $name, $tele, $spec, $id);
                }
                if ($error === '' && $up && $up->execute()) {
                    $wu = $database->prepare('UPDATE webuser SET email=? WHERE email=? AND usertype=\'d\'');
                    $wu->bind_param('ss', $email, $oldemail);
                    $wu->execute();
                    $wu->close();
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
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doc) {
    header('location: doctors.php');
    exit;
}

$specs = $database->query('SELECT id, sname FROM specialties ORDER BY id');
edoc_app_start(edoc_t('edit_doctor'), 'a', $username, $useremail, 'doctors.php');
echo '<p class="edoc-muted">' . edoc_h(edoc_t('synthetic')) . ' · ' . edoc_h(edoc_t('no_license')) . '</p>';
if ($ok) {
    edoc_alert('ok', edoc_t('status_ok'));
}
if ($error !== '') {
    edoc_alert('error', $error);
}
echo '<div class="edoc-card"><form class="edoc-form" method="post">';
echo edoc_csrf_field();
echo '<input type="hidden" name="id00" value="' . (int) $doc['docid'] . '">';
echo '<input type="hidden" name="oldemail" value="' . edoc_h($doc['docemail']) . '">';
echo '<label class="edoc-label">' . edoc_h(edoc_t('doctor_name')) . '<input class="edoc-field" name="name" required value="' . edoc_h($doc['docname']) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('email')) . '<input class="edoc-field" type="email" name="email" required value="' . edoc_h($doc['docemail']) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('phone')) . '<input class="edoc-field" name="Tele" value="' . edoc_h($doc['doctel']) . '"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('specialty')) . '<select class="edoc-field" name="spec">';
while ($row = $specs->fetch_assoc()) {
    $sel = ((int) $row['id'] === (int) $doc['specialties']) ? ' selected' : '';
    echo '<option value="' . (int) $row['id'] . '"' . $sel . '>' . edoc_h($row['sname']) . '</option>';
}
echo '</select></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('password')) . '<input class="edoc-field" type="password" name="password" autocomplete="new-password"></label>';
echo '<label class="edoc-label">' . edoc_h(edoc_t('confirm_password')) . '<input class="edoc-field" type="password" name="cpassword"></label>';
echo '<div class="edoc-form-actions"><button class="edoc-btn edoc-btn-primary" type="submit">' . edoc_h(edoc_t('save')) . '</button>';
echo '<a class="edoc-btn edoc-btn-soft" href="doctors.php">' . edoc_h(edoc_t('prev')) . '</a></div></form></div>';
edoc_app_end();
