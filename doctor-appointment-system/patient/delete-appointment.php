<?php

    session_start();
    include __DIR__ . '/../csrf.php';

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
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

    $patientStmt = $database->prepare('SELECT pid FROM patient WHERE pemail = ? LIMIT 1');
    if ($patientStmt === false) {
        error_log('Patient cancel lookup prepare failed');
        header("location: appointment.php");
        exit;
    }
    $patientStmt->bind_param('s', $useremail);
    if (!$patientStmt->execute()) {
        error_log('Patient cancel lookup failed');
        $patientStmt->close();
        header("location: appointment.php");
        exit;
    }
    $patientResult = $patientStmt->get_result();
    $patientStmt->close();
    if ($patientResult === false || $patientResult->num_rows !== 1) {
        header("location: ../login.php");
        exit;
    }
    $pid = (int) $patientResult->fetch_assoc()['pid'];

    $deleteStmt = $database->prepare('DELETE FROM appointment WHERE appoid = ? AND pid = ?');
    if ($deleteStmt === false) {
        error_log('Patient cancel delete prepare failed');
        header("location: appointment.php");
        exit;
    }
    $deleteStmt->bind_param('ii', $id, $pid);
    if (!$deleteStmt->execute()) {
        error_log('Patient cancel delete failed');
    }
    $deleteStmt->close();
    header("location: appointment.php");
    exit;
