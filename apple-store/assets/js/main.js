// Apple Store - Main JavaScript

$(document).ready(function() {
    
    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 1000);
        }
    });
    
    // Auto-hide flash messages
    setTimeout(function() {
        $('.flash-message').fadeOut('slow');
    }, 5000);
    
    // Product image lazy loading
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Filter products
    $('#applyFilters').on('click', function() {
        const category = $('input[name="category"]:checked').val();
        const minPrice = $('#minPrice').val();
        const maxPrice = $('#maxPrice').val();
        const sort = $('#sortBy').val();
        
        let url = 'shop.php?';
        if (category) url += 'category=' + category + '&';
        if (minPrice) url += 'min_price=' + minPrice + '&';
        if (maxPrice) url += 'max_price=' + maxPrice + '&';
        if (sort) url += 'sort=' + sort;
        
        window.location.href = url;
    });
    
    // Clear filters
    $('#clearFilters').on('click', function() {
        window.location.href = 'shop.php';
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
        
        // Email validation
        const emailFields = $(this).find('input[type="email"]');
        emailFields.each(function() {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                isValid = false;
                $(this).addClass('is-invalid');
            }
        });
        
        // Password confirmation
        const password = $(this).find('input[name="password"]').val();
        const confirmPassword = $(this).find('input[name="confirm_password"]').val();
        if (password && confirmPassword && password !== confirmPassword) {
            isValid = false;
            $(this).find('input[name="confirm_password"]').addClass('is-invalid');
            alert($('body').hasClass('rtl') ? 'كلمات المرور غير متطابقة' : 'Passwords do not match');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
    
    // Remove invalid class on input
    $('input, textarea, select').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
    
    // Product quantity controls
    $('.qty-minus').on('click', function() {
        const input = $(this).siblings('input[type="number"]');
        let value = parseInt(input.val());
        if (value > 1) {
            input.val(value - 1);
        }
    });
    
    $('.qty-plus').on('click', function() {
        const input = $(this).siblings('input[type="number"]');
        let value = parseInt(input.val());
        const max = parseInt(input.attr('max'));
        if (!max || value < max) {
            input.val(value + 1);
        }
    });
    
    // Confirm delete actions
    $('.delete-btn, .btn-delete').on('click', function(e) {
        const isRTL = $('body').hasClass('rtl');
        const message = isRTL ? 'هل أنت متأكد من الحذف؟' : 'Are you sure you want to delete?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Star rating
    $('.star-rating').each(function() {
        const rating = $(this).data('rating');
        const stars = $(this).find('.star');
        stars.each(function(index) {
            if (index < rating) {
                $(this).addClass('active');
            }
        });
    });
    
    // Interactive star rating for reviews
    $('.review-stars .star').on('click', function() {
        const rating = $(this).data('value');
        $(this).siblings('input[name="rating"]').val(rating);
        $(this).parent().find('.star').removeClass('active');
        $(this).prevAll('.star').addBack().addClass('active');
    });
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.product-card').each(function() {
            const productName = $(this).find('h4').text().toLowerCase();
            if (productName.includes(searchTerm)) {
                $(this).parent().show();
            } else {
                $(this).parent().hide();
            }
        });
    });
    
    // Calculate order total
    function calculateOrderTotal() {
        let total = 0;
        $('.order-item').each(function() {
            const price = parseFloat($(this).data('price'));
            const quantity = parseInt($(this).find('.quantity').val());
            total += price * quantity;
        });
        $('#orderTotal').text(total.toFixed(2));
    }
    
    $('.quantity').on('change', calculateOrderTotal);
    
    // Governorate-based payment type
    $('#governorate').on('change', function() {
        const localGov = $(this).data('local');
        const selectedGov = $(this).val();
        
        if (selectedGov === localGov) {
            $('#payment_type_deposit').prop('disabled', false);
            $('#payment_type_full').prop('checked', false);
            $('#payment_type_deposit').prop('checked', true);
        } else {
            $('#payment_type_deposit').prop('disabled', true);
            $('#payment_type_full').prop('checked', true);
        }
    });
    
    // Image preview for file upload
    $('input[type="file"]').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(this).siblings('.image-preview').attr('src', e.target.result).show();
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });
    
    // Parallax effect for hero section
    $(window).on('scroll', function() {
        const scrolled = $(window).scrollTop();
        $('.hero').css('transform', 'translateY(' + (scrolled * 0.5) + 'px)');
    });
    
    // Counter animation
    $('.counter').each(function() {
        const $this = $(this);
        const countTo = $this.attr('data-count');
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'linear',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
    
});

// Preloader
window.addEventListener('load', function() {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        preloader.style.opacity = '0';
        setTimeout(function() {
            preloader.style.display = 'none';
        }, 500);
    }
});
