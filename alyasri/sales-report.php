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

// معالجة فلترة التقرير
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// جلب تقرير المبيعات
$stmt = $pdo->prepare("
    SELECT i.*, c.name as customer_name, u.name as user_name 
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE DATE(i.created_at) BETWEEN ? AND ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$start_date, $end_date]);
$invoices = $stmt->fetchAll();

// حساب الإجماليات
$total_sales = 0;
$total_invoices = count($invoices);
$total_discount = 0;
$total_paid = 0;
$total_remaining = 0;

foreach ($invoices as $invoice) {
    $total_sales += $invoice['total'];
    $total_discount += $invoice['discount'];
    $total_paid += $invoice['paid'];
    $total_remaining += $invoice['remaining'];
}
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">تقرير المبيعات</h1>
        <p class="text-muted">تقرير مفصل عن المبيعات حسب الفترة المحددة</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label for="start_date" class="form-label">من تاريخ</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label for="end_date" class="form-label">إلى تاريخ</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">تصفية</button>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <a href="print-sales-report.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" target="_blank" class="btn btn-success">طباعة التقرير</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي الفواتير</h5>
                <h2 class="mb-0"><?php echo $total_invoices; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي المبيعات</h5>
                <h2 class="mb-0"><?php echo number_format($total_sales, 2); ?> ر.س</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي الخصومات</h5>
                <h2 class="mb-0"><?php echo number_format($total_discount, 2); ?> ر.س</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي المدفوع</h5>
                <h2 class="mb-0"><?php echo number_format($total_paid, 2); ?> ر.س</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">المتبقى تحصيله</h5>
                <h2 class="mb-0"><?php echo number_format($total_remaining, 2); ?> ر.س</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">صافي الأرباح</h5>
                <h2 class="mb-0"><?php echo number_format($total_sales - $total_discount, 2); ?> ر.س</h2>
            </div>
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
                                <th>رقم الفاتورة</th>
                                <th>التاريخ</th>
                                <th>العميل</th>
                                <th>الكاشير</th>
                                <th>المجموع</th>
                                <th>الخصم</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($invoices) > 0): ?>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($invoice['created_at'])); ?></td>
                                        <td><?php echo !empty($invoice['customer_name']) ? htmlspecialchars($invoice['customer_name']) : 'زائر'; ?></td>
                                        <td><?php echo htmlspecialchars($invoice['user_name']); ?></td>
                                        <td><?php echo number_format($invoice['subtotal'], 2); ?> ر.س</td>
                                        <td><?php echo number_format($invoice['discount'], 2); ?> ر.س</td>
                                        <td><?php echo number_format($invoice['total'], 2); ?> ر.س</td>
                                        <td>
                                            <?php if ($invoice['remaining'] > 0): ?>
                                                <span class="badge bg-warning">غير مسدد</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">مسدد</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">لا توجد فواتير في هذه الفترة</td>
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