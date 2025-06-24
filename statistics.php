<?php
require_once 'config.php';
require_once 'db_connect.php';
//require_once 'auth_check.php'; // تأكد من تسجيل الدخول

//$pageTitle = getPageTitle('statistics');
$pageSubtitle = 'تحليل شامل لأداء المهام';

// الفلترة حسب المستخدم الحالي
$user_id = $_SESSION['user_id'];

// الإحصائيات الأساسية (مع فلترة المستخدم)
$stats_query = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN status = 'pending' AND due_date < NOW() THEN 1 ELSE 0 END) as overdue_tasks,
    AVG(CHAR_LENGTH(title)) as avg_task_length
    FROM tasks 
    WHERE user_id = $user_id";
$stats_result = $mysqli->query($stats_query);
$basicStats = $stats_result->fetch_assoc();

// الإحصائيات الشهرية (مع فلترة المستخدم)
$monthly_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as task_count,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM tasks 
    WHERE user_id = $user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC";
$monthly_result = $mysqli->query($monthly_query);

// توزيع أطوال المهام (مع فلترة المستخدم)
$length_query = "SELECT 
    CASE 
        WHEN CHAR_LENGTH(title) <= 20 THEN 'قصيرة (1-20 حرف)'
        WHEN CHAR_LENGTH(title) <= 50 THEN 'متوسطة (21-50 حرف)'
        WHEN CHAR_LENGTH(title) <= 100 THEN 'طويلة (51-100 حرف)'
        ELSE 'مفصلة (أكثر من 100 حرف)'
    END as length_category,
    COUNT(*) as count
    FROM tasks
    WHERE user_id = $user_id
    GROUP BY length_category
    ORDER BY count DESC";
$length_result = $mysqli->query($length_query);

// حساب معدل الإنجاز
$completionRate = $basicStats['total_tasks'] > 0 ? 
    round(($basicStats['completed_tasks'] / $basicStats['total_tasks']) * 100, 1) : 0;

// المهام القريبة من الاستحقاق (للإشعارات)
$reminder_query = "SELECT 
    title, 
    due_date,
    DATEDIFF(due_date, NOW()) as days_remaining
    FROM tasks
    WHERE user_id = $user_id 
    AND status = 'pending'
    AND due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
    ORDER BY due_date ASC
    LIMIT 5";
$reminder_result = $mysqli->query($reminder_query);

include('includes/header.php');
?>

<main class="main-content statistics">
    <!-- إشعارات التذكير -->
    <?php if ($reminder_result->num_rows > 0): ?>
    <section class="notifications">
        <h2>تذكيرات المهام</h2>
        <div class="notification-grid">
            <?php while ($reminder = $reminder_result->fetch_assoc()): ?>
                <div class="notification-card <?php echo $reminder['days_remaining'] <= 0 ? 'urgent' : 'warning'; ?>">
                    <i class="fas fa-bell"></i>
                    <div class="notification-content">
                        <h3><?php echo htmlspecialchars($reminder['title']); ?></h3>
                        <p>
                            <?php if ($reminder['days_remaining'] <= 0): ?>
                                <strong>متأخرة!</strong> كان الاستحقاق: <?php echo $reminder['due_date']; ?>
                            <?php else: ?>
                                تستحق خلال <?php echo $reminder['days_remaining']; ?> يوم
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- الإحصائيات الأساسية (مع بطاقة المهام المتأخرة الجديدة) -->
    <section class="stats-overview">
        <div class="overview-grid">
            <!-- البطاقات السابقة... -->
            
            <!-- بطاقة جديدة للمهام المتأخرة -->
            <div class="stat-card large overdue">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h2><?php echo $basicStats['overdue_tasks']; ?></h2>
                    <p>مهام متأخرة</p>
                    <small>تجاوزت تاريخ الاستحقاق</small>
                </div>
            </div>
        </div>
    </section>

    <!-- أقسام أخرى (نفس السابق مع تعديلات بسيطة) -->
    
    <!-- قسم تواريخ الاستحقاق -->
    <section class="due-date-stats">
        <h2>حالة الاستحقاق</h2>
        <div class="due-date-grid">
            <div class="due-date-card">
                <h3>المتأخرة</h3>
                <span class="count overdue"><?php echo $basicStats['overdue_tasks']; ?></span>
            </div>
            <div class="due-date-card">
                <h3>القادمة (3 أيام)</h3>
                <span class="count warning"><?php echo $reminder_result->num_rows; ?></span>
            </div>
        </div>
    </section>
</main>

<?php 
$mysqli->close();
include('includes/footer.php'); 
?>