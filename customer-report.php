<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('/login.php');
}

// التحقق من صلاحيات المستخدم (فقط المدير يمكنه الوصول للتقارير)
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'غير مصرح لك بالوصول لهذه الصفحة';
    redirect('/dashboard.php');
}

// جلب تقرير العملاء
$stmt = $pdo->query("
    SELECT c.*, 
    (SELECT COUNT(*) FROM invoices i WHERE i.customer_id = c.id) as total_invoices,
    (SELECT COALESCE(SUM(total), 0) FROM invoices i WHERE i.customer_id = c.id) as total_spent
    FROM customers c 
    ORDER BY c.created_at DESC
");
$customers = $stmt->fetchAll();

// حساب الإجماليات
$total_customers = count($customers);
$total_invoices = 0;
$total_spent = 0;

foreach ($customers as $customer) {
    $total_invoices += $customer['total_invoices'];
    $total_spent += $customer['total_spent'];
}
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">تقرير العملاء</h1>
        <p class="text-muted">تقرير مفصل عن العملاء ومشترياتهم</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي العملاء</h5>
                <h2 class="mb-0"><?php echo $total_customers; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي الفواتير</h5>
                <h2 class="mb-0"><?php echo $total_invoices; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي الإنفاق</h5>
                <h2 class="mb-0"><?php echo number_format($total_spent, 2); ?> ر.س</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">متوسط الإنفاق</h5>
                <h2 class="mb-0"><?php echo $total_customers > 0 ? number_format($total_spent / $total_customers, 2) : '0.00'; ?> ر.س</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="card-title">تفاصيل العملاء</h5>
                    <a href="print-customer-report.php" target="_blank" class="btn btn-success">طباعة التقرير</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>البريد الإلكتروني</th>
                                <th>العنوان</th>
                                <th>عدد الفواتير</th>
                                <th>إجمالي الإنفاق</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($customers) > 0): ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['phone'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo htmlspecialchars($customer['address'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo $customer['total_invoices']; ?></td>
                                        <td><?php echo number_format($customer['total_spent'], 2); ?> ر.س</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">لا توجد عملاء مسجلين</td>
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