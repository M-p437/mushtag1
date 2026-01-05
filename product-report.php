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

// جلب تقرير المنتجات
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name,
    (SELECT COUNT(*) FROM invoice_items ii JOIN invoices i ON ii.invoice_id = i.id WHERE ii.product_id = p.id) as times_sold,
    (SELECT COALESCE(SUM(ii.total), 0) FROM invoice_items ii JOIN invoices i ON ii.invoice_id = i.id WHERE ii.product_id = p.id) as total_revenue
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.quantity ASC
");
$products = $stmt->fetchAll();

// حساب الإجماليات
$total_products = count($products);
$total_sold = 0;
$total_revenue = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

foreach ($products as $product) {
    $total_sold += $product['times_sold'];
    $total_revenue += $product['total_revenue'];
    if ($product['quantity'] <= $product['min_quantity']) {
        $low_stock_count++;
    }
    if ($product['quantity'] == 0) {
        $out_of_stock_count++;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">تقرير المنتجات</h1>
        <p class="text-muted">تقرير مفصل عن المنتجات وحركة المبيعات</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي المنتجات</h5>
                <h2 class="mb-0"><?php echo $total_products; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي المبيعات</h5>
                <h2 class="mb-0"><?php echo $total_sold; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">إجمالي الإيرادات</h5>
                <h2 class="mb-0"><?php echo number_format($total_revenue, 2); ?> ر.س</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">المنتجات نفذت</h5>
                <h2 class="mb-0"><?php echo $out_of_stock_count; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="card-title">تفاصيل المنتجات</h5>
                    <a href="print-product-report.php" target="_blank" class="btn btn-success">طباعة التقرير</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الفئة</th>
                                <th>الكمية</th>
                                <th>عدد مرات البيع</th>
                                <th>إجمالي الإيرادات</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?></td>
                                        <td><?php echo $product['times_sold']; ?></td>
                                        <td><?php echo number_format($product['total_revenue'], 2); ?> ر.س</td>
                                        <td>
                                            <?php if ($product['quantity'] == 0): ?>
                                                <span class="badge bg-danger">نفد من المخزون</span>
                                            <?php elseif ($product['quantity'] <= $product['min_quantity']): ?>
                                                <span class="badge bg-warning">منخفض في المخزون</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">متوفر</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">لا توجد منتجات مسجلة</td>
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