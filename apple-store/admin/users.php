<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlash('success', $lang === 'ar' ? 'تم حذف المستخدم' : 'User deleted');
        }
    }
    redirect(ADMIN_URL . '/users.php');
}

// Get all users
$users = $db->query("SELECT u.*, 
                     COUNT(DISTINCT o.id) as order_count,
                     COALESCE(SUM(o.total), 0) as total_spent
                     FROM users u
                     LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
                     GROUP BY u.id
                     ORDER BY u.created_at DESC")->fetchAll();
?>

<div class="page-header">
    <h1><?php echo t('manage_users'); ?></h1>
    <p><?php echo $lang === 'ar' ? 'إدارة المستخدمين والعملاء' : 'Manage users and customers'; ?></p>
</div>

<div class="admin-table">
    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></th>
                    <th><?php echo t('email'); ?></th>
                    <th><?php echo t('phone'); ?></th>
                    <th><?php echo $lang === 'ar' ? 'الدور' : 'Role'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'النقاط' : 'Points'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الطلبات' : 'Orders'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'إجمالي الإنفاق' : 'Total Spent'; ?></th>
                    <th><?php echo t('date'); ?></th>
                    <th><?php echo t('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge bg-gold text-black"><?php echo $lang === 'ar' ? 'مدير' : 'Admin'; ?></span>
                        <?php else: ?>
                        <span class="badge bg-secondary"><?php echo $lang === 'ar' ? 'عميل' : 'Customer'; ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $user['points']; ?></td>
                    <td><?php echo $user['order_count']; ?></td>
                    <td class="text-gold"><?php echo formatPrice($user['total_spent']); ?></td>
                    <td><?php echo formatDate($user['created_at']); ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="?delete=<?php echo $user['id']; ?>" 
                           class="btn btn-sm btn-danger btn-action btn-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
