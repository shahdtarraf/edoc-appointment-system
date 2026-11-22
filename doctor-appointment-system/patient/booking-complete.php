<?php

    //learn from w3schools.com

    session_start();
    include __DIR__ . '/../csrf.php';

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
            exit;
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
        exit;
    }
    

    //import database
    include("../connection.php");
    if (!isset($database) || !($database instanceof mysqli) || $database->connect_error) {
        header("location: ../login.php");
        exit;
    }

    $sqlmain= "select * from patient where pemail=?";
    $stmt = $database->prepare($sqlmain);
    if ($stmt === false) {
        error_log('Booking patient lookup prepare failed');
        header("location: schedule.php");
        exit;
    }
    $stmt->bind_param("s",$useremail);
    if (!$stmt->execute()) {
        error_log('Booking patient lookup failed');
        $stmt->close();
        header("location: schedule.php");
        exit;
    }
    $userrow = $stmt->get_result();
    $stmt->close();
    if ($userrow === false || $userrow->num_rows !== 1) {
        header("location: ../login.php");
        exit;
    }
    $userfetch=$userrow->fetch_assoc();
    $userid= (int) $userfetch["pid"];
    $username=$userfetch["pname"];


    if($_POST){
        if(isset($_POST["booknow"])){
            if (!edoc_csrf_verify()) {
                header("location: schedule.php");
                exit;
            }

            $scheduleid = filter_var(isset($_POST["scheduleid"]) ? $_POST["scheduleid"] : 0, FILTER_VALIDATE_INT);

            if ($userid < 1 || $scheduleid === false || $scheduleid < 1) {
                header("location: schedule.php");
                exit;
            }

            $scheduleStmt = $database->prepare('SELECT schedule.scheduleid, schedule.nop FROM schedule INNER JOIN doctor ON schedule.docid = doctor.docid WHERE schedule.scheduleid = ? LIMIT 1');
            if ($scheduleStmt === false) {
                error_log('Booking schedule lookup prepare failed');
                header("location: schedule.php");
                exit;
            }
            $scheduleStmt->bind_param('i', $scheduleid);
            if (!$scheduleStmt->execute()) {
                error_log('Booking schedule lookup failed');
                $scheduleStmt->close();
                header("location: schedule.php");
                exit;
            }
            $scheduleResult = $scheduleStmt->get_result();
            $scheduleStmt->close();
            if ($scheduleResult === false || $scheduleResult->num_rows !== 1) {
                header("location: schedule.php");
                exit;
            }
            $scheduleRow = $scheduleResult->fetch_assoc();
            $nop = (int) $scheduleRow['nop'];

            // Capacity uses COUNT(*) (occupied rows for this schedule).
            // Next ticket uses MAX(apponum): deletes leave gaps, so COUNT+1 can collide
            // with an existing number. Logical case: rows 1,2,4 → COUNT=3, MAX=4, next=5
            // (COUNT+1 would wrongly reuse 4). Empty session: MAX NULL → 1.
            // MyISAM: this SELECT then INSERT is not atomic; concurrent bookings can still
            // share an apponum or exceed nop. Not claimed solved.
            $countStmt = $database->prepare('SELECT COUNT(*) AS booked, COALESCE(MAX(apponum), 0) AS max_apponum FROM appointment WHERE scheduleid = ?');
            if ($countStmt === false) {
                error_log('Booking count prepare failed');
                header("location: schedule.php");
                exit;
            }
            $countStmt->bind_param('i', $scheduleid);
            if (!$countStmt->execute()) {
                error_log('Booking count failed');
                $countStmt->close();
                header("location: schedule.php");
                exit;
            }
            $countResult = $countStmt->get_result();
            $countStmt->close();
            if ($countResult === false) {
                header("location: schedule.php");
                exit;
            }
            $countRow = $countResult->fetch_assoc();
            $booked = isset($countRow['booked']) ? (int) $countRow['booked'] : 0;
            $maxApponum = isset($countRow['max_apponum']) ? (int) $countRow['max_apponum'] : 0;

            if ($nop < 1 || $booked >= $nop) {
                header("location: schedule.php");
                exit;
            }

            $apponum = $maxApponum + 1;

            $appodate = date('Y-m-d');
            $insertStmt = $database->prepare('INSERT INTO appointment (pid, apponum, scheduleid, appodate) VALUES (?, ?, ?, ?)');
            if ($insertStmt === false) {
                error_log('Booking insert prepare failed');
                header("location: schedule.php");
                exit;
            }
            $insertStmt->bind_param('iiis', $userid, $apponum, $scheduleid, $appodate);
            if (!$insertStmt->execute()) {
                error_log('Booking insert failed');
                $insertStmt->close();
                header("location: schedule.php");
                exit;
            }
            $insertStmt->close();
            header("location: appointment.php?action=booking-added&id=".$apponum."&titleget=none");
            exit;
        }
    }
 ?>
