<?php
require_once __DIR__ . '/includes/header.php';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        setFlash('error', t('required_fields'));
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', t('invalid_email'));
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, message) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $email, $phone, $message])) {
            setFlash('success', $lang === 'ar' ? 'تم إرسال رسالتك بنجاح!' : 'Your message has been sent successfully!');
            redirect(SITE_URL . '/contact.php');
        } else {
            setFlash('error', t('error'));
        }
    }
}
?>

<section class="section" style="margin-top: 100px;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><?php echo t('get_in_touch'); ?></h2>
            <p><?php echo $lang === 'ar' ? 'نحن هنا للإجابة على أسئلتك' : 'We\'re here to answer your questions'; ?></p>
        </div>
        
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-7 mb-4" data-aos="fade-right">
                <div class="contact-form">
                    <h3 class="mb-4"><?php echo t('send_message'); ?></h3>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label"><?php echo t('your_name'); ?> *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label"><?php echo t('your_email'); ?> *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="phone" class="form-label"><?php echo t('your_phone'); ?></label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="message" class="form-label"><?php echo t('your_message'); ?> *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-gold">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    <?php echo t('send_message'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-5 mb-4" data-aos="fade-left">
                <div class="contact-form">
                    <h3 class="mb-4"><?php echo t('contact_info'); ?></h3>
                    
                    <div class="contact-info-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt fa-2x text-gold"></i>
                            </div>
                            <div class="ms-3">
                                <h5><?php echo t('address'); ?></h5>
                                <p class="text-light-gray mb-0">
                                    <?php echo getSetting('store_address_' . $lang); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-info-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="contact-icon">
                                <i class="fas fa-envelope fa-2x text-gold"></i>
                            </div>
                            <div class="ms-3">
                                <h5><?php echo t('email'); ?></h5>
                                <p class="text-light-gray mb-0">
                                    <a href="mailto:<?php echo getSetting('store_email'); ?>" class="text-light-gray">
                                        <?php echo getSetting('store_email'); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-info-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="contact-icon">
                                <i class="fas fa-phone fa-2x text-gold"></i>
                            </div>
                            <div class="ms-3">
                                <h5><?php echo t('phone'); ?></h5>
                                <p class="text-light-gray mb-0">
                                    <a href="tel:<?php echo getSetting('whatsapp_number'); ?>" class="text-light-gray">
                                        <?php echo getSetting('whatsapp_number'); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="d-flex align-items-start">
                            <div class="contact-icon">
                                <i class="fab fa-whatsapp fa-2x text-gold"></i>
                            </div>
                            <div class="ms-3">
                                <h5>WhatsApp</h5>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('whatsapp_number')); ?>" 
                                   class="btn btn-outline-gold" target="_blank">
                                    <i class="fab fa-whatsapp me-2"></i>
                                    <?php echo $lang === 'ar' ? 'تواصل عبر واتساب' : 'Chat on WhatsApp'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Map -->
        <div class="row mt-5">
            <div class="col-12" data-aos="fade-up">
                <div style="border: 2px solid var(--gold); border-radius: 20px; overflow: hidden; height: 400px;">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d55251.37709390283!2d31.223096!3d30.044420!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14583fa60b21beeb%3A0x79dfb296e8423bba!2sCairo%2C%20Egypt!5e0!3m2!1sen!2s!4v1234567890"
                        width="100%" 
                        height="400" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
