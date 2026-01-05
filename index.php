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

// جلب المستخدمين
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">إدارة المستخدمين</h1>
        <p class="text-muted">يمكنك من هنا إدارة حسابات المستخدمين</p>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> إضافة مستخدم جديد
        </a>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-end">
            <input type="text" class="form-control w-auto" id="searchInput" placeholder="البحث...">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>اسم المستخدم</th>
                                <th>الصلاحية</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo $user['role'] === 'admin' ? 'مدير' : 'كاشير'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger confirm-delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">لا توجد مستخدمين مسجلين بعد</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>