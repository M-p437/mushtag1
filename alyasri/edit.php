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

// التحقق من وجود معرف المستخدم
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'مستخدم غير موجود';
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

// معالجة تحديث المستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $username = clean_input($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    // التحقق من عدم تكرار اسم المستخدم
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'اسم المستخدم موجود مسبقاً';
    } else {
        if (!empty($password)) {
            // تشفير كلمة المرور
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // تحديث المستخدم مع كلمة المرور
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, password = ?, role = ? WHERE id = ?");
            $params = [$name, $username, $hashed_password, $role, $user_id];
        } else {
            // تحديث المستخدم بدون تغيير كلمة المرور
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ? WHERE id = ?");
            $params = [$name, $username, $role, $user_id];
        }
        
        if ($stmt->execute($params)) {
            $_SESSION['success'] = 'تم تحديث المستخدم بنجاح';
            redirect('/users/index.php');
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء تحديث المستخدم';
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">تعديل المستخدم</h1>
        <p class="text-muted">تعديل معلومات المستخدم <?php echo htmlspecialchars($user['name']); ?></p>
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
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label required">اسم المستخدم</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">تغيير كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="اتركه فارغًا إذا كنت لا تريد تغييرها">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label required">الصلاحية</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="cashier" <?php echo ($user['role'] === 'cashier') ? 'selected' : ''; ?>>كاشير</option>
                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>مدير</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid d-md-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-save"></i> حفظ التعديلات
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
                <h5 class="card-title">معلومات المستخدم</h5>
                <table class="table table-borderless">
                    <tr>
                        <td>الاسم:</td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                    </tr>
                    <tr>
                        <td>اسم المستخدم:</td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                    </tr>
                    <tr>
                        <td>الصلاحية:</td>
                        <td>
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                <?php echo $user['role'] === 'admin' ? 'مدير' : 'كاشير'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>تاريخ الإنشاء:</td>
                        <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>