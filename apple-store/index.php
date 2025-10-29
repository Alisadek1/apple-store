<?php
require_once __DIR__ . '/includes/header.php';

// Get featured products
$db = getDB();
$stmt = $db->query("SELECT p.*, c.name_en, c.name_ar 
                    FROM products p 
                    JOIN categories c ON p.category_id = c.id 
                    WHERE p.featured = 1 
                    ORDER BY p.created_at DESC 
                    LIMIT 6");
$featured_products = $stmt->fetchAll();

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY id")->fetchAll();

// Get approved reviews
$reviews = $db->query("SELECT r.*, u.name, p.name_en, p.name_ar 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       JOIN products p ON r.product_id = p.id 
                       WHERE r.approved = 1 
                       ORDER BY r.created_at DESC 
                       LIMIT 6")->fetchAll();
?>

<!-- Hero Section with Background Video -->
<section class="hero hero-video">
    <!-- Background Video -->
    <div class="hero-video-container">
        <video autoplay muted loop playsinline preload="auto" class="hero-background-video" id="heroVideo">
            <source src="<?php echo SITE_URL; ?>/assets/videos/apple-showcase.mp4" type="video/mp4">
            <?php echo $lang === 'ar' ? 'متصفحك لا يدعم تشغيل الفيديو.' : 'Your browser does not support the video tag.'; ?>
        </video>
        <div class="hero-video-overlay"></div>
    </div>
    
    <!-- Background Logo -->
    <div class="hero-background-logo">
        <img src="<?php echo SITE_URL; ?>/assets/images/logo.jpeg" alt="Background Logo" class="hero-logo-bg">
    </div>
    
    <!-- Hero Content -->
    <div class="hero-content" data-aos="fade-up">
        <h1><?php echo t('hero_title'); ?></h1>
        <p><?php echo t('hero_subtitle'); ?></p>
        <div class="hero-buttons" data-aos="fade-up" data-aos-delay="200">
            <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-gold btn-hero">
                <?php echo t('shop_now'); ?>
                <i class="fas fa-arrow-<?php echo $is_rtl ? 'left' : 'right'; ?> ms-2"></i>
            </a>
        </div>
        
        <!-- Luxury tagline -->
        <div class="hero-tagline" data-aos="fade-up" data-aos-delay="400">
            <p class="luxury-text-small">
                <?php echo $lang === 'ar' ? 'الفخامة في كل التفاصيل' : 'Luxury in Every Detail'; ?>
            </p>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><?php echo t('categories'); ?></h2>
            <p><?php echo $lang === 'ar' ? 'تصفح فئات منتجاتنا' : 'Browse our product categories'; ?></p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($categories as $index => $category): ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="category-card">
                        <i class="fas <?php echo $category['icon']; ?>"></i>
                        <h3><?php echo $lang === 'ar' ? $category['name_ar'] : $category['name_en']; ?></h3>
                        <p><?php echo $lang === 'ar' ? 'تصفح المنتجات' : 'Browse Products'; ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section bg-dark-gray">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><?php echo t('featured_products'); ?></h2>
            <p><?php echo $lang === 'ar' ? 'أحدث وأفضل منتجاتنا' : 'Our latest and best products'; ?></p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featured_products as $index => $product): ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="product-card">
                    <div class="product-image">
                        <?php if ($product['image']): ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $product['image']; ?>" 
                             alt="<?php echo $lang === 'ar' ? $product['name_ar'] : $product['name_en']; ?>">
                        <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" 
                             alt="<?php echo $lang === 'ar' ? $product['name_ar'] : $product['name_en']; ?>">
                        <?php endif; ?>
                        
                        <?php if ($product['featured']): ?>
                        <span class="product-badge"><?php echo $lang === 'ar' ? 'مميز' : 'Featured'; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-body">
                        <h4><?php echo $lang === 'ar' ? $product['name_ar'] : $product['name_en']; ?></h4>
                        <p><?php echo $lang === 'ar' ? mb_substr($product['description_ar'], 0, 100) : mb_substr($product['description_en'], 0, 100); ?>...</p>
                        <div class="product-price">
                            <?php echo formatPrice($product['price'], $product['max_price']); ?>
                        </div>
                    </div>
                    
                    <div class="product-footer">
                        <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-gold w-100">
                            <?php echo t('buy_now'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-outline-gold">
                <?php echo t('all_products'); ?>
                <i class="fas fa-arrow-<?php echo $is_rtl ? 'left' : 'right'; ?> ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Customer Reviews Section -->
<?php if (!empty($reviews)): ?>
<section class="section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><?php echo t('customer_reviews'); ?></h2>
            <p><?php echo $lang === 'ar' ? 'ماذا يقول عملاؤنا' : 'What our customers say'; ?></p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($reviews as $index => $review): ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="review-card">
                    <div class="review-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="review-text">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                    <p class="review-author">
                        <strong><?php echo htmlspecialchars($review['name']); ?></strong>
                        <br>
                        <small class="text-light-gray">
                            <?php echo $lang === 'ar' ? $review['name_ar'] : $review['name_en']; ?>
                        </small>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Contact CTA Section -->
<section class="section bg-dark-gray">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8" data-aos="fade-right">
                <h2 class="mb-3"><?php echo t('get_in_touch'); ?></h2>
                <p class="text-light-gray mb-4">
                    <?php echo $lang === 'ar' ? 'لديك أسئلة؟ نحن هنا للمساعدة. تواصل معنا عبر واتساب أو املأ نموذج الاتصال.' : 'Have questions? We\'re here to help. Contact us via WhatsApp or fill out the contact form.'; ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end" data-aos="fade-left">
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-gold me-2 mb-2">
                    <?php echo t('contact'); ?>
                </a>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('whatsapp_number')); ?>" 
                   class="btn btn-outline-gold mb-2" target="_blank">
                    <i class="fab fa-whatsapp me-2"></i>
                    WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
