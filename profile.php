<?php
require_once 'config/config.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('/login.php');
}

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $username = clean_input($_POST['username']);
    
    // التحقق من عدم تكرار اسم المستخدم
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'اسم المستخدم موجود مسبقاً';
    } else {
        // تحديث بيانات المستخدم
        $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
        if ($stmt->execute([$name, $username, $_SESSION['user_id']])) {
            $_SESSION['success'] = 'تم تحديث الملف الشخصي بنجاح';
            $_SESSION['name'] = $name;
            $_SESSION['username'] = $username;
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء تحديث الملف الشخصي';
        }
    }
    
    redirect('/profile.php');
}

// جلب بيانات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/login.php');
}
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">الملف الشخصي</h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">الاسم الكامل</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">اسم المستخدم</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">الصلاحية</label>
                        <input type="text" class="form-control" id="role" value="<?php echo $user['role'] === 'admin' ? 'مدير' : 'كاشير'; ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="created_at" class="form-label">تاريخ الإنشاء</label>
                        <input type="text" class="form-control" id="created_at" value="<?php echo date('Y/m/d', strtotime($user['created_at'])); ?>" readonly>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">تحديث الملف الشخصي</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                </div>
                <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                <p class="text-muted"><?php echo $user['role'] === 'admin' ? 'مدير النظام' : 'كاشير'; ?></p>
                
                <a href="#" class="btn btn-outline-primary btn-sm">تغيير كلمة المرور</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>