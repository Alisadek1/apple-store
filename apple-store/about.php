<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="section" style="margin-top: 100px;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><?php echo t('about_us'); ?></h2>
            <p><?php echo $lang === 'ar' ? 'تعرف على قصتنا ورؤيتنا' : 'Learn about our story and vision'; ?></p>
        </div>
        
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 mb-4" data-aos="fade-right">
                <div style="background: var(--dark-gray); border: 2px solid var(--gold); border-radius: 20px; padding: 3rem;">
                    <h3 class="mb-4"><?php echo t('our_story'); ?></h3>
                    <p class="text-light-gray" style="font-size: 1.1rem; line-height: 1.8;">
                        <?php if ($lang === 'ar'): ?>
                            نحن متجر أبل الرائد في مصر، متخصصون في توفير أحدث منتجات أبل الأصلية بأفضل الأسعار. 
                            منذ تأسيسنا، كان هدفنا هو جلب الابتكار والجودة إلى عملائنا الكرام.
                            <br><br>
                            نفخر بتقديم تجربة تسوق فاخرة تليق بمنتجات أبل المميزة، مع خدمة عملاء استثنائية 
                            وضمان على جميع المنتجات.
                        <?php else: ?>
                            We are Egypt's premier Apple Store, specializing in providing the latest authentic Apple products 
                            at the best prices. Since our establishment, our goal has been to bring innovation and quality 
                            to our valued customers.
                            <br><br>
                            We pride ourselves on offering a luxury shopping experience worthy of Apple's premium products, 
                            with exceptional customer service and warranty on all products.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4" data-aos="fade-left">
                <div style="background: var(--dark-gray); border: 2px solid var(--gold); border-radius: 20px; padding: 3rem;">
                    <h3 class="mb-4"><?php echo t('our_vision'); ?></h3>
                    <p class="text-light-gray" style="font-size: 1.1rem; line-height: 1.8;">
                        <?php if ($lang === 'ar'): ?>
                            رؤيتنا هي أن نكون الوجهة الأولى لعشاق أبل في مصر والشرق الأوسط. 
                            نسعى لتوفير تجربة تسوق سلسة وآمنة، مع أفضل الأسعار والعروض الحصرية.
                            <br><br>
                            نؤمن بأن التكنولوجيا يجب أن تكون متاحة للجميع، ولهذا نقدم خيارات دفع مرنة 
                            وخدمة توصيل سريعة لجميع أنحاء مصر.
                        <?php else: ?>
                            Our vision is to be the premier destination for Apple enthusiasts in Egypt and the Middle East. 
                            We strive to provide a seamless and secure shopping experience, with the best prices and exclusive offers.
                            <br><br>
                            We believe technology should be accessible to everyone, which is why we offer flexible payment options 
                            and fast delivery service throughout Egypt.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Features -->
        <div class="row g-4 mt-5">
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="text-center p-4" style="background: var(--dark-gray); border: 2px solid var(--gold); border-radius: 20px; height: 100%;">
                    <i class="fas fa-shield-alt fa-3x text-gold mb-3"></i>
                    <h4><?php echo $lang === 'ar' ? 'منتجات أصلية' : 'Authentic Products'; ?></h4>
                    <p class="text-light-gray">
                        <?php echo $lang === 'ar' ? '100% منتجات أبل أصلية مع الضمان' : '100% authentic Apple products with warranty'; ?>
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="text-center p-4" style="background: var(--dark-gray); border: 2px solid var(--gold); border-radius: 20px; height: 100%;">
                    <i class="fas fa-truck fa-3x text-gold mb-3"></i>
                    <h4><?php echo $lang === 'ar' ? 'توصيل سريع' : 'Fast Delivery'; ?></h4>
                    <p class="text-light-gray">
                        <?php echo $lang === 'ar' ? 'توصيل لجميع أنحاء مصر' : 'Delivery throughout Egypt'; ?>
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="text-center p-4" style="background: var(--dark-gray); border: 2px solid var(--gold); border-radius: 20px; height: 100%;">
                    <i class="fas fa-headset fa-3x text-gold mb-3"></i>
                    <h4><?php echo $lang === 'ar' ? 'دعم 24/7' : '24/7 Support'; ?></h4>
                    <p class="text-light-gray">
                        <?php echo $lang === 'ar' ? 'خدمة عملاء متاحة دائماً' : 'Customer service always available'; ?>
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="text-center p-4" style="background: var(--dark-gray); border: 2px solid var(--gold); border-radius: 20px; height: 100%;">
                    <i class="fas fa-credit-card fa-3x text-gold mb-3"></i>
                    <h4><?php echo $lang === 'ar' ? 'دفع مرن' : 'Flexible Payment'; ?></h4>
                    <p class="text-light-gray">
                        <?php echo $lang === 'ar' ? 'خيارات دفع متعددة ومريحة' : 'Multiple convenient payment options'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
