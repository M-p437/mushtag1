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

// تعيين نوع المحتوى لطباعة الفاتورة
header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            margin: 20px;
            background-color: #fff;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 15px;
        }
        .invoice-title {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        .invoice-number {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .customer-info, .company-info {
            width: 45%;
        }
        .info-title {
            font-weight: bold;
            margin-bottom: 5px;
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
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .notes {
            margin-top: 20px;
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
        <button onclick="window.print()" class="btn btn-primary">طباعة الفاتورة</button>
        <button onclick="window.close()" class="btn btn-secondary">إغلاق</button>
    </div>
    
    <div class="invoice-header">
        <div class="invoice-title">محلات اليعري لمواد البناء والمقاولات</div>
        <div class="invoice-number">فاتورة <?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
        <div class="invoice-date">تاريخ الإصدار: <?php echo date('Y/m/d H:i', strtotime($invoice['created_at'])); ?></div>
    </div>
    
    <div class="invoice-details">
        <div class="customer-info">
            <div class="info-title">معلومات العميل:</div>
            <div>الاسم: <?php echo htmlspecialchars($invoice['customer_name'] ?? 'زائر'); ?></div>
            <?php if (!empty($invoice['customer_phone'])): ?>
                <div>الهاتف: <?php echo htmlspecialchars($invoice['customer_phone']); ?></div>
            <?php endif; ?>
        </div>
        <div class="company-info">
            <div class="info-title">معلومات الكاشير:</div>
            <div>الاسم: <?php echo htmlspecialchars($invoice['user_name']); ?></div>
            <div>الوقت: <?php echo date('Y/m/d H:i', strtotime($invoice['created_at'])); ?></div>
        </div>
    </div>
    
    <table>
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
            <tr class="total-row">
                <td colspan="3">المجموع الفرعي:</td>
                <td><?php echo number_format($invoice['subtotal'], 2); ?> ر.س</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">الخصم:</td>
                <td><?php echo number_format($invoice['discount'], 2); ?> ر.س</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">الإجمالي:</td>
                <td><?php echo number_format($invoice['total'], 2); ?> ر.س</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">المدفوع:</td>
                <td><?php echo number_format($invoice['paid'], 2); ?> ر.س</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">المتبقي:</td>
                <td><?php echo number_format($invoice['remaining'], 2); ?> ر.س</td>
            </tr>
        </tfoot>
    </table>
    
    <?php if ($invoice['notes']): ?>
        <div class="notes">
            <div class="info-title">ملاحظات:</div>
            <div><?php echo htmlspecialchars($invoice['notes']); ?></div>
        </div>
    <?php endif; ?>
    
    <div class="footer">
        شكرًا لتعاملكم مع محلات اليعري لمواد البناء والمقاولات
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // طباعة الفاتورة تلقائياً عند التحميل إذا كان المستخدم من متصفح موبايل
        if (window.innerWidth <= 768) {
            window.print();
        }
    </script>
</body>
</html>