<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (!@session_start()) {
        error_log('Unable to start session');
        http_response_code(500);
        exit('Unable to start session.');
    }
}

$_SESSION["user"] = "";
$_SESSION["usertype"] = "";

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d');
$_SESSION["date"] = $date;

include("connection.php");

function edoc_login_success($email, $usertype, $location)
{
    if (!session_regenerate_id(true)) {
        error_log('session_regenerate_id failed');
        return false;
    }
    $_SESSION['user'] = $email;
    $_SESSION['usertype'] = $usertype;
    header('location: ' . $location);
    exit;
}

if ($_POST) {
    $email = isset($_POST['useremail']) ? trim((string) $_POST['useremail']) : '';
    $password = isset($_POST['userpassword']) ? (string) $_POST['userpassword'] : '';

    $error = '<label for="promter" class="form-label"></label>';
    $genericError = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Wrong credentials: Invalid email or password</label>';

    if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = $genericError;
    } elseif (!isset($database) || !($database instanceof mysqli) || $database->connect_error) {
        error_log('Login aborted: database unavailable');
        $error = $genericError;
    } else {
        $stmt = $database->prepare('SELECT email, usertype FROM webuser WHERE email = ? LIMIT 1');
        if ($stmt === false) {
            error_log('Login prepare failed');
            $error = $genericError;
        } else {
            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                error_log('Login execute failed');
                $error = $genericError;
                $stmt->close();
            } else {
                $result = $stmt->get_result();
                $stmt->close();
                if ($result === false || $result->num_rows !== 1) {
                    $error = $genericError;
                } else {
                    $utype = $result->fetch_assoc()['usertype'];

                    if ($utype == 'p') {
                        $checkerStmt = $database->prepare('SELECT pemail FROM patient WHERE pemail = ? AND ppassword = ? LIMIT 1');
                        if ($checkerStmt === false) {
                            error_log('Login patient prepare failed');
                            $error = $genericError;
                        } else {
                            $checkerStmt->bind_param('ss', $email, $password);
                            $loginOk = false;
                            if ($checkerStmt->execute()) {
                                $checker = $checkerStmt->get_result();
                                $loginOk = ($checker && $checker->num_rows == 1);
                            }
                            $checkerStmt->close();
                            if ($loginOk && edoc_login_success($email, 'p', 'patient/index.php')) {
                                // redirected
                            }
                            $error = $genericError;
                        }
                    } elseif ($utype == 'a') {
                        $checkerStmt = $database->prepare('SELECT aemail FROM admin WHERE aemail = ? AND apassword = ? LIMIT 1');
                        if ($checkerStmt === false) {
                            error_log('Login admin prepare failed');
                            $error = $genericError;
                        } else {
                            $checkerStmt->bind_param('ss', $email, $password);
                            $loginOk = false;
                            if ($checkerStmt->execute()) {
                                $checker = $checkerStmt->get_result();
                                $loginOk = ($checker && $checker->num_rows == 1);
                            }
                            $checkerStmt->close();
                            if ($loginOk && edoc_login_success($email, 'a', 'admin/index.php')) {
                                // redirected
                            }
                            $error = $genericError;
                        }
                    } elseif ($utype == 'd') {
                        $checkerStmt = $database->prepare('SELECT docemail FROM doctor WHERE docemail = ? AND docpassword = ? LIMIT 1');
                        if ($checkerStmt === false) {
                            error_log('Login doctor prepare failed');
                            $error = $genericError;
                        } else {
                            $checkerStmt->bind_param('ss', $email, $password);
                            $loginOk = false;
                            if ($checkerStmt->execute()) {
                                $checker = $checkerStmt->get_result();
                                $loginOk = ($checker && $checker->num_rows == 1);
                            }
                            $checkerStmt->close();
                            if ($loginOk && edoc_login_success($email, 'd', 'doctor/index.php')) {
                                // redirected
                            }
                            $error = $genericError;
                        }
                    } else {
                        $error = $genericError;
                    }
                }
            }
        }
    }
} else {
    $error = '<label for="promter" class="form-label">&nbsp;</label>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/login.css">
        
    <title>Login</title>

    
    
</head>
<body>
    <center>
    <div class="container">
        <table border="0" style="margin: 0;padding: 0;width: 60%;">
            <tr>
                <td>
                    <p class="header-text">Welcome Back!</p>
                </td>
            </tr>
        <div class="form-body">
            <tr>
                <td>
                    <p class="sub-text">Login with your details to continue</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST" >
                <td class="label-td">
                    <label for="useremail" class="form-label">Email: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="email" name="useremail" class="input-text" placeholder="Email Address" required>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <label for="userpassword" class="form-label">Password: </label>
                </td>
            </tr>

            <tr>
                <td class="label-td">
                    <input type="Password" name="userpassword" class="input-text" placeholder="Password" required>
                </td>
            </tr>


            <tr>
                <td><br>
                <?php echo $error ?>
                </td>
            </tr>

            <tr>
                <td>
                    <input type="submit" value="Login" class="login-btn btn-primary btn">
                </td>
            </tr>
        </div>
            <tr>
                <td>
                    <br>
                    <label for="" class="sub-text" style="font-weight: 280;">Don't have an account&#63; </label>
                    <a href="signup.php" class="hover-link1 non-style-link">Sign Up</a>
                    <br><br><br>
                </td>
            </tr>
                        
                        
    
                        
                    </form>
        </table>

    </div>
</center>
</body>
</html>
