<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'whatsapp_number' => sanitize($_POST['whatsapp_number']),
        'store_name_en' => sanitize($_POST['store_name_en']),
        'store_name_ar' => sanitize($_POST['store_name_ar']),
        'store_email' => sanitize($_POST['store_email']),
        'store_address_en' => sanitize($_POST['store_address_en']),
        'store_address_ar' => sanitize($_POST['store_address_ar']),
        'local_governorate' => sanitize($_POST['local_governorate']),
        'deposit_percentage' => (int)$_POST['deposit_percentage']
    ];
    
    foreach ($settings as $key => $value) {
        updateSetting($key, $value);
    }
    
    setFlash('success', $lang === 'ar' ? 'تم تحديث الإعدادات بنجاح' : 'Settings updated successfully');
    redirect(ADMIN_URL . '/settings.php');
}

// Get current settings
$current_settings = [];
$stmt = $db->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="page-header">
    <h1><?php echo t('settings'); ?></h1>
    <p><?php echo $lang === 'ar' ? 'إعدادات المتجر العامة' : 'General store settings'; ?></p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-form">
            <form method="POST">
                <h5 class="text-gold mb-4"><?php echo $lang === 'ar' ? 'معلومات المتجر' : 'Store Information'; ?></h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="store_name_en" class="form-label">
                            <?php echo $lang === 'ar' ? 'اسم المتجر (إنجليزي)' : 'Store Name (English)'; ?>
                        </label>
                        <input type="text" class="form-control" id="store_name_en" name="store_name_en" 
                               value="<?php echo htmlspecialchars($current_settings['store_name_en'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="store_name_ar" class="form-label">
                            <?php echo $lang === 'ar' ? 'اسم المتجر (عربي)' : 'Store Name (Arabic)'; ?>
                        </label>
                        <input type="text" class="form-control" id="store_name_ar" name="store_name_ar" 
                               value="<?php echo htmlspecialchars($current_settings['store_name_ar'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="store_email" class="form-label">
                            <?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?>
                        </label>
                        <input type="email" class="form-control" id="store_email" name="store_email" 
                               value="<?php echo htmlspecialchars($current_settings['store_email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="whatsapp_number" class="form-label">
                            <?php echo $lang === 'ar' ? 'رقم واتساب' : 'WhatsApp Number'; ?>
                        </label>
                        <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" 
                               value="<?php echo htmlspecialchars($current_settings['whatsapp_number'] ?? ''); ?>" 
                               placeholder="+201234567890" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="store_address_en" class="form-label">
                            <?php echo $lang === 'ar' ? 'العنوان (إنجليزي)' : 'Address (English)'; ?>
                        </label>
                        <input type="text" class="form-control" id="store_address_en" name="store_address_en" 
                               value="<?php echo htmlspecialchars($current_settings['store_address_en'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="store_address_ar" class="form-label">
                            <?php echo $lang === 'ar' ? 'العنوان (عربي)' : 'Address (Arabic)'; ?>
                        </label>
                        <input type="text" class="form-control" id="store_address_ar" name="store_address_ar" 
                               value="<?php echo htmlspecialchars($current_settings['store_address_ar'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <hr class="border-gold my-4">
                
                <h5 class="text-gold mb-4"><?php echo $lang === 'ar' ? 'إعدادات الطلبات' : 'Order Settings'; ?></h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="local_governorate" class="form-label">
                            <?php echo $lang === 'ar' ? 'المحافظة المحلية' : 'Local Governorate'; ?>
                        </label>
                        <input type="text" class="form-control" id="local_governorate" name="local_governorate" 
                               value="<?php echo htmlspecialchars($current_settings['local_governorate'] ?? 'Cairo'); ?>" required>
                        <small class="text-light-gray">
                            <?php echo $lang === 'ar' ? 'المحافظة التي تسمح بالعربون' : 'Governorate that allows deposit payment'; ?>
                        </small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="deposit_percentage" class="form-label">
                            <?php echo $lang === 'ar' ? 'نسبة العربون (%)' : 'Deposit Percentage (%)'; ?>
                        </label>
                        <input type="number" class="form-control" id="deposit_percentage" name="deposit_percentage" 
                               value="<?php echo htmlspecialchars($current_settings['deposit_percentage'] ?? '30'); ?>" 
                               min="1" max="100" required>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-gold">
                        <i class="fas fa-save me-2"></i>
                        <?php echo t('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="admin-form">
            <h5 class="text-gold mb-4"><?php echo $lang === 'ar' ? 'معلومات النظام' : 'System Information'; ?></h5>
            
            <div class="mb-3">
                <p class="text-light-gray mb-1">
                    <strong><?php echo $lang === 'ar' ? 'إصدار PHP:' : 'PHP Version:'; ?></strong>
                </p>
                <p class="text-gold"><?php echo phpversion(); ?></p>
            </div>
            
            <div class="mb-3">
                <p class="text-light-gray mb-1">
                    <strong><?php echo $lang === 'ar' ? 'قاعدة البيانات:' : 'Database:'; ?></strong>
                </p>
                <p class="text-gold">MySQL</p>
            </div>
            
            <div class="mb-3">
                <p class="text-light-gray mb-1">
                    <strong><?php echo $lang === 'ar' ? 'المنطقة الزمنية:' : 'Timezone:'; ?></strong>
                </p>
                <p class="text-gold"><?php echo date_default_timezone_get(); ?></p>
            </div>
            
            <div class="mb-3">
                <p class="text-light-gray mb-1">
                    <strong><?php echo $lang === 'ar' ? 'حد رفع الملفات:' : 'Upload Limit:'; ?></strong>
                </p>
                <p class="text-gold"><?php echo ini_get('upload_max_filesize'); ?></p>
            </div>
            
            <hr class="border-gold">
            
            <h5 class="text-gold mb-3"><?php echo $lang === 'ar' ? 'روابط سريعة' : 'Quick Links'; ?></h5>
            
            <div class="d-grid gap-2">
                <a href="<?php echo SITE_URL; ?>/database/schema.sql" class="btn btn-outline-gold" download>
                    <i class="fas fa-database me-2"></i>
                    <?php echo $lang === 'ar' ? 'تنزيل قاعدة البيانات' : 'Download Database Schema'; ?>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/README.md" class="btn btn-outline-gold" target="_blank">
                    <i class="fas fa-book me-2"></i>
                    <?php echo $lang === 'ar' ? 'دليل الاستخدام' : 'Documentation'; ?>
                </a>
                
                <a href="<?php echo SITE_URL; ?>/MCP_REVIEW.md" class="btn btn-outline-gold" target="_blank">
                    <i class="fas fa-clipboard-check me-2"></i>
                    <?php echo $lang === 'ar' ? 'تقرير المراجعة' : 'Review Report'; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
