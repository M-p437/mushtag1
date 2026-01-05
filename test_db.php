<?php
require_once 'config/database.php';

try {
    // التحقق من وجود الجدول users
    $stmt = $pdo->query("SELECT * FROM users LIMIT 1");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "اتصال بقاعدة البيانات ناجح!<br>";
        echo "المستخدم الأول: " . $result['username'] . "<br>";
        echo "الاسم: " . $result['name'] . "<br>";
        echo "الصلاحية: " . $result['role'] . "<br>";
    } else {
        echo "الجدول موجود ولكن لا توجد مستخدمين<br>";
        echo "جاري محاولة إنشاء مستخدم افتراضي...";
        
        // إنشاء مستخدم افتراضي
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute(['admin', $password_hash, 'مدير النظام', 'admin']);
        
        if ($result) {
            echo "تم إنشاء المستخدم الافتراضي (admin/admin123) بنجاح<br>";
        } else {
            echo "فشل في إنشاء المستخدم الافتراضي<br>";
        }
    }
} catch (Exception $e) {
    echo "خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "<br>";
    
    // محاولة إنشاء قاعدة البيانات والجداول
    echo "جاري محاولة إنشاء قاعدة البيانات...";
    
    // إعادة تعريف المتغيرات لتجنب التضارب
    $host = 'localhost';
    $dbname = 'alyasri_store';
    $username = 'root';
    $password = '';
    
    try {
        // إنشاء اتصال مؤقت بدون تحديد قاعدة البيانات
        $temp_pdo = new PDO("mysql:host=$host", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // إنشاء قاعدة البيانات إذا لم تكن موجودة
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        
        // إعادة الاتصال بقاعدة البيانات الجديدة
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // استيراد الجداول من ملف SQL
        $sql = file_get_contents('database.sql');
        // حذف أوامر الإنشاء وتحديد قاعدة البيانات من المحتوى
        $sql = str_replace([
            'CREATE DATABASE IF NOT EXISTS `alyasri_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
            'USE `alyasri_store`;'
        ], '', $sql);
        
        $pdo->exec($sql);
        
        echo "تم إنشاء قاعدة البيانات والجداول بنجاح!<br>";
        
        // إنشاء مستخدم افتراضي
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute(['admin', $password_hash, 'مدير النظام', 'admin']);
        
        if ($result) {
            echo "تم إنشاء المستخدم الافتراضي (admin/admin123) بنجاح<br>";
        }
    } catch (Exception $e) {
        echo "فشل في إنشاء قاعدة البيانات: " . $e->getMessage() . "<br>";
    }
}
?>