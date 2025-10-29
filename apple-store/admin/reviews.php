<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle approve/reject
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $db->prepare("UPDATE reviews SET approved = 1 WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlash('success', $lang === 'ar' ? 'تم الموافقة على التقييم' : 'Review approved');
    }
    redirect(ADMIN_URL . '/reviews.php');
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlash('success', $lang === 'ar' ? 'تم رفض التقييم' : 'Review rejected');
    }
    redirect(ADMIN_URL . '/reviews.php');
}

// Get all reviews
$reviews = $db->query("SELECT r.*, u.name as user_name, p.name_en, p.name_ar
                       FROM reviews r
                       JOIN users u ON r.user_id = u.id
                       JOIN products p ON r.product_id = p.id
                       ORDER BY r.created_at DESC")->fetchAll();
?>

<div class="page-header">
    <h1><?php echo t('manage_reviews'); ?></h1>
    <p><?php echo $lang === 'ar' ? 'إدارة تقييمات المنتجات' : 'Manage product reviews'; ?></p>
</div>

<div class="admin-table">
    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo $lang === 'ar' ? 'المنتج' : 'Product'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'العميل' : 'Customer'; ?></th>
                    <th><?php echo t('rating'); ?></th>
                    <th><?php echo t('comment'); ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th><?php echo t('date'); ?></th>
                    <th><?php echo t('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?php echo $review['id']; ?></td>
                    <td><?php echo $lang === 'ar' ? $review['name_ar'] : $review['name_en']; ?></td>
                    <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                    <td>
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars(mb_substr($review['comment'], 0, 50)); ?>...</td>
                    <td>
                        <?php if ($review['approved']): ?>
                        <span class="badge bg-success"><?php echo $lang === 'ar' ? 'موافق عليه' : 'Approved'; ?></span>
                        <?php else: ?>
                        <span class="badge bg-warning text-black"><?php echo $lang === 'ar' ? 'معلق' : 'Pending'; ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatDate($review['created_at']); ?></td>
                    <td>
                        <?php if (!$review['approved']): ?>
                        <a href="?approve=<?php echo $review['id']; ?>" 
                           class="btn btn-sm btn-success btn-action">
                            <i class="fas fa-check"></i>
                        </a>
                        <?php endif; ?>
                        <a href="?reject=<?php echo $review['id']; ?>" 
                           class="btn btn-sm btn-danger btn-action btn-delete">
                            <i class="fas fa-times"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
