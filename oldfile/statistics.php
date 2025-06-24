<?php
require_once '../config.php';
require_once '../db_connect.php';

//$pageTitle = getPageTitle('statistics');
$pageSubtitle = 'تحليل شامل لأداء المهام';

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    AVG(CHAR_LENGTH(title)) as avg_task_length
    FROM tasks";
$stats_result = $mysqli->query($stats_query);
$basicStats = $stats_result->fetch_assoc();

// Monthly stats
$monthly_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as task_count,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM tasks 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC";
$monthly_result = $mysqli->query($monthly_query);

// Task length distribution
$length_query = "SELECT 
    CASE 
        WHEN CHAR_LENGTH(title) <= 20 THEN 'قصيرة (1-20 حرف)'
        WHEN CHAR_LENGTH(title) <= 50 THEN 'متوسطة (21-50 حرف)'
        WHEN CHAR_LENGTH(title) <= 100 THEN 'طويلة (51-100 حرف)'
        ELSE 'مفصلة (أكثر من 100 حرف)'
    END as length_category,
    COUNT(*) as count
    FROM tasks
    GROUP BY length_category
    ORDER BY count DESC";
$length_result = $mysqli->query($length_query);

// Calculate completion rate
$completionRate = $basicStats['total_tasks'] > 0 ? 
    round(($basicStats['completed_tasks'] / $basicStats['total_tasks']) * 100, 1) : 0;

include('../includes/header.php');
?>
 <!-- <link rel="stylesheet" href="../styles.css"> -->
 <!-- <link rel="stylesheet" href="../css/styles.css"> -->
<main class="main-content statistics">
    <!-- Statistics Overview -->
    <section class="stats-overview">
        <div class="overview-grid">
            <div class="stat-card large total">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <h2><?php echo $basicStats['total_tasks']; ?></h2>
                    <p>إجمالي المهام</p>
                    <small>جميع المهام المسجلة في النظام</small>
                </div>
            </div>
            
            <div class="stat-card large completed">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h2><?php echo $basicStats['completed_tasks']; ?></h2>
                    <p>مهام مكتملة</p>
                    <small>معدل الإنجاز: <?php echo $completionRate; ?>%</small>
                </div>
            </div>
            
            <div class="stat-card large pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h2><?php echo $basicStats['pending_tasks']; ?></h2>
                    <p>مهام معلقة</p>
                    <small>تحتاج للإنجاز</small>
                </div>
            </div>
            
            <div class="stat-card large average">
                <div class="stat-icon">
                    <i class="fas fa-ruler"></i>
                </div>
                <div class="stat-content">
                    <h2><?php echo round($basicStats['avg_task_length']); ?></h2>
                    <p>متوسط طول المهمة</p>
                    <small>عدد الأحرف</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Progress Section -->
    <section class="progress-section">
        <h2>معدل الإنجاز</h2>
        <div class="progress-container">
            <div class="progress-circle">
                <svg width="200" height="200" viewBox="0 0 200 200">
                    <circle cx="100" cy="100" r="80" fill="none" stroke="#e2e8f0" stroke-width="20"/>
                    <circle cx="100" cy="100" r="80" fill="none" stroke="#48bb78" stroke-width="20"
                            stroke-dasharray="<?php echo $completionRate * 5.02; ?> 502"
                            stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                </svg>
                <div class="progress-text">
                    <span class="progress-percentage"><?php echo $completionRate; ?>%</span>
                    <span class="progress-label">مكتمل</span>
                </div>
            </div>
            <div class="progress-details">
                <div class="progress-item completed">
                    <span class="progress-count"><?php echo $basicStats['completed_tasks']; ?></span>
                    <span class="progress-desc">مهام مكتملة</span>
                </div>
                <div class="progress-item pending">
                    <span class="progress-count"><?php echo $basicStats['pending_tasks']; ?></span>
                    <span class="progress-desc">مهام معلقة</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Monthly Trends -->
    <section class="monthly-trends">
        <h2>الاتجاهات الشهرية (آخر 6 أشهر)</h2>
        <div class="chart-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الشهر</th>
                        <th>إجمالي المهام</th>
                        <th>المكتملة</th>
                        <th>النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $monthly_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['month']; ?></td>
                            <td><?php echo $row['task_count']; ?></td>
                            <td><?php echo $row['completed_count']; ?></td>
                            <td><?php echo round(($row['completed_count'] / $row['task_count']) * 100); ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Task Length Distribution -->
    <section class="length-distribution">
        <h2>توزيع أطوال المهام</h2>
        <div class="distribution-grid">
            <?php while ($row = $length_result->fetch_assoc()): ?>
                <div class="distribution-item">
                    <div class="distribution-info">
                        <span class="category"><?php echo $row['length_category']; ?></span>
                        <span class="count"><?php echo $row['count']; ?> مهمة</span>
                    </div>
                    <div class="distribution-percent">
                        <?php echo round(($row['count'] / $basicStats['total_tasks']) * 100); ?>%
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- Productivity Insights -->
    <section class="productivity-insights">
        <h2>رؤى الإنتاجية</h2>
        <div class="insights-grid">
            <div class="insight-card">
                <div class="insight-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="insight-content">
                    <h3>أفضل أداء</h3>
                    <p>
                        <?php if ($completionRate >= 80): ?>
                            أداء ممتاز! معدل إنجازك مرتفع جداً
                        <?php elseif ($completionRate >= 60): ?>
                            أداء جيد، يمكنك تحسينه أكثر
                        <?php elseif ($completionRate >= 40): ?>
                            أداء متوسط، حاول زيادة التركيز
                        <?php else: ?>
                            يحتاج لتحسين، ابدأ بمهام صغيرة
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="insight-card">
                <div class="insight-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="insight-content">
                    <h3>نصيحة</h3>
                    <p>
                        <?php if ($basicStats['avg_task_length'] > 100): ?>
                            حاول تقسيم المهام الطويلة إلى مهام أصغر
                        <?php elseif ($basicStats['pending_tasks'] > $basicStats['completed_tasks']): ?>
                            ركز على إنجاز المهام المعلقة أولاً
                        <?php else: ?>
                            استمر في الأداء الجيد وأضف مهام جديدة
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="insight-card">
                <div class="insight-icon">
                    <i class="fas fa-target"></i>
                </div>
                <div class="insight-content">
                    <h3>الهدف التالي</h3>
                    <p>
                        <?php if ($completionRate < 100): ?>
                            الوصول لمعدل إنجاز <?php echo min(100, $completionRate + 20); ?>%
                        <?php else: ?>
                            الحفاظ على الأداء المثالي
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php 
$mysqli->close();
include('../includes/footer.php'); 
?>