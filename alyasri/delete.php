<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('/login.php');
}

// التحقق من صلاحيات المستخدم (فقط المدير يمكنه حذف المستخدمين)
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'غير مصرح لك بالوصول لهذه الصفحة';
    redirect('/users/index.php');
}

// التحقق من وجود معرف المستخدم
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'مستخدم غير موجود';
    redirect('/users/index.php');
}

// لا يمكن حذف المستخدم الحالي
if ($_GET['id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = 'لا يمكن حذف حسابك الخاص';
    redirect('/users/index.php');
}

$user_id = (int)$_GET['id'];

// جلب معلومات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'مستخدم غير موجود';
    redirect('/users/index.php');
}

// حذف المستخدم
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
if ($stmt->execute([$user_id])) {
    $_SESSION['success'] = 'تم حذف المستخدم بنجاح';
} else {
    $_SESSION['error'] = 'حدث خطأ أثناء حذف المستخدم';
}

redirect('/users/index.php');
?>