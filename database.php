<?php
// معلومات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // كلمة المرور الافتراضية لـ XAMPP تكون فارغة
define('DB_NAME', 'alyasri_store');

try {
    // إنشاء اتصال PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // في حالة حدوث خطأ في الاتصال
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// دالة لتنظيف المدخلات
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
