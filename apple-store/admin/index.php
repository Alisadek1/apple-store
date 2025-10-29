<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Get statistics
$stats = [
    'total_sales' => $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
    'today_sales' => $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn(),
    'total_orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'guest_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE user_id IS NULL")->fetchColumn(),
    'new_contacts' => $db->query("SELECT COUNT(*) FROM contacts WHERE status = 'new'")->fetchColumn(),
];

// Get recent orders
$recent_orders = $db->query("SELECT o.*, u.name as user_name 
                             FROM orders o 
                             LEFT JOIN users u ON o.user_id = u.id 
                             ORDER BY o.created_at DESC 
                             LIMIT 10")->fetchAll();

// Get top products
$top_products = $db->query("SELECT p.name_en, p.name_ar, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold
                            FROM products p
                            JOIN order_items oi ON p.id = oi.product_id
                            JOIN orders o ON oi.order_id = o.id
                            WHERE o.status != 'cancelled'
                            GROUP BY p.id
                            ORDER BY total_sold DESC
                            LIMIT 5")->fetchAll();

// Get sales by category
$sales_by_category = $db->query("SELECT c.name_en, c.name_ar, COUNT(oi.id) as order_count
                                 FROM categories c
                                 JOIN products p ON c.id = p.category_id
                                 JOIN order_items oi ON p.id = oi.product_id
                                 JOIN orders o ON oi.order_id = o.id
                                 WHERE o.status != 'cancelled'
                                 GROUP BY c.id
                                 ORDER BY order_count DESC")->fetchAll();
?>

<div class="page-header">
    <h1><?php echo t('dashboard'); ?></h1>
    <p><?php echo $lang === 'ar' ? 'نظرة عامة على المتجر' : 'Store overview'; ?></p>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h3><?php echo number_format($stats['total_sales'], 0); ?> <?php echo $lang === 'ar' ? 'ج.م' : 'EGP'; ?></h3>
            <p><?php echo t('total_sales'); ?></p>
            <small class="text-gold">
                <?php echo $lang === 'ar' ? 'اليوم:' : 'Today:'; ?> 
                <?php echo number_format($stats['today_sales'], 0); ?> <?php echo $lang === 'ar' ? 'ج.م' : 'EGP'; ?>
            </small>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3><?php echo $stats['total_orders']; ?></h3>
            <p><?php echo t('total_orders'); ?></p>
            <small class="text-gold">
                <?php echo t('pending_orders'); ?>: <?php echo $stats['pending_orders']; ?>
            </small>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3><?php echo $stats['total_users']; ?></h3>
            <p><?php echo t('total_users'); ?></p>
            <small class="text-gold">
                <?php echo $lang === 'ar' ? 'ضيوف:' : 'Guests:'; ?> <?php echo $stats['guest_orders']; ?>
            </small>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <h3><?php echo $stats['total_products']; ?></h3>
            <p><?php echo t('manage_products'); ?></p>
            <small class="text-gold">
                <?php echo $lang === 'ar' ? 'رسائل جديدة:' : 'New messages:'; ?> <?php echo $stats['new_contacts']; ?>
            </small>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="chart-container">
            <h4 class="text-gold mb-3"><?php echo $lang === 'ar' ? 'المبيعات حسب الفئة' : 'Sales by Category'; ?></h4>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="chart-container">
            <h4 class="text-gold mb-3"><?php echo $lang === 'ar' ? 'أفضل المنتجات' : 'Top Products'; ?></h4>
            <canvas id="topProductsChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row">
    <div class="col-12">
        <div class="admin-table">
            <div class="p-3 border-bottom border-gold">
                <h4 class="text-gold mb-0"><?php echo $lang === 'ar' ? 'الطلبات الأخيرة' : 'Recent Orders'; ?></h4>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo $lang === 'ar' ? 'رقم الطلب' : 'Order ID'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'العميل' : 'Customer'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'الهاتف' : 'Phone'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('date'); ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <?php 
                                if ($order['user_id']) {
                                    echo htmlspecialchars($order['user_name']);
                                } else {
                                    echo htmlspecialchars($order['buyer_name']) . ' <span class="badge bg-secondary">' . ($lang === 'ar' ? 'ضيف' : 'Guest') . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['buyer_phone']); ?></td>
                            <td class="text-gold"><?php echo formatPrice($order['total']); ?></td>
                            <td>
                                <span class="badge status-badge badge-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => $lang === 'ar' ? 'معلق' : 'Pending',
                                        'confirmed' => $lang === 'ar' ? 'مؤكد' : 'Confirmed',
                                        'shipped' => $lang === 'ar' ? 'تم الشحن' : 'Shipped',
                                        'completed' => $lang === 'ar' ? 'مكتمل' : 'Completed',
                                        'cancelled' => $lang === 'ar' ? 'ملغي' : 'Cancelled'
                                    ];
                                    echo $status_labels[$order['status']];
                                    ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/orders.php?view=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-outline-gold btn-action">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Category Sales Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($cat) use ($lang) { 
            return "'" . ($lang === 'ar' ? $cat['name_ar'] : $cat['name_en']) . "'"; 
        }, $sales_by_category)); ?>],
        datasets: [{
            label: '<?php echo $lang === 'ar' ? 'عدد الطلبات' : 'Order Count'; ?>',
            data: [<?php echo implode(',', array_column($sales_by_category, 'order_count')); ?>],
            backgroundColor: 'rgba(212, 175, 55, 0.8)',
            borderColor: 'rgba(212, 175, 55, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#D4AF37'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#CCCCCC'
                },
                grid: {
                    color: 'rgba(212, 175, 55, 0.1)'
                }
            },
            x: {
                ticks: {
                    color: '#CCCCCC'
                },
                grid: {
                    color: 'rgba(212, 175, 55, 0.1)'
                }
            }
        }
    }
});

// Top Products Chart
const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
new Chart(topProductsCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($prod) use ($lang) { 
            return "'" . ($lang === 'ar' ? $prod['name_ar'] : $prod['name_en']) . "'"; 
        }, $top_products)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($top_products, 'total_sold')); ?>],
            backgroundColor: [
                'rgba(212, 175, 55, 0.8)',
                'rgba(184, 148, 31, 0.8)',
                'rgba(255, 215, 0, 0.8)',
                'rgba(218, 165, 32, 0.8)',
                'rgba(238, 232, 170, 0.8)'
            ],
            borderColor: '#000000',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#D4AF37',
                    padding: 10
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
