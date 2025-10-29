<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlash('success', $lang === 'ar' ? 'تم حذف الفئة بنجاح' : 'Category deleted successfully');
    }
    redirect(ADMIN_URL . '/categories.php');
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name_en = sanitize($_POST['name_en']);
    $name_ar = sanitize($_POST['name_ar']);
    $icon = sanitize($_POST['icon']);
    
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE categories SET name_en=?, name_ar=?, icon=? WHERE id=?");
        $stmt->execute([$name_en, $name_ar, $icon, $id]);
        setFlash('success', $lang === 'ar' ? 'تم تحديث الفئة بنجاح' : 'Category updated successfully');
    } else {
        $stmt = $db->prepare("INSERT INTO categories (name_en, name_ar, icon) VALUES (?, ?, ?)");
        $stmt->execute([$name_en, $name_ar, $icon]);
        setFlash('success', $lang === 'ar' ? 'تم إضافة الفئة بنجاح' : 'Category added successfully');
    }
    
    redirect(ADMIN_URL . '/categories.php');
}

// Get all categories
$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count 
                          FROM categories c 
                          LEFT JOIN products p ON c.id = p.category_id 
                          GROUP BY c.id 
                          ORDER BY c.id")->fetchAll();
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><?php echo t('manage_categories'); ?></h1>
            <p><?php echo $lang === 'ar' ? 'إدارة فئات المنتجات' : 'Manage product categories'; ?></p>
        </div>
        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>
            <?php echo t('add_new'); ?>
        </button>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($categories as $category): ?>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas <?php echo $category['icon']; ?>"></i>
            </div>
            <h3><?php echo $lang === 'ar' ? $category['name_ar'] : $category['name_en']; ?></h3>
            <p><?php echo $category['product_count']; ?> <?php echo $lang === 'ar' ? 'منتج' : 'products'; ?></p>
            <div class="mt-3">
                <button class="btn btn-sm btn-outline-gold me-2" 
                        onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                    <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                </button>
                <?php if ($category['product_count'] == 0): ?>
                <a href="?delete=<?php echo $category['id']; ?>" 
                   class="btn btn-sm btn-danger btn-delete">
                    <i class="fas fa-trash"></i> <?php echo t('delete'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark-gray border-gold">
            <div class="modal-header border-gold">
                <h5 class="modal-title text-gold" id="modalTitle">
                    <?php echo $lang === 'ar' ? 'إضافة فئة' : 'Add Category'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="category_id">
                    
                    <div class="mb-3">
                        <label for="name_en" class="form-label"><?php echo $lang === 'ar' ? 'الاسم (إنجليزي)' : 'Name (English)'; ?> *</label>
                        <input type="text" class="form-control" id="name_en" name="name_en" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name_ar" class="form-label"><?php echo $lang === 'ar' ? 'الاسم (عربي)' : 'Name (Arabic)'; ?> *</label>
                        <input type="text" class="form-control" id="name_ar" name="name_ar" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label"><?php echo $lang === 'ar' ? 'الأيقونة (Font Awesome)' : 'Icon (Font Awesome)'; ?> *</label>
                        <input type="text" class="form-control" id="icon" name="icon" placeholder="fa-mobile-alt" required>
                        <small class="text-light-gray">
                            <?php echo $lang === 'ar' ? 'مثال: fa-mobile-alt, fa-laptop, fa-tablet-alt' : 'Example: fa-mobile-alt, fa-laptop, fa-tablet-alt'; ?>
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                        <?php echo t('cancel'); ?>
                    </button>
                    <button type="submit" class="btn btn-gold">
                        <?php echo t('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('category_id').value = '';
    document.getElementById('modalTitle').textContent = '<?php echo $lang === 'ar' ? 'إضافة فئة' : 'Add Category'; ?>';
    document.querySelector('#categoryModal form').reset();
}

function editCategory(category) {
    document.getElementById('category_id').value = category.id;
    document.getElementById('name_en').value = category.name_en;
    document.getElementById('name_ar').value = category.name_ar;
    document.getElementById('icon').value = category.icon;
    document.getElementById('modalTitle').textContent = '<?php echo $lang === 'ar' ? 'تعديل فئة' : 'Edit Category'; ?>';
    
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
