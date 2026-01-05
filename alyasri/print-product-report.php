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

// تعيين نوع المحتوى لطباعة التقرير
header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير المنتجات</title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            margin: 20px;
            background-color: #fff;
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 15px;
        }
        .report-title {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .summary-card {
            width: 23%;
            text-align: center;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        @media print {
            body {
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" class="btn btn-primary">طباعة التقرير</button>
        <button onclick="window.close()" class="btn btn-secondary">إغلاق</button>
    </div>
    
    <div class="report-header">
        <div class="report-title">تقرير المنتجات</div>
        <div class="report-date">تاريخ التقرير: <?php echo date('Y/m/d'); ?></div>
    </div>
    
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-title">إجمالي المنتجات</div>
            <div class="card-value"><?php echo $total_products; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">إجمالي المبيعات</div>
            <div class="card-value"><?php echo $total_sold; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">إجمالي الإيرادات</div>
            <div class="card-value"><?php echo number_format($total_revenue, 2); ?> ر.س</div>
        </div>
        <div class="summary-card">
            <div class="card-title">المنتجات نفذت</div>
            <div class="card-value"><?php echo $out_of_stock_count; ?></div>
        </div>
    </div>
    
    <table>
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
                                نفذ من المخزون
                            <?php elseif ($product['quantity'] <= $product['min_quantity']): ?>
                                منخفض في المخزون
                            <?php else: ?>
                                متوفر
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">لا توجد منتجات مسجلة</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        تقرير منتجات - محلات اليعري لمواد البناء والمقاولات
    </div>
</body>
</html>