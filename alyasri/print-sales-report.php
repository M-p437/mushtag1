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

// تعيين نوع المحتوى لطباعة التقرير
header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير المبيعات - <?php echo $start_date; ?> إلى <?php echo $end_date; ?></title>
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
        .report-period {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .summary-card {
            width: 15%;
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
        <div class="report-title">تقرير المبيعات</div>
        <div class="report-period">من <?php echo $start_date; ?> إلى <?php echo $end_date; ?></div>
        <div class="report-date">تاريخ التقرير: <?php echo date('Y/m/d'); ?></div>
    </div>
    
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-title">إجمالي الفواتير</div>
            <div class="card-value"><?php echo $total_invoices; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">إجمالي المبيعات</div>
            <div class="card-value"><?php echo number_format($total_sales, 2); ?> ر.س</div>
        </div>
        <div class="summary-card">
            <div class="card-title">إجمالي الخصومات</div>
            <div class="card-value"><?php echo number_format($total_discount, 2); ?> ر.س</div>
        </div>
        <div class="summary-card">
            <div class="card-title">إجمالي المدفوع</div>
            <div class="card-value"><?php echo number_format($total_paid, 2); ?> ر.س</div>
        </div>
        <div class="summary-card">
            <div class="card-title">المتبقى تحصيله</div>
            <div class="card-value"><?php echo number_format($total_remaining, 2); ?> ر.س</div>
        </div>
        <div class="summary-card">
            <div class="card-title">صافي الأرباح</div>
            <div class="card-value"><?php echo number_format($total_sales - $total_discount, 2); ?> ر.س</div>
        </div>
    </div>
    
    <table>
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
                                غير مسدد
                            <?php else: ?>
                                مسدد
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">لا توجد فواتير في هذه الفترة</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        تقرير مبيعات - محلات اليعري لمواد البناء والمقاولات
    </div>
</body>
</html>