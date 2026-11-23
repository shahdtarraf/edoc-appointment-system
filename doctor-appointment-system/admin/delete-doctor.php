<?php
require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !edoc_csrf_verify()) {
    header('location: doctors.php');
    exit;
}
$id = filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('location: doctors.php');
    exit;
}
$stmt = $database->prepare('SELECT docemail FROM doctor WHERE docid = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row) {
    $email = $row['docemail'];
    $del = $database->prepare('DELETE FROM doctor WHERE docid = ?');
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
    $wu = $database->prepare('DELETE FROM webuser WHERE email = ? AND usertype = \'d\'');
    $wu->bind_param('s', $email);
    $wu->execute();
    $wu->close();
}
header('location: doctors.php');
exit;
