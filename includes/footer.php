    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>
                    <i class="fas fa-tasks"></i>
                    <?php echo APP_NAME; ?>
                </h3>
                <p>تطبيق إدارة المهام الأكثر تطوراً وسهولة في الاستخدام</p>
            </div>
            <div class="footer-section">
                <h4>روابط سريعة</h4>
                <ul>
                    <li><a href="index.php">الصفحة الرئيسية</a></li>
                    <li><a href="dashboard.php">لوحة التحكم</a></li>
                    <li><a href="statistics.php">الإحصائيات</a></li>
                    <li><a href="settings.php">الإعدادات</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>معلومات التطبيق</h4>
                <p>الإصدار: <?php echo APP_VERSION; ?></p>
                <p>تم التطوير باستخدام PHP وMySQL</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner"></div>
        <p>جاري التحميل...</p>
    </div>

    <!-- Toast Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="script.js"></script>
    <script>
        // Mobile navigation toggle
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.querySelector('.nav-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (navToggle && navMenu) {
                navToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    navToggle.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>

