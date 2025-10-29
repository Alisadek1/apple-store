<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlash('success', $lang === 'ar' ? 'تم حذف المنتج بنجاح' : 'Product deleted successfully');
    } else {
        setFlash('error', t('error'));
    }
    redirect(ADMIN_URL . '/products.php');
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $category_id = (int)$_POST['category_id'];
    $name_en = sanitize($_POST['name_en']);
    $name_ar = sanitize($_POST['name_ar']);
    $description_en = sanitize($_POST['description_en']);
    $description_ar = sanitize($_POST['description_ar']);
    $price = (float)$_POST['price'];
    $max_price = !empty($_POST['max_price']) ? (float)$_POST['max_price'] : null;
    $stock = (int)$_POST['stock'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_result = uploadImage($_FILES['image'], 'products');
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
        }
    }
    
    if ($id > 0) {
        // Update
        if ($image) {
            $stmt = $db->prepare("UPDATE products SET category_id=?, name_en=?, name_ar=?, description_en=?, description_ar=?, price=?, max_price=?, image=?, stock=?, featured=? WHERE id=?");
            $stmt->execute([$category_id, $name_en, $name_ar, $description_en, $description_ar, $price, $max_price, $image, $stock, $featured, $id]);
        } else {
            $stmt = $db->prepare("UPDATE products SET category_id=?, name_en=?, name_ar=?, description_en=?, description_ar=?, price=?, max_price=?, stock=?, featured=? WHERE id=?");
            $stmt->execute([$category_id, $name_en, $name_ar, $description_en, $description_ar, $price, $max_price, $stock, $featured, $id]);
        }
        setFlash('success', $lang === 'ar' ? 'تم تحديث المنتج بنجاح' : 'Product updated successfully');
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO products (category_id, name_en, name_ar, description_en, description_ar, price, max_price, image, stock, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name_en, $name_ar, $description_en, $description_ar, $price, $max_price, $image, $stock, $featured]);
        setFlash('success', $lang === 'ar' ? 'تم إضافة المنتج بنجاح' : 'Product added successfully');
    }
    
    redirect(ADMIN_URL . '/products.php');
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $edit_product = $stmt->fetch();
}

// Get all products
$products = $db->query("SELECT p.*, c.name_en as cat_name_en, c.name_ar as cat_name_ar 
                        FROM products p 
                        JOIN categories c ON p.category_id = c.id 
                        ORDER BY p.created_at DESC")->fetchAll();

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><?php echo t('manage_products'); ?></h1>
            <p><?php echo $lang === 'ar' ? 'إدارة منتجات المتجر' : 'Manage store products'; ?></p>
        </div>
        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>
            <?php echo t('add_new'); ?>
        </button>
    </div>
</div>

<!-- Products Table -->
<div class="admin-table">
    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo $lang === 'ar' ? 'الصورة' : 'Image'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></th>
                    <th><?php echo t('category'); ?></th>
                    <th><?php echo t('price'); ?></th>
                    <th><?php echo $lang === 'ar' ? 'المخزون' : 'Stock'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'مميز' : 'Featured'; ?></th>
                    <th><?php echo t('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td>
                        <?php if ($product['image']): ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $product['image']; ?>" 
                             alt="<?php echo $product['name_en']; ?>" 
                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                        <?php else: ?>
                        <i class="fas fa-image fa-2x text-gold"></i>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $lang === 'ar' ? $product['name_ar'] : $product['name_en']; ?></td>
                    <td><?php echo $lang === 'ar' ? $product['cat_name_ar'] : $product['cat_name_en']; ?></td>
                    <td class="text-gold"><?php echo formatPrice($product['price'], $product['max_price']); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <?php if ($product['featured']): ?>
                        <span class="badge bg-gold text-black"><?php echo $lang === 'ar' ? 'نعم' : 'Yes'; ?></span>
                        <?php else: ?>
                        <span class="badge bg-secondary"><?php echo $lang === 'ar' ? 'لا' : 'No'; ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-gold btn-action" 
                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?php echo $product['id']; ?>" 
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

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark-gray border-gold">
            <div class="modal-header border-gold">
                <h5 class="modal-title text-gold" id="modalTitle">
                    <?php echo $lang === 'ar' ? 'إضافة منتج' : 'Add Product'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="product_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name_en" class="form-label"><?php echo $lang === 'ar' ? 'الاسم (إنجليزي)' : 'Name (English)'; ?> *</label>
                            <input type="text" class="form-control" id="name_en" name="name_en" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="name_ar" class="form-label"><?php echo $lang === 'ar' ? 'الاسم (عربي)' : 'Name (Arabic)'; ?> *</label>
                            <input type="text" class="form-control" id="name_ar" name="name_ar" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label"><?php echo t('category'); ?> *</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">-- <?php echo $lang === 'ar' ? 'اختر' : 'Select'; ?> --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo $lang === 'ar' ? $cat['name_ar'] : $cat['name_en']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="image" class="form-label"><?php echo $lang === 'ar' ? 'الصورة' : 'Image'; ?></label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label"><?php echo $lang === 'ar' ? 'السعر (أو الأدنى)' : 'Price (or Min)'; ?> *</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="max_price" class="form-label"><?php echo $lang === 'ar' ? 'السعر الأقصى' : 'Max Price'; ?></label>
                            <input type="number" class="form-control" id="max_price" name="max_price" step="0.01">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="stock" class="form-label"><?php echo $lang === 'ar' ? 'المخزون' : 'Stock'; ?> *</label>
                            <input type="number" class="form-control" id="stock" name="stock" value="0" required>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="description_en" class="form-label"><?php echo $lang === 'ar' ? 'الوصف (إنجليزي)' : 'Description (English)'; ?></label>
                            <textarea class="form-control" id="description_en" name="description_en" rows="3"></textarea>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="description_ar" class="form-label"><?php echo $lang === 'ar' ? 'الوصف (عربي)' : 'Description (Arabic)'; ?></label>
                            <textarea class="form-control" id="description_ar" name="description_ar" rows="3"></textarea>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="featured" name="featured">
                                <label class="form-check-label text-light-gray" for="featured">
                                    <?php echo $lang === 'ar' ? 'منتج مميز' : 'Featured Product'; ?>
                                </label>
                            </div>
                        </div>
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
    document.getElementById('product_id').value = '';
    document.getElementById('modalTitle').textContent = '<?php echo $lang === 'ar' ? 'إضافة منتج' : 'Add Product'; ?>';
    document.querySelector('#productModal form').reset();
}

function editProduct(product) {
    document.getElementById('product_id').value = product.id;
    document.getElementById('name_en').value = product.name_en;
    document.getElementById('name_ar').value = product.name_ar;
    document.getElementById('category_id').value = product.category_id;
    document.getElementById('price').value = product.price;
    document.getElementById('max_price').value = product.max_price || '';
    document.getElementById('stock').value = product.stock;
    document.getElementById('description_en').value = product.description_en || '';
    document.getElementById('description_ar').value = product.description_ar || '';
    document.getElementById('featured').checked = product.featured == 1;
    document.getElementById('modalTitle').textContent = '<?php echo $lang === 'ar' ? 'تعديل منتج' : 'Edit Product'; ?>';
    
    new bootstrap.Modal(document.getElementById('productModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
