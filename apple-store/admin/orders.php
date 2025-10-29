<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);
    
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $order_id])) {
        setFlash('success', $lang === 'ar' ? 'تم تحديث حالة الطلب' : 'Order status updated');
    }
    redirect(ADMIN_URL . '/orders.php');
}

// Handle assign user
if (isset($_POST['assign_user'])) {
    $order_id = (int)$_POST['order_id'];
    $user_id = (int)$_POST['user_id'];
    
    $stmt = $db->prepare("UPDATE orders SET user_id = ? WHERE id = ?");
    if ($stmt->execute([$user_id, $order_id])) {
        setFlash('success', $lang === 'ar' ? 'تم تعيين الطلب للمستخدم' : 'Order assigned to user');
    }
    redirect(ADMIN_URL . '/orders.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlash('success', $lang === 'ar' ? 'تم حذف الطلب' : 'Order deleted');
    }
    redirect(ADMIN_URL . '/orders.php');
}

// Get all orders
$orders = $db->query("SELECT o.*, u.name as user_name, u.email as user_email
                      FROM orders o
                      LEFT JOIN users u ON o.user_id = u.id
                      ORDER BY o.created_at DESC")->fetchAll();

// Get users for assignment
$users = $db->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name")->fetchAll();

// Get order details if viewing
$view_order = null;
$order_items = [];
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    $stmt = $db->prepare("SELECT o.*, u.name as user_name, u.email as user_email
                          FROM orders o
                          LEFT JOIN users u ON o.user_id = u.id
                          WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $view_order = $stmt->fetch();
    
    if ($view_order) {
        $items_stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll();
    }
}
?>

<div class="page-header">
    <h1><?php echo t('manage_orders'); ?></h1>
    <p><?php echo $lang === 'ar' ? 'إدارة طلبات العملاء' : 'Manage customer orders'; ?></p>
</div>

<!-- Orders Table -->
<div class="admin-table">
    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'رقم الطلب' : 'Order ID'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'العميل' : 'Customer'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الهاتف' : 'Phone'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'المحافظة' : 'Governorate'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'نوع الدفع' : 'Payment'; ?></th>
                    <th><?php echo t('status'); ?></th>
                    <th><?php echo t('date'); ?></th>
                    <th><?php echo t('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td>
                        <?php 
                        if ($order['user_id']) {
                            echo '<i class="fas fa-user text-gold"></i> ' . htmlspecialchars($order['user_name']);
                        } else {
                            echo '<i class="fas fa-user-slash text-secondary"></i> ' . htmlspecialchars($order['buyer_name']) . 
                                 ' <span class="badge bg-secondary">' . ($lang === 'ar' ? 'ضيف' : 'Guest') . '</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($order['buyer_phone']); ?></td>
                    <td><?php echo htmlspecialchars($order['governorate']); ?></td>
                    <td class="text-gold"><?php echo formatPrice($order['total']); ?></td>
                    <td>
                        <?php 
                        echo $order['payment_type'] === 'deposit' ? 
                            ($lang === 'ar' ? 'عربون' : 'Deposit') : 
                            ($lang === 'ar' ? 'كامل' : 'Full');
                        ?>
                    </td>
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
                        <a href="?view=<?php echo $order['id']; ?>" 
                           class="btn btn-sm btn-outline-gold btn-action">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo generateWhatsAppLink($order['id']); ?>" 
                           class="btn btn-sm btn-success btn-action" target="_blank">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="?delete=<?php echo $order['id']; ?>" 
                           class="btn btn-sm btn-danger btn-action btn-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($view_order): ?>
<script>
window.location.hash = 'orderModal';
</script>
<div class="modal fade show d-block" id="orderModal" tabindex="-1" style="background: rgba(0,0,0,0.8);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark-gray border-gold">
            <div class="modal-header border-gold">
                <h5 class="modal-title text-gold">
                    <?php echo $lang === 'ar' ? 'تفاصيل الطلب' : 'Order Details'; ?> #<?php echo $view_order['id']; ?>
                </h5>
                <a href="<?php echo ADMIN_URL; ?>/orders.php" class="btn-close btn-close-white"></a>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-gold"><?php echo $lang === 'ar' ? 'معلومات العميل' : 'Customer Information'; ?></h6>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'الاسم:' : 'Name:'; ?></strong> <?php echo htmlspecialchars($view_order['buyer_name']); ?></p>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'الهاتف:' : 'Phone:'; ?></strong> <?php echo htmlspecialchars($view_order['buyer_phone']); ?></p>
                        <?php if ($view_order['buyer_email']): ?>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'البريد:' : 'Email:'; ?></strong> <?php echo htmlspecialchars($view_order['buyer_email']); ?></p>
                        <?php endif; ?>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'المحافظة:' : 'Governorate:'; ?></strong> <?php echo htmlspecialchars($view_order['governorate']); ?></p>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'العنوان:' : 'Address:'; ?></strong> <?php echo htmlspecialchars($view_order['address']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-gold"><?php echo $lang === 'ar' ? 'معلومات الطلب' : 'Order Information'; ?></h6>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'التاريخ:' : 'Date:'; ?></strong> <?php echo formatDate($view_order['created_at']); ?></p>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'الإجمالي:' : 'Total:'; ?></strong> <span class="text-gold"><?php echo formatPrice($view_order['total']); ?></span></p>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'نوع الدفع:' : 'Payment:'; ?></strong> <?php echo $view_order['payment_type'] === 'deposit' ? ($lang === 'ar' ? 'عربون' : 'Deposit') : ($lang === 'ar' ? 'كامل' : 'Full'); ?></p>
                        <?php if ($view_order['notes']): ?>
                        <p class="text-light-gray mb-1"><strong><?php echo $lang === 'ar' ? 'ملاحظات:' : 'Notes:'; ?></strong> <?php echo htmlspecialchars($view_order['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h6 class="text-gold mb-3"><?php echo $lang === 'ar' ? 'المنتجات' : 'Products'; ?></h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><?php echo $lang === 'ar' ? 'المنتج' : 'Product'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'الكمية' : 'Quantity'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'السعر' : 'Price'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td class="text-gold"><?php echo formatPrice($item['price']); ?></td>
                            <td class="text-gold"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <hr class="border-gold">
                
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                            <label class="form-label text-gold"><?php echo $lang === 'ar' ? 'تحديث الحالة' : 'Update Status'; ?></label>
                            <div class="input-group">
                                <select class="form-control" name="status" required>
                                    <option value="pending" <?php echo $view_order['status'] === 'pending' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'معلق' : 'Pending'; ?></option>
                                    <option value="confirmed" <?php echo $view_order['status'] === 'confirmed' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مؤكد' : 'Confirmed'; ?></option>
                                    <option value="shipped" <?php echo $view_order['status'] === 'shipped' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'تم الشحن' : 'Shipped'; ?></option>
                                    <option value="completed" <?php echo $view_order['status'] === 'completed' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></option>
                                    <option value="cancelled" <?php echo $view_order['status'] === 'cancelled' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'ملغي' : 'Cancelled'; ?></option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-gold"><?php echo t('save'); ?></button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (!$view_order['user_id']): ?>
                    <div class="col-md-6">
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                            <label class="form-label text-gold"><?php echo $lang === 'ar' ? 'تعيين لمستخدم' : 'Assign to User'; ?></label>
                            <div class="input-group">
                                <select class="form-control" name="user_id" required>
                                    <option value="">-- <?php echo $lang === 'ar' ? 'اختر مستخدم' : 'Select User'; ?> --</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_user" class="btn btn-gold"><?php echo t('save'); ?></button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer border-gold">
                <a href="<?php echo generateWhatsAppLink($view_order['id']); ?>" class="btn btn-success" target="_blank">
                    <i class="fab fa-whatsapp me-2"></i>
                    <?php echo $lang === 'ar' ? 'فتح في واتساب' : 'Open in WhatsApp'; ?>
                </a>
                <a href="<?php echo ADMIN_URL; ?>/orders.php" class="btn btn-outline-gold">
                    <?php echo $lang === 'ar' ? 'إغلاق' : 'Close'; ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
