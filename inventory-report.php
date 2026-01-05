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

// جلب تقرير الجرد
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.quantity ASC
");
$products = $stmt->fetchAll();

// حساب الإجماليات
$total_products = count($products);
$low_stock_count = 0;
$out_of_stock_count = 0;
$total_value = 0;

foreach ($products as $product) {
    $total_value += ($product['quantity'] * $product['purchase_price']);
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
        <h1 class="h3 mb-0">تقرير الجرد</h1>
        <p class="text-muted">تقرير مفصل عن حالة المخزون</p>
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
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">المنتجات منخفضة المخزون</h5>
                <h2 class="mb-0"><?php echo $low_stock_count; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">المنتجات نفذت</h5>
                <h2 class="mb-0"><?php echo $out_of_stock_count; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h5 class="text-white-50">قيمة المخزون</h5>
                <h2 class="mb-0"><?php echo number_format($total_value, 2); ?> ر.س</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="card-title">تفاصيل المخزون</h5>
                    <a href="print-inventory-report.php" target="_blank" class="btn btn-success">طباعة التقرير</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الفئة</th>
                                <th>الكمية</th>
                                <th>الحد الأدنى</th>
                                <th>سعر الشراء</th>
                                <th>إجمالي القيمة</th>
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
                                        <td><?php echo $product['min_quantity']; ?></td>
                                        <td><?php echo number_format($product['purchase_price'], 2); ?> ر.س</td>
                                        <td><?php echo number_format($product['quantity'] * $product['purchase_price'], 2); ?> ر.س</td>
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
                                    <td colspan="7" class="text-center text-muted py-4">لا توجد منتجات في المخزون</td>
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