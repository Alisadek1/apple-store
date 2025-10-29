<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="footer-logo mb-3">
                    <div class="logo-container mb-2">
                        <img src="<?php echo SITE_URL; ?>/assets/images/logo.jpeg" alt="Apple Store" height="40" class="logo-img">
                    </div>
                    <h5 class="text-gold mb-3">
                        <?php echo getSetting('store_name_' . $lang) ?? 'Apple Store'; ?>
                    </h5>
                </div>
                <p class="text-light-gray">
                    <?php 
                    if ($lang === 'ar') {
                        echo 'متجرك الموثوق لمنتجات أبل الأصلية في مصر';
                    } else {
                        echo 'Your trusted store for authentic Apple products in Egypt';
                    }
                    ?>
                </p>
            </div>
            
            <div class="col-md-4 mb-4">
                <h5 class="text-gold mb-3"><?php echo t('contact_info'); ?></h5>
                <ul class="list-unstyled text-light-gray">
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt text-gold"></i>
                        <?php echo getSetting('store_address_' . $lang); ?>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope text-gold"></i>
                        <?php echo getSetting('store_email'); ?>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-phone text-gold"></i>
                        <?php echo getSetting('whatsapp_number'); ?>
                    </li>
                </ul>
            </div>
            
            <div class="col-md-4 mb-4">
                <h5 class="text-gold mb-3"><?php echo t('follow_us'); ?></h5>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        
        <hr class="border-gold">
        
        <div class="row">
            <div class="col-12 text-center">
                <p class="text-light-gray mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo getSetting('store_name_' . $lang); ?>. 
                    <?php echo t('all_rights_reserved'); ?>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- AOS Animation -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<!-- Custom JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

<script>
// Initialize AOS
AOS.init({
    duration: 800,
    once: true,
    offset: 100,
    easing: 'ease-in-out'
});

// Navbar scroll effect
$(window).scroll(function() {
    if ($(this).scrollTop() > 50) {
        $('#mainNav').addClass('scrolled');
    } else {
        $('#mainNav').removeClass('scrolled');
    }
});

// Logo and hero video enhancements
$(document).ready(function() {
    // SVG support detection and fallback
    function supportsSVG() {
        return document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Image", "1.1");
    }
    
    if (!supportsSVG()) {
        document.body.classList.add('no-svg');
    }
    
    // Logo error handling - fallback to PNG if SVG fails
    $('.logo-svg').on('error', function() {
        $(this).hide();
        $(this).siblings('.logo-png').show();
    });
    
    // Enhanced logo interactions
    $('.navbar-brand, .footer-logo').hover(
        function() {
            $(this).find('.logo-img').addClass('animate__animated animate__pulse');
        },
        function() {
            $(this).find('.logo-img').removeClass('animate__animated animate__pulse');
        }
    );
    // Enhanced hero background video with auto-replay
    const heroVideo = document.querySelector('.hero-background-video');
    if (heroVideo) {
        // Force video properties for better autoplay
        heroVideo.muted = true;
        heroVideo.loop = true;
        heroVideo.autoplay = true;
        heroVideo.playsInline = true;
        
        // Ensure video plays and replays automatically
        function ensureVideoPlays() {
            heroVideo.play().catch(function(error) {
                console.log('Video autoplay failed, retrying...', error);
                // Retry after a short delay
                setTimeout(() => {
                    heroVideo.play().catch(e => console.log('Video play retry failed:', e));
                }, 1000);
            });
        }
        
        // Initial play attempt
        ensureVideoPlays();
        
        // Ensure video restarts when it ends (backup for loop)
        heroVideo.addEventListener('ended', function() {
            heroVideo.currentTime = 0;
            ensureVideoPlays();
        });
        
        // Handle video loading
        heroVideo.addEventListener('loadeddata', function() {
            ensureVideoPlays();
        });
        
        // Pause video when page is not visible (performance optimization)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                heroVideo.pause();
            } else {
                ensureVideoPlays();
            }
        });
        
        // Handle window focus/blur for better video control
        window.addEventListener('focus', function() {
            ensureVideoPlays();
        });
        
        // Pause video on mobile to save battery
        if (window.innerWidth <= 768) {
            heroVideo.pause();
            heroVideo.style.display = 'none';
        } else {
            // Ensure video plays on desktop
            setTimeout(ensureVideoPlays, 500);
        }
    }
    

    
    // Enhanced hero button interactions
    $('.btn-hero').hover(
        function() {
            $(this).addClass('animate__animated animate__pulse');
        },
        function() {
            $(this).removeClass('animate__animated animate__pulse');
        }
    );
});
</script>

</body>
</html>
