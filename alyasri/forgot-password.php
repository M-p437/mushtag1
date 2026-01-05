<?php
require_once 'config/config.php';

// إذا كان المستخدم مسجل دخوله بالفعل، يتم توجيهه للصفحة الرئيسية
if (is_logged_in()) {
    redirect('/dashboard.php');
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="text-center mb-4">استعادة كلمة المرور</h2>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> إرسال تعليمات الاستعادة
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0"><a href="login.php">العودة لتسجيل الدخول</a></p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <p>© <?php echo date('Y'); ?> محلات اليعري لمواد البناء والمقاولات</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>