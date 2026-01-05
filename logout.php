<?php
require_once 'config/config.php';

// تدمير جميع بيانات الجلسة
$_SESSION = array();

// إذا كنت تريد تدمير الجلسة تمامًا، قم بإلغاء تعيين معرف الجلسة
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// أخيرًا، قم بتدمير الجلسة
session_destroy();

// توجيه المستخدم إلى صفحة تسجيل الدخول
$_SESSION['success'] = 'تم تسجيل الخروج بنجاح';
redirect('/login.php');
?>
