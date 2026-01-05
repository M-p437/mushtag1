<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('/login.php');
}

// التحقق من صلاحيات المستخدم (فقط المدير يمكنه إدارة المستخدمين)
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'غير مصرح لك بالوصول لهذه الصفحة';
    redirect('/dashboard.php');
}

// معالجة إضافة المستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // التحقق من عدم تكرار اسم المستخدم
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'اسم المستخدم موجود مسبقاً';
    } else {
        // تشفير كلمة المرور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // إضافة المستخدم
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $username, $hashed_password, $role])) {
            $_SESSION['success'] = 'تم إضافة المستخدم بنجاح';
            redirect('/users/index.php');
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء إضافة المستخدم';
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">إضافة مستخدم جديد</h1>
        <p class="text-muted">إضافة مستخدم جديد إلى النظام</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label required">الاسم الكامل</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label required">اسم المستخدم</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label required">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label required">الصلاحية</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="cashier" <?php echo (isset($_POST['role']) && $_POST['role'] === 'cashier') ? 'selected' : ''; ?>>كاشير</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>مدير</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid d-md-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-plus-circle"></i> إضافة المستخدم
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary flex-fill">
                            <i class="bi bi-x"></i> إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">تعليمات الإضافة</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>جميع الحقول مطلوبة</span>
                        <i class="bi bi-exclamation-circle text-danger"></i>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>كلمة المرور يجب أن تكون قوية</span>
                        <i class="bi bi-info-circle text-info"></i>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>الصلاحية تحدد صلاحيات المستخدم</span>
                        <i class="bi bi-info-circle text-info"></i>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>اسم المستخدم يجب أن يكون فريد</span>
                        <i class="bi bi-exclamation-circle text-danger"></i>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>