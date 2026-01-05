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

// جلب تقرير العملاء
$stmt = $pdo->query("
    SELECT c.*, 
    (SELECT COUNT(*) FROM invoices i WHERE i.customer_id = c.id) as total_invoices,
    (SELECT COALESCE(SUM(total), 0) FROM invoices i WHERE i.customer_id = c.id) as total_spent
    FROM customers c 
    ORDER BY c.created_at DESC
");
$customers = $stmt->fetchAll();

// حساب الإجماليات
$total_customers = count($customers);
$total_invoices = 0;
$total_spent = 0;

foreach ($customers as $customer) {
    $total_invoices += $customer['total_invoices'];
    $total_spent += $customer['total_spent'];
}

// تعيين نوع المحتوى لطباعة التقرير
header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير العملاء</title>
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
        <div class="report-title">تقرير العملاء</div>
        <div class="report-date">تاريخ التقرير: <?php echo date('Y/m/d'); ?></div>
    </div>
    
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-title">إجمالي العملاء</div>
            <div class="card-value"><?php echo $total_customers; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">إجمالي الفواتير</div>
            <div class="card-value"><?php echo $total_invoices; ?></div>
        </div>
        <div class="summary-card">
            <div class="card-title">إجمالي الإنفاق</div>
            <div class="card-value"><?php echo number_format($total_spent, 2); ?> ر.س</div>
        </div>
        <div class="summary-card">
            <div class="card-title">متوسط الإنفاق</div>
            <div class="card-value"><?php echo $total_customers > 0 ? number_format($total_spent / $total_customers, 2) : '0.00'; ?> ر.س</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>الاسم</th>
                <th>الهاتف</th>
                <th>البريد الإلكتروني</th>
                <th>العنوان</th>
                <th>عدد الفواتير</th>
                <th>إجمالي الإنفاق</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($customers) > 0): ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone'] ?? 'غير محدد'); ?></td>
                        <td><?php echo htmlspecialchars($customer['email'] ?? 'غير محدد'); ?></td>
                        <td><?php echo htmlspecialchars($customer['address'] ?? 'غير محدد'); ?></td>
                        <td><?php echo $customer['total_invoices']; ?></td>
                        <td><?php echo number_format($customer['total_spent'], 2); ?> ر.س</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">لا توجد عملاء مسجلين</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        تقرير عملاء - محلات اليعري لمواد البناء والمقاولات
    </div>
</body>
</html>