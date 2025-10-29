<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle status update
if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $stmt = $db->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlash('success', $lang === 'ar' ? 'تم تحديث الحالة' : 'Status updated');
    }
    redirect(ADMIN_URL . '/contacts.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlash('success', $lang === 'ar' ? 'تم حذف الرسالة' : 'Message deleted');
    }
    redirect(ADMIN_URL . '/contacts.php');
}

// Get all contacts
$contacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
?>

<div class="page-header">
    <h1><?php echo t('manage_contacts'); ?></h1>
    <p><?php echo $lang === 'ar' ? 'إدارة رسائل الاتصال' : 'Manage contact messages'; ?></p>
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
                    <th><?php echo $lang === 'ar' ? 'الرسالة' : 'Message'; ?></th>
                    <th><?php echo t('status'); ?></th>
                    <th><?php echo t('date'); ?></th>
                    <th><?php echo t('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact): ?>
                <tr class="<?php echo $contact['status'] === 'new' ? 'table-warning' : ''; ?>">
                    <td><?php echo $contact['id']; ?></td>
                    <td><?php echo htmlspecialchars($contact['name']); ?></td>
                    <td>
                        <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="text-gold">
                            <?php echo htmlspecialchars($contact['email']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($contact['phone'] ?? '-'); ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-gold" 
                                data-bs-toggle="modal" 
                                data-bs-target="#messageModal<?php echo $contact['id']; ?>">
                            <i class="fas fa-eye"></i> <?php echo $lang === 'ar' ? 'عرض' : 'View'; ?>
                        </button>
                    </td>
                    <td>
                        <?php 
                        $status_labels = [
                            'new' => $lang === 'ar' ? 'جديد' : 'New',
                            'read' => $lang === 'ar' ? 'مقروء' : 'Read',
                            'replied' => $lang === 'ar' ? 'تم الرد' : 'Replied'
                        ];
                        $status_colors = [
                            'new' => 'warning',
                            'read' => 'info',
                            'replied' => 'success'
                        ];
                        ?>
                        <span class="badge bg-<?php echo $status_colors[$contact['status']]; ?>">
                            <?php echo $status_labels[$contact['status']]; ?>
                        </span>
                    </td>
                    <td><?php echo formatDate($contact['created_at']); ?></td>
                    <td>
                        <?php if ($contact['status'] === 'new'): ?>
                        <a href="?mark_read=<?php echo $contact['id']; ?>" 
                           class="btn btn-sm btn-info btn-action">
                            <i class="fas fa-check"></i>
                        </a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $contact['id']; ?>" 
                           class="btn btn-sm btn-danger btn-action btn-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                
                <!-- Message Modal -->
                <div class="modal fade" id="messageModal<?php echo $contact['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content bg-dark-gray border-gold">
                            <div class="modal-header border-gold">
                                <h5 class="modal-title text-gold">
                                    <?php echo $lang === 'ar' ? 'رسالة من' : 'Message from'; ?> 
                                    <?php echo htmlspecialchars($contact['name']); ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-light-gray">
                                    <strong><?php echo t('email'); ?>:</strong> 
                                    <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="text-gold">
                                        <?php echo htmlspecialchars($contact['email']); ?>
                                    </a>
                                </p>
                                <?php if ($contact['phone']): ?>
                                <p class="text-light-gray">
                                    <strong><?php echo t('phone'); ?>:</strong> 
                                    <?php echo htmlspecialchars($contact['phone']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="text-light-gray">
                                    <strong><?php echo t('date'); ?>:</strong> 
                                    <?php echo formatDate($contact['created_at']); ?>
                                </p>
                                <hr class="border-gold">
                                <p class="text-light-gray" style="white-space: pre-wrap;">
                                    <?php echo htmlspecialchars($contact['message']); ?>
                                </p>
                            </div>
                            <div class="modal-footer border-gold">
                                <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" 
                                   class="btn btn-gold">
                                    <i class="fas fa-reply me-2"></i>
                                    <?php echo $lang === 'ar' ? 'رد' : 'Reply'; ?>
                                </a>
                                <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                                    <?php echo $lang === 'ar' ? 'إغلاق' : 'Close'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
