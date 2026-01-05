<?php
require_once 'config/config.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('/login.php');
}

// جلب إحصائيات سريعة
$stats = [
    'total_products' => 0,
    'total_customers' => 0,
    'today_sales' => 0,
    'monthly_sales' => 0
];

try {
    // عدد المنتجات
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $stmt->fetch()['count'];
    
    // عدد العملاء
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = $stmt->fetch()['count'];
    
    // مبيعات اليوم
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as total 
        FROM invoices 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $stats['today_sales'] = number_format($stmt->fetch()['total'], 2);
    
    // مبيعات الشهر
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as total 
        FROM invoices 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $stats['monthly_sales'] = number_format($stmt->fetch()['total'], 2);
    
    // أحدث الفواتير
    $stmt = $pdo->query("
        SELECT i.*, c.name as customer_name, u.name as user_name 
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN users u ON i.user_id = u.id
        ORDER BY i.created_at DESC 
        LIMIT 5
    ");
    $recent_invoices = $stmt->fetchAll();
    
    // المنتجات المنخفضة في المخزون
    $stmt = $pdo->query("
        SELECT * FROM products 
        WHERE quantity <= min_quantity 
        ORDER BY quantity ASC 
        LIMIT 5
    ");
    $low_stock_products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'حدث خطأ في جلب البيانات: ' . $e->getMessage();
}
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">لوحة التحكم</h1>
        <p class="text-muted">مرحباً بك <?php echo htmlspecialchars($_SESSION['name']); ?>، يمكنك من هنا متابعة نشاط المحل.</p>
    </div>
</div>

<!-- بطاقات الإحصائيات -->
<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">إجمالي المنتجات</h6>
                        <h2 class="mb-0"><?php echo $stats['total_products']; ?></h2>
                    </div>
                    <div class="icon-lg">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary bg-opacity-25 border-0">
                <a href="products/" class="text-white text-decoration-none">عرض الكل <i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">إجمالي العملاء</h6>
                        <h2 class="mb-0"><?php echo $stats['total_customers']; ?></h2>
                    </div>
                    <div class="icon-lg">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success bg-opacity-25 border-0">
                <a href="customers/" class="text-white text-decoration-none">عرض الكل <i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">مبيعات اليوم</h6>
                        <h2 class="mb-0"><?php echo $stats['today_sales']; ?> ر.س</h2>
                    </div>
                    <div class="icon-lg">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-info bg-opacity-25 border-0">
                <a href="invoices/" class="text-white text-decoration-none">عرض الفواتير <i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">مبيعات الشهر</h6>
                        <h2 class="mb-0"><?php echo $stats['monthly_sales']; ?> ر.س</h2>
                    </div>
                    <div class="icon-lg">
                        <i class="bi bi-graph-up"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-warning bg-opacity-25 border-0">
                <a href="reports/" class="text-white text-decoration-none">عرض التقارير <i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- أحدث الفواتير -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">أحدث الفواتير</h5>
                <a href="invoices/" class="btn btn-sm btn-outline-primary">عرض الكل</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>التاريخ</th>
                                <th>العميل</th>
                                <th>المجموع</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_invoices) > 0): ?>
                                <?php foreach ($recent_invoices as $invoice): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($invoice['created_at'])); ?></td>
                                        <td><?php echo !empty($invoice['customer_name']) ? htmlspecialchars($invoice['customer_name']) : 'زائر'; ?></td>
                                        <td><?php echo number_format($invoice['total'], 2); ?> ر.س</td>
                                        <td>
                                            <?php if ($invoice['remaining'] > 0): ?>
                                                <span class="badge bg-warning">غير مسدد بالكامل</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">مسدد</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="invoices/view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="invoices/print.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">لا توجد فواتير مسجلة بعد</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- المنتجات المنخفضة في المخزون -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">المنتجات المنخفضة</h5>
                <a href="products/low-stock.php" class="btn btn-sm btn-outline-danger">عرض الكل</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (count($low_stock_products) > 0): ?>
                        <?php foreach ($low_stock_products as $product): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted">
                                            الكمية المتوفرة: 
                                            <span class="fw-bold <?php echo $product['quantity'] <= 0 ? 'text-danger' : ''; ?>">
                                                <?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                            </span>
                                        </small>
                                    </div>
                                    <a href="products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-check-circle display-6 d-block mb-2"></i>
                            <p class="mb-0">لا توجد منتجات منخفضة في المخزون</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
