<?php
require_once dirname(__DIR__) . '/includes/layout.php';
edoc_bootstrap(true);
edoc_require_role('p');

$useremail = $_SESSION['user'];
$stmt = $database->prepare('SELECT * FROM patient WHERE pemail = ? LIMIT 1');
$stmt->bind_param('s', $useremail);
$stmt->execute();
$userfetch = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$userfetch) {
    header('location: ../login.php');
    exit;
}
$userid = (int) $userfetch['pid'];
$username = $userfetch['pname'];
