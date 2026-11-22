<?php

    session_start();
    include __DIR__ . '/../csrf.php';

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='d'){
            header("location: ../login.php");
            exit;
        }else{
            $useremail = $_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("location: appointment.php");
        exit;
    }

    if (!edoc_csrf_verify()) {
        header("location: appointment.php");
        exit;
    }

    include("../connection.php");
    if (!isset($database) || !($database instanceof mysqli) || $database->connect_error) {
        header("location: appointment.php");
        exit;
    }

    $id = filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        header("location: appointment.php");
        exit;
    }

    $doctorStmt = $database->prepare('SELECT docid FROM doctor WHERE docemail = ? LIMIT 1');
    if ($doctorStmt === false) {
        error_log('Doctor cancel lookup prepare failed');
        header("location: appointment.php");
        exit;
    }
    $doctorStmt->bind_param('s', $useremail);
    if (!$doctorStmt->execute()) {
        error_log('Doctor cancel lookup failed');
        $doctorStmt->close();
        header("location: appointment.php");
        exit;
    }
    $doctorResult = $doctorStmt->get_result();
    $doctorStmt->close();
    if ($doctorResult === false || $doctorResult->num_rows !== 1) {
        header("location: ../login.php");
        exit;
    }
    $docid = (string) $doctorResult->fetch_assoc()['docid'];

    $deleteStmt = $database->prepare(
        'DELETE appointment FROM appointment INNER JOIN schedule ON appointment.scheduleid = schedule.scheduleid WHERE appointment.appoid = ? AND schedule.docid = ?'
    );
    if ($deleteStmt === false) {
        error_log('Doctor cancel delete prepare failed');
        header("location: appointment.php");
        exit;
    }
    $deleteStmt->bind_param('is', $id, $docid);
    if (!$deleteStmt->execute()) {
        error_log('Doctor cancel delete failed');
    }
    $deleteStmt->close();
    header("location: appointment.php");
    exit;
