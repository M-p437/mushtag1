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

// تعيين نوع المحتوى لطباعة التقرير
header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الجرد</title>
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
        <div class="report-title">تقرير الجرد</div>
        <div class="report-date">تاريخ التقرير: <?php echo date('Y/m/d'); ?></div>
    </div>
    
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-title">إجمالي المنتجات</div>
            <div class="card-value"><?php echo $total_products; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">المنتجات منخفضة المخزون</div>
            <div class="card-value"><?php echo $low_stock_count; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">المنتجات نفذت</div>
            <div class="card-value"><?php echo $out_of_stock_count; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">قيمة المخزون</div>
            <div class="card-value"><?php echo number_format($total_value, 2); ?> ر.س</div>
        </div>
    </div>
    
    <table>
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
                    <td colspan="7" class="text-center">لا توجد منتجات في المخزون</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        تقرير جرد - محلات اليعري لمواد البناء والمقاولات
    </div>
</body>
</html>