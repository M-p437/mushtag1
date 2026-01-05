// تفعيل أدوات Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل النوافذ المنبثقة
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // تفعيل النوافذ المنبثقة
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // إخفاء التنبيهات تلقائياً بعد 5 ثواني
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // تفعيل القوائم المنسدلة
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // تفعيل الجداول القابلة للفرز
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.datatable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
            },
            "responsive": true,
            "order": [[0, "desc"]]
        });
    }

    // إضافة فاصل الآلاف للأرقام في حقول الإدخال
    document.querySelectorAll('.thousands-separator').forEach(function(element) {
        element.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9.]/g, '');
            if (value) {
                let parts = value.split('.');
                parts[0] = parts[0].replace(/\\B(?=(\\d{3})+(?!\\d))/g, ",");
                this.value = parts.join('.');
            }
        });
    });

    // تأكيد الحذف
    document.querySelectorAll('.confirm-delete').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('هل أنت متأكد من حذف هذا السجل؟ لا يمكن التراجع عن هذه العملية.')) {
                window.location.href = this.getAttribute('href');
            }
        });
    });

    // إضافة منتج جديد إلى الفاتورة
    document.querySelectorAll('.add-product-row').forEach(function(button) {
        button.addEventListener('click', function() {
            const template = document.querySelector('#product-row-template').innerHTML;
            const container = document.querySelector('#products-container');
            const newRow = document.createElement('div');
            newRow.className = 'product-row mb-3';
            newRow.innerHTML = template;
            container.appendChild(newRow);
            initProductRow(newRow);
        });
    });

    // تهيئة صفوف المنتجات
    function initProductRow(row) {
        // تهيئة منتج select2 إذا كان موجوداً
        if ($.fn.select2) {
            $(row).find('.product-select').select2({
                placeholder: 'ابحث عن منتج...',
                allowClear: true,
                language: 'ar',
                dir: 'rtl'
            });
        }

        // إزالة صف المنتج
        row.querySelector('.remove-product').addEventListener('click', function() {
            if (confirm('هل تريد حذف هذا المنتج من الفاتورة؟')) {
                row.remove();
                calculateTotal();
            }
        });

        // تحديث المجموع الفرعي عند تغيير الكمية أو السعر
        const quantityInput = row.querySelector('.quantity');
        const priceInput = row.querySelector('.price');
        const subtotalInput = row.querySelector('.subtotal');

        function updateSubtotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            subtotalInput.value = (quantity * price).toFixed(2);
            calculateTotal();
        }

        quantityInput.addEventListener('input', updateSubtotal);
        priceInput.addEventListener('input', updateSubtotal);

        // تحديث السعر عند اختيار منتج
        if (row.querySelector('.product-select')) {
            row.querySelector('.product-select').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.dataset.price) {
                    priceInput.value = selectedOption.dataset.price;
                    updateSubtotal();
                }
            });
        }
    }

    // حساب المجموع الكلي للفاتورة
    function calculateTotal() {
        let subtotal = 0;
        document.querySelectorAll('.product-row').forEach(function(row) {
            const subtotalInput = row.querySelector('.subtotal');
            if (subtotalInput) {
                subtotal += parseFloat(subtotalInput.value) || 0;
            }
        });

        const discount = parseFloat(document.querySelector('#discount').value) || 0;
        const taxRate = parseFloat(document.querySelector('#tax_rate').value) || 0;
        
        const tax = (subtotal - discount) * (taxRate / 100);
        const total = subtotal - discount + tax;

        document.querySelector('#subtotal').value = subtotal.toFixed(2);
        document.querySelector('#tax_amount').value = tax.toFixed(2);
        document.querySelector('#total').value = total.toFixed(2);
        document.querySelector('#paid').max = total;
    }

    // إضافة مستمعي الأحداث لحقول الخصم والضريبة
    const discountInput = document.querySelector('#discount');
    const taxRateInput = document.querySelector('#tax_rate');
    const paidInput = document.querySelector('#paid');

    if (discountInput) {
        discountInput.addEventListener('input', calculateTotal);
    }
    if (taxRateInput) {
        taxRateInput.addEventListener('input', calculateTotal);
    }
    if (paidInput) {
        paidInput.addEventListener('input', function() {
            const total = parseFloat(document.querySelector('#total').value) || 0;
            const paid = parseFloat(this.value) || 0;
            const remaining = total - paid;
            document.querySelector('#remaining').value = remaining.toFixed(2);
        });
    }

    // تهيئة صفوف المنتجات الموجودة
    document.querySelectorAll('.product-row').forEach(initProductRow);
});

// دالة لتنسيق الأرقام بإضافة فاصل الآلاف
function formatNumber(number) {
    return number.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, ",");
}

// دالة لإزالة تنسيق الأرقام
function unformatNumber(formattedNumber) {
    return formattedNumber.replace(/,/g, '');
}

// دالة للتحقق من صحة البريد الإلكتروني
function validateEmail(email) {
    const re = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
    return re.test(String(email).toLowerCase());
}

// دالة للتحقق من صحة رقم الهاتف
function validatePhone(phone) {
    const re = /^[0-9\\-+\\s()]*$/;
    return re.test(phone);
}