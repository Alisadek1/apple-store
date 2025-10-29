<?php
require_once __DIR__ . '/includes/header.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    redirect(SITE_URL . '/shop.php');
}

$db = getDB();

// Get product details
$stmt = $db->prepare("SELECT p.*, c.name_en as cat_name_en, c.name_ar as cat_name_ar 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect(SITE_URL . '/shop.php');
}

// Get product reviews
$reviews_stmt = $db->prepare("SELECT r.*, u.name 
                               FROM reviews r 
                               JOIN users u ON r.user_id = u.id 
                               WHERE r.product_id = ? AND r.approved = 1 
                               ORDER BY r.created_at DESC");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = $total_rating / count($reviews);
}

// Get related products
$related_stmt = $db->prepare("SELECT * FROM products 
                               WHERE category_id = ? AND id != ? 
                               ORDER BY RAND() LIMIT 4");
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll();

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $buyer_name = sanitize($_POST['buyer_name'] ?? '');
    $buyer_phone = sanitize($_POST['buyer_phone'] ?? '');
    $buyer_email = sanitize($_POST['buyer_email'] ?? '');
    $governorate = sanitize($_POST['governorate'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $payment_type = sanitize($_POST['payment_type'] ?? 'full');
    $notes = sanitize($_POST['notes'] ?? '');
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    
    if (empty($buyer_name) || empty($buyer_phone) || empty($governorate) || empty($address)) {
        setFlash('error', t('required_fields'));
    } else {
        // Calculate total
        $total = $product['price'] * $quantity;
        
        // Get user_id if logged in
        $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
        
        // Insert order
        $order_stmt = $db->prepare("INSERT INTO orders (user_id, buyer_name, buyer_phone, buyer_email, governorate, address, status, total, payment_type, notes) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        $order_stmt->execute([$user_id, $buyer_name, $buyer_phone, $buyer_email, $governorate, $address, $total, $payment_type, $notes]);
        
        $order_id = $db->lastInsertId();
        
        // Insert order items
        $item_stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) 
                                    VALUES (?, ?, ?, ?, ?)");
        $product_name = $lang === 'ar' ? $product['name_ar'] : $product['name_en'];
        $item_stmt->execute([$order_id, $product_id, $product_name, $quantity, $product['price']]);
        
        // Generate WhatsApp link
        $whatsapp_link = generateWhatsAppLink($order_id);
        
        if ($whatsapp_link) {
            setFlash('success', t('order_success'));
            redirect($whatsapp_link);
        } else {
            setFlash('success', t('order_success'));
            redirect(SITE_URL . '/index.php');
        }
    }
}
?>

<section class="section" style="margin-top: 100px;">
    <div class="container">
        <!-- Product Details -->
        <div class="row mb-5">
            <div class="col-lg-6 mb-4" data-aos="fade-right">
                <div class="product-image-large">
                    <?php if ($product['image']): ?>
                    <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $product['image']; ?>" 
                         alt="<?php echo $lang === 'ar' ? $product['name_ar'] : $product['name_en']; ?>"
                         class="img-fluid rounded" style="width: 100%; border: 2px solid var(--gold);">
                    <?php else: ?>
                    <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" 
                         alt="<?php echo $lang === 'ar' ? $product['name_ar'] : $product['name_en']; ?>"
                         class="img-fluid rounded" style="width: 100%; border: 2px solid var(--gold);">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-6" data-aos="fade-left">
                <div class="product-details-content">
                    <h1 class="mb-3"><?php echo $lang === 'ar' ? $product['name_ar'] : $product['name_en']; ?></h1>
                    
                    <div class="mb-3">
                        <span class="badge bg-dark-gray border border-gold text-gold px-3 py-2">
                            <?php echo $lang === 'ar' ? $product['cat_name_ar'] : $product['cat_name_en']; ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($reviews)): ?>
                    <div class="mb-3">
                        <div class="review-stars d-inline-block">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= round($avg_rating) ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-light-gray ms-2">
                            (<?php echo count($reviews); ?> <?php echo $lang === 'ar' ? 'تقييم' : 'reviews'; ?>)
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="product-price mb-4" style="font-size: 2.5rem;">
                        <?php echo formatPrice($product['price'], $product['max_price']); ?>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-light-gray" style="font-size: 1.1rem; line-height: 1.8;">
                            <?php echo $lang === 'ar' ? $product['description_ar'] : $product['description_en']; ?>
                        </p>
                    </div>
                    
                    <?php if ($product['stock'] > 0): ?>
                    <div class="mb-3">
                        <span class="badge bg-success px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i>
                            <?php echo t('in_stock'); ?>
                        </span>
                    </div>
                    
                    <button type="button" class="btn btn-gold btn-lg" data-bs-toggle="modal" data-bs-target="#orderModal">
                        <i class="fab fa-whatsapp me-2"></i>
                        <?php echo t('order_now'); ?>
                    </button>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo t('out_of_stock'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <?php if (!empty($reviews)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="mb-4" data-aos="fade-up"><?php echo t('customer_reviews'); ?></h2>
                <div class="row g-4">
                    <?php foreach ($reviews as $index => $review): ?>
                    <div class="col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
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
                                <small class="text-light-gray"><?php echo formatDate($review['created_at']); ?></small>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4" data-aos="fade-up"><?php echo $lang === 'ar' ? 'منتجات ذات صلة' : 'Related Products'; ?></h2>
                <div class="row g-4">
                    <?php foreach ($related_products as $index => $rel_product): ?>
                    <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($rel_product['image']): ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $rel_product['image']; ?>" 
                                     alt="<?php echo $lang === 'ar' ? $rel_product['name_ar'] : $rel_product['name_en']; ?>">
                                <?php else: ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" 
                                     alt="<?php echo $lang === 'ar' ? $rel_product['name_ar'] : $rel_product['name_en']; ?>">
                                <?php endif; ?>
                            </div>
                            <div class="product-body">
                                <h4><?php echo $lang === 'ar' ? $rel_product['name_ar'] : $rel_product['name_en']; ?></h4>
                                <div class="product-price">
                                    <?php echo formatPrice($rel_product['price'], $rel_product['max_price']); ?>
                                </div>
                            </div>
                            <div class="product-footer">
                                <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $rel_product['id']; ?>" 
                                   class="btn btn-gold w-100">
                                    <?php echo t('buy_now'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark-gray border-gold">
            <div class="modal-header border-gold">
                <h5 class="modal-title text-gold"><?php echo t('complete_order'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="buyer_name" class="form-label"><?php echo t('full_name'); ?> *</label>
                            <input type="text" class="form-control" id="buyer_name" name="buyer_name" 
                                   value="<?php echo isLoggedIn() ? $_SESSION['user_name'] : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="buyer_phone" class="form-label"><?php echo t('phone'); ?> *</label>
                            <input type="tel" class="form-control" id="buyer_phone" name="buyer_phone" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="buyer_email" class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" id="buyer_email" name="buyer_email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="governorate" class="form-label"><?php echo t('governorate'); ?> *</label>
                            <select class="form-control" id="governorate" name="governorate" 
                                    data-local="<?php echo getSetting('local_governorate'); ?>" required>
                                <option value="">-- <?php echo $lang === 'ar' ? 'اختر' : 'Select'; ?> --</option>
                                <option value="Cairo"><?php echo $lang === 'ar' ? 'القاهرة' : 'Cairo'; ?></option>
                                <option value="Giza"><?php echo $lang === 'ar' ? 'الجيزة' : 'Giza'; ?></option>
                                <option value="Alexandria"><?php echo $lang === 'ar' ? 'الإسكندرية' : 'Alexandria'; ?></option>
                                <option value="Other"><?php echo $lang === 'ar' ? 'أخرى' : 'Other'; ?></option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label"><?php echo t('delivery_address'); ?> *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label"><?php echo $lang === 'ar' ? 'الكمية' : 'Quantity'; ?></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo t('payment_type'); ?> *</label>
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_type" id="payment_type_deposit" value="deposit">
                                    <label class="form-check-label text-light-gray" for="payment_type_deposit">
                                        <?php echo t('deposit'); ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_type" id="payment_type_full" value="full" checked>
                                    <label class="form-check-label text-light-gray" for="payment_type_full">
                                        <?php echo t('full_payment'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="notes" class="form-label"><?php echo t('order_notes'); ?></label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                        <?php echo t('cancel'); ?>
                    </button>
                    <button type="submit" name="place_order" class="btn btn-gold">
                        <i class="fab fa-whatsapp me-2"></i>
                        <?php echo t('place_order'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
