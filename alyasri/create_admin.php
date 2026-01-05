<?php
require_once 'config/database.php';

// التحقق مما إذا كان المستخدم 'admin' موجودًا بالفعل
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['admin']);
$user = $stmt->fetch();

if ($user) {
    // تحديث كلمة المرور للمستخدم admin
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $result = $stmt->execute([$password_hash, 'admin']);
    
    if ($result) {
        echo "تم تحديث كلمة مرور المستخدم admin إلى 'admin123' بنجاح";
    } else {
        echo "فشل في تحديث كلمة المرور";
    }
} else {
    // إنشاء مستخدم admin جديد
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute(['admin', $password_hash, 'مدير النظام', 'admin']);
    
    if ($result) {
        echo "تم إنشاء المستخدم admin مع كلمة المرور 'admin123' بنجاح";
    } else {
        echo "فشل في إنشاء المستخدم";
    }
}
?>