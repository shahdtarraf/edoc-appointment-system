<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Specialty helper</title>
    <style>
        .filter-container{animation: transitionIn-Y-bottom 0.5s;}
        .assist-box{width:92%;margin:20px auto;background:#fff;padding:20px;border-radius:8px;text-align:right;}
        .assist-note{color:#555;font-size:14px;line-height:1.6;}
        .rec-card{border:1px solid #d8d8d8;border-radius:8px;padding:12px 16px;margin:10px 0;background:#f9fbff;}
        textarea.input-text{width:100%;min-height:110px;padding:12px;box-sizing:border-box;}
    </style>
</head>
<body>
<?php
session_start();
include __DIR__ . '/../csrf.php';
include __DIR__ . '/../connection.php';
include __DIR__ . '/../config/supported_specialties.php';
include __DIR__ . '/../ai_recommend_client.php';

if (!isset($_SESSION['user']) || $_SESSION['user'] === '' || $_SESSION['usertype'] != 'p') {
    header('location: ../login.php');
    exit;
}

$useremail = $_SESSION['user'];
$stmt = $database->prepare('select * from patient where pemail=?');
$stmt->bind_param('s', $useremail);
$stmt->execute();
$userfetch = $stmt->get_result()->fetch_assoc();
$username = $userfetch['pname'];

$supported = edoc_supported_specialty_map();
$error = '';
$transportDown = false;
$resultPayload = null;
$submittedText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!edoc_csrf_verify()) {
        $error = 'تعذر التحقق من الطلب. أعد المحاولة.';
    } else {
        $submittedText = isset($_POST['symptom_text']) ? trim((string) $_POST['symptom_text']) : '';
        $call = edoc_ai_recommend($submittedText);
        if ($call['ok'] !== true) {
            $transportDown = true;
            $error = 'خدمة المساعدة غير متاحة حالياً. يمكنك اختيار التخصص يدوياً أو تصفح الأطباء.';
        } else {
            $resultPayload = $call['payload'];
        }
    }
}
?>
<div class="container">
    <div class="menu">
        <table class="menu-container" border="0">
            <tr>
                <td style="padding:10px" colspan="2">
                    <table border="0" class="profile-container">
                        <tr>
                            <td width="30%" style="padding-left:20px">
                                <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                            </td>
                            <td style="padding:0px;margin:0px;">
                                <p class="profile-title"><?php echo htmlspecialchars(substr($username, 0, 13), ENT_QUOTES, 'UTF-8'); ?>..</p>
                                <p class="profile-subtitle"><?php echo htmlspecialchars(substr($useremail, 0, 22), ENT_QUOTES, 'UTF-8'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="../logout.php"><input type="button" value="Log out" class="logout-btn btn-primary-soft btn"></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-home">
                    <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Home</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-doctor">
                    <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">All Doctors</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session menu-active menu-icon-session-active">
                    <a href="specialty-assist.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Specialty help</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session">
                    <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Scheduled Sessions</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">My Bookings</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-settings">
                    <a href="settings.php" class="non-style-link-menu"><div><p class="menu-text">Settings</p></div></a>
                </td>
            </tr>
        </table>
    </div>
    <div class="dash-body">
        <table border="0" width="100%" style="border-spacing:0;margin-top:25px;">
            <tr>
                <td width="13%">
                    <a href="doctors.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Back</font></button></a>
                </td>
                <td>
                    <p class="heading-main12" style="margin-left:45px;font-size:18px;color:rgb(49,49,49)">ساعدني في اختيار التخصص المناسب</p>
                </td>
            </tr>
        </table>

        <div class="assist-box">
            <p class="assist-note">
                هذه أداة تجريبية إرشادية فقط، وليست تشخيصاً أو علاجاً أو قراراً طبياً.
                التوصية اختيارية، ويمكنك تجاهلها واختيار التخصص أو الطبيب يدوياً في أي وقت.
                النموذج يغطي 30 تخصصاً مدعوماً فقط.
            </p>
            <form method="post" action="specialty-assist.php">
                <?php echo edoc_csrf_field(); ?>
                <label for="symptom_text">صف باختصار ما تريد مراجعته</label>
                <textarea id="symptom_text" name="symptom_text" class="input-text" maxlength="1500" placeholder="مثال: ألم شديد في الضرس مع تورم في اللثة"><?php echo htmlspecialchars($submittedText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <br><br>
                <input type="submit" value="احصل على التوصية" class="login-btn btn-primary btn">
                &nbsp;
                <a href="doctors.php" class="non-style-link"><button type="button" class="login-btn btn-primary-soft btn">اختيار يدوي / كل الأطباء</button></a>
                &nbsp;
                <a href="schedule.php" class="non-style-link"><button type="button" class="login-btn btn-primary-soft btn">الجلسات المتاحة</button></a>
            </form>

            <?php if ($error !== '') { ?>
                <p class="assist-note" style="color:#a33;margin-top:16px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>

            <?php
            if (is_array($resultPayload)) {
                $status = isset($resultPayload['status']) ? $resultPayload['status'] : '';
                $message = isset($resultPayload['message']) ? $resultPayload['message'] : '';
                echo '<p class="assist-note" style="margin-top:18px;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';

                if ($status === 'recommend' && !empty($resultPayload['recommendations']) && is_array($resultPayload['recommendations'])) {
                    $shown = 0;
                    foreach ($resultPayload['recommendations'] as $rec) {
                        if ($shown >= 3) {
                            break;
                        }
                        $sid = isset($rec['specialty_id']) ? (int) $rec['specialty_id'] : 0;
                        $sname = isset($rec['specialty_name']) ? (string) $rec['specialty_name'] : '';
                        if (!edoc_is_supported_specialty($sid)) {
                            continue;
                        }
                        if ($sname === '' && isset($supported[$sid])) {
                            $sname = $supported[$sid];
                        }
                        $docCount = edoc_doctor_count_by_specialty($database, $sid);
                        echo '<div class="rec-card">';
                        echo '<strong>' . htmlspecialchars($sname, ENT_QUOTES, 'UTF-8') . '</strong>';
                        echo '<p class="assist-note">اقتراح إرشادي تجريبي — ليست نسبة احتمال.</p>';
                        if ($docCount > 0) {
                            echo '<a class="non-style-link" href="doctors.php?spec=' . $sid . '"><button class="btn-primary-soft btn">عرض الأطباء المتاحين (' . $docCount . ')</button></a>';
                        } else {
                            echo '<p class="assist-note">لا يوجد أطباء مسجّلون حالياً لهذا التخصص. اختر تخصصاً آخر أو تصفح الأطباء يدوياً.</p>';
                        }
                        echo '</div>';
                        $shown++;
                    }
                }
            }
            ?>
        </div>

        <div class="assist-box">
            <p><strong>التخصصات الثلاثون المدعومة (اختيار يدوي)</strong></p>
            <p class="assist-note">لم نغيّر سجلات التخصصات الأخرى في قاعدة البيانات. هذه القائمة للواجهة التجريبية فقط.</p>
            <ul>
            <?php
            foreach ($supported as $sid => $sname) {
                $docCount = edoc_doctor_count_by_specialty($database, $sid);
                echo '<li style="margin:8px 0;">' . htmlspecialchars($sname, ENT_QUOTES, 'UTF-8');
                if ($docCount > 0) {
                    echo ' — <a href="doctors.php?spec=' . (int) $sid . '">أطباء متاحون (' . $docCount . ')</a>';
                } else {
                    echo ' — لا يوجد أطباء حالياً';
                }
                echo '</li>';
            }
            ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
