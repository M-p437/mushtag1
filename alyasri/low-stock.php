<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect('/login.php');
}

// جلب المنتجات المنخفضة في المخزون
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.quantity <= p.min_quantity
    ORDER BY p.quantity ASC
");
$low_stock_products = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">المنتجات المنخفضة في المخزون</h1>
        <p class="text-muted">هذه المنتجات تحتاج إلى تجديد المخزون</p>
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
                                <th>الفئة</th>
                                <th>الكمية الحالية</th>
                                <th>الحد الأدنى</th>
                                <th>الوحدة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($low_stock_products) > 0): ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'غير محدد'); ?></td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $product['min_quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">لا توجد منتجات منخفضة في المخزون</td>
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