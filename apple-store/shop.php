<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Get filters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = $_GET['sort'] ?? 'newest';
$search = sanitize($_GET['search'] ?? '');

// Build query
$where = ["1=1"];
$params = [];

if ($category_filter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($min_price > 0) {
    $where[] = "p.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where[] = "p.price <= ?";
    $params[] = $max_price;
}

if (!empty($search)) {
    $where[] = "(p.name_en LIKE ? OR p.name_ar LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_clause = implode(" AND ", $where);

// Sort
$order_by = "p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "p.price ASC";
        break;
    case 'price_high':
        $order_by = "p.price DESC";
        break;
    case 'name':
        $order_by = $lang === 'ar' ? "p.name_ar ASC" : "p.name_en ASC";
        break;
}

// Get total count
$count_stmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE {$where_clause}");
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pagination = paginate($total_products, PRODUCTS_PER_PAGE, $page);

// Get products
$stmt = $db->prepare("SELECT p.*, c.name_en as cat_name_en, c.name_ar as cat_name_ar 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE {$where_clause} 
                      ORDER BY {$order_by} 
                      LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
?>

<section class="section" style="margin-top: 100px;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><?php echo t('shop'); ?></h2>
            <p><?php echo $lang === 'ar' ? 'تصفح جميع منتجاتنا' : 'Browse all our products'; ?></p>
        </div>
        
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filters-sidebar" data-aos="fade-right">
                    <h4 class="text-gold mb-4"><?php echo t('filter_by'); ?></h4>
                    
                    <!-- Search -->
                    <div class="filter-group">
                        <h5><?php echo $lang === 'ar' ? 'بحث' : 'Search'; ?></h5>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="<?php echo $lang === 'ar' ? 'ابحث عن منتج...' : 'Search for a product...'; ?>"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <h5><?php echo t('category'); ?></h5>
                        <?php foreach ($categories as $cat): ?>
                        <div class="filter-option">
                            <input type="radio" name="category" id="cat_<?php echo $cat['id']; ?>" 
                                   value="<?php echo $cat['id']; ?>"
                                   <?php echo $category_filter == $cat['id'] ? 'checked' : ''; ?>>
                            <label for="cat_<?php echo $cat['id']; ?>">
                                <?php echo $lang === 'ar' ? $cat['name_ar'] : $cat['name_en']; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <div class="filter-option">
                            <input type="radio" name="category" id="cat_all" value="0"
                                   <?php echo $category_filter == 0 ? 'checked' : ''; ?>>
                            <label for="cat_all"><?php echo $lang === 'ar' ? 'الكل' : 'All'; ?></label>
                        </div>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="filter-group">
                        <h5><?php echo t('price_range'); ?></h5>
                        <div class="mb-2">
                            <input type="number" class="form-control" id="minPrice" 
                                   placeholder="<?php echo $lang === 'ar' ? 'من' : 'Min'; ?>"
                                   value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                        </div>
                        <div>
                            <input type="number" class="form-control" id="maxPrice" 
                                   placeholder="<?php echo $lang === 'ar' ? 'إلى' : 'Max'; ?>"
                                   value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Sort -->
                    <div class="filter-group">
                        <h5><?php echo t('sort_by'); ?></h5>
                        <select class="form-control" id="sortBy">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                                <?php echo t('newest'); ?>
                            </option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>
                                <?php echo t('price_low_high'); ?>
                            </option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>
                                <?php echo t('price_high_low'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <button class="btn btn-gold w-100 mb-2" id="applyFilters">
                        <?php echo t('apply_filters'); ?>
                    </button>
                    <button class="btn btn-outline-gold w-100" id="clearFilters">
                        <?php echo t('clear_filters'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <?php if (empty($products)): ?>
                <div class="text-center py-5" data-aos="fade-up">
                    <i class="fas fa-box-open fa-5x text-gold mb-3"></i>
                    <h3><?php echo $lang === 'ar' ? 'لا توجد منتجات' : 'No products found'; ?></h3>
                    <p class="text-light-gray">
                        <?php echo $lang === 'ar' ? 'جرب تغيير الفلاتر' : 'Try changing the filters'; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $index => $product): ?>
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
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
                                <p><?php echo $lang === 'ar' ? mb_substr($product['description_ar'], 0, 80) : mb_substr($product['description_en'], 0, 80); ?>...</p>
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
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="mt-5" data-aos="fade-up">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link bg-dark-gray border-gold text-<?php echo $i === $pagination['current_page'] ? 'black' : 'gold'; ?>" 
                               href="?page=<?php echo $i; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $sort !== 'newest' ? '&sort=' . $sort : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
