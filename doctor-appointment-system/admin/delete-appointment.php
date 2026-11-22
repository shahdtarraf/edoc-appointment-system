<?php

    session_start();
    include __DIR__ . '/../csrf.php';

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
            exit;
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

    $lookupStmt = $database->prepare('SELECT appoid FROM appointment WHERE appoid = ? LIMIT 1');
    if ($lookupStmt === false) {
        error_log('Admin cancel lookup prepare failed');
        header("location: appointment.php");
        exit;
    }
    $lookupStmt->bind_param('i', $id);
    if (!$lookupStmt->execute()) {
        error_log('Admin cancel lookup failed');
        $lookupStmt->close();
        header("location: appointment.php");
        exit;
    }
    $lookupResult = $lookupStmt->get_result();
    $lookupStmt->close();
    if ($lookupResult === false || $lookupResult->num_rows !== 1) {
        header("location: appointment.php");
        exit;
    }

    $deleteStmt = $database->prepare('DELETE FROM appointment WHERE appoid = ?');
    if ($deleteStmt === false) {
        error_log('Admin cancel delete prepare failed');
        header("location: appointment.php");
        exit;
    }
    $deleteStmt->bind_param('i', $id);
    if (!$deleteStmt->execute()) {
        error_log('Admin cancel delete failed');
    }
    $deleteStmt->close();
    header("location: appointment.php");
    exit;
