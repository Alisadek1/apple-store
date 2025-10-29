// Admin Dashboard JavaScript

$(document).ready(function() {
    
    // Initialize DataTables
    if ($('.data-table').length) {
        $('.data-table').DataTable({
            language: {
                url: $('body').hasClass('rtl') ? 
                    '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' : 
                    '//cdn.datatables.net/plug-ins/1.13.6/i18n/en-GB.json'
            },
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
    
    // Toggle sidebar on mobile
    $('.navbar-toggler').on('click', function() {
        $('.sidebar').toggleClass('show');
    });
    
    // Image preview
    $('input[type="file"]').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            const preview = $(this).closest('.mb-3').find('.image-preview');
            
            reader.onload = function(e) {
                if (preview.length) {
                    preview.attr('src', e.target.result).show();
                } else {
                    $(this).closest('.mb-3').append(
                        '<img src="' + e.target.result + '" class="image-preview" alt="Preview">'
                    );
                }
            }.bind(this);
            
            reader.readAsDataURL(file);
        }
    });
    
    // Confirm delete
    $('.btn-delete, .delete-btn').on('click', function(e) {
        const isRTL = $('body').hasClass('rtl');
        const message = isRTL ? 'هل أنت متأكد من الحذف؟' : 'Are you sure you want to delete?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Status badge colors
    $('.status-badge').each(function() {
        const status = $(this).text().toLowerCase().trim();
        $(this).removeClass('badge-pending badge-confirmed badge-shipped badge-completed badge-cancelled');
        
        if (status.includes('pending') || status.includes('معلق')) {
            $(this).addClass('badge-pending');
        } else if (status.includes('confirmed') || status.includes('مؤكد')) {
            $(this).addClass('badge-confirmed');
        } else if (status.includes('shipped') || status.includes('شحن')) {
            $(this).addClass('badge-shipped');
        } else if (status.includes('completed') || status.includes('مكتمل')) {
            $(this).addClass('badge-completed');
        } else if (status.includes('cancelled') || status.includes('ملغي')) {
            $(this).addClass('badge-cancelled');
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        
        $(this).find('input[required], textarea[required], select[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            const isRTL = $('body').hasClass('rtl');
            alert(isRTL ? 'يرجى ملء جميع الحقول المطلوبة' : 'Please fill all required fields');
            return false;
        }
    });
    
    // Remove invalid class on input
    $('input, textarea, select').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
    
});
