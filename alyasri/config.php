<?php
// جلسة المستخدم
session_start();

// إعدادات الموقع
define('SITE_NAME', 'نظام إدارة محلات اليعري');
define('SITE_URL', 'http://localhost:85/alyasri');

// مسارات الملفات
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');

// تحميل ملف الاتصال بقاعدة البيانات
require_once 'database.php';

// دالة إعادة التوجيه
function redirect($path) {
    // Ensure path starts with /
    if (substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
    }
    $full_path = rtrim(SITE_URL, '/') . $path;
    header("Location: " . $full_path);
    exit();
}

// التحقق من تسجيل الدخول
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// التحقق من صلاحيات المستخدم
function check_permission($required_role) {
    if (!is_logged_in() || $_SESSION['role'] !== $required_role) {
        $_SESSION['error'] = 'غير مصرح لك بالوصول لهذه الصفحة';
        redirect('/login.php');
    }
}

// عرض رسائل الخطأ أو النجاح
function display_message() {
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
}
?>