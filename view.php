<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('/login.php');
}

// التحقق من وجود معرف الفاتورة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'فاتورة غير موجودة';
    redirect('/invoices/index.php');
}

$invoice_id = (int)$_GET['id'];

// جلب معلومات الفاتورة
$stmt = $pdo->prepare("
    SELECT i.*, c.name as customer_name, c.phone as customer_phone, u.name as user_name 
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    $_SESSION['error'] = 'فاتورة غير موجودة';
    redirect('/invoices/index.php');
}

// جلب تفاصيل الفاتورة
$stmt = $pdo->prepare("
    SELECT ii.*, p.name as product_name, p.barcode, p.unit as product_unit
    FROM invoice_items ii
    LEFT JOIN products p ON ii.product_id = p.id
    WHERE ii.invoice_id = ?
");
$stmt->execute([$invoice_id]);
$invoice_items = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">عرض الفاتورة</h1>
        <p class="text-muted">رقم الفاتورة: <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoice_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['product_unit'] ?? 'وحدة'); ?></td>
                                    <td><?php echo number_format($item['price'], 2); ?> ر.س</td>
                                    <td><?php echo number_format($item['total'], 2); ?> ر.س</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">المجموع الفرعي:</th>
                                <th><?php echo number_format($invoice['subtotal'], 2); ?> ر.س</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">الخصم:</th>
                                <th><?php echo number_format($invoice['discount'], 2); ?> ر.س</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">الإجمالي:</th>
                                <th><?php echo number_format($invoice['total'], 2); ?> ر.س</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">المدفوع:</th>
                                <th><?php echo number_format($invoice['paid'], 2); ?> ر.س</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">المتبقي:</th>
                                <th><?php echo number_format($invoice['remaining'], 2); ?> ر.س</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if ($invoice['notes']): ?>
                    <div class="mt-3">
                        <h6>ملاحظات:</h6>
                        <p><?php echo htmlspecialchars($invoice['notes']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">معلومات الفاتورة</h5>
                <table class="table table-borderless">
                    <tr>
                        <td>رقم الفاتورة:</td>
                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                    </tr>
                    <tr>
                        <td>التاريخ:</td>
                        <td><?php echo date('Y/m/d H:i', strtotime($invoice['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td>العميل:</td>
                        <td><?php echo htmlspecialchars($invoice['customer_name'] ?? 'زائر'); ?></td>
                    </tr>
                    <tr>
                        <td>الكاشير:</td>
                        <td><?php echo htmlspecialchars($invoice['user_name']); ?></td>
                    </tr>
                    <tr>
                        <td>الحالة:</td>
                        <td>
                            <?php if ($invoice['remaining'] > 0): ?>
                                <span class="badge bg-warning">غير مسدد بالكامل</span>
                            <?php else: ?>
                                <span class="badge bg-success">مسدد</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <div class="d-grid gap-2">
                    <a href="print.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="btn btn-primary">
                        <i class="bi bi-printer"></i> طباعة الفاتورة
                    </a>
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-right"></i> العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>