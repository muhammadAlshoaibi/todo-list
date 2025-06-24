<?php
require_once 'config.php';
require_login();
$pageTitle = 'dashboard';
$conn = db_connect();
$user_id = $_SESSION['user_id'];


// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_all_completed'])) {
        $query = "UPDATE tasks SET status = 'completed' WHERE status = 'pending' and user_id='$user_id'";
        $mysqli->query($query);
        header("Location: dashboard.php");
        exit();
    } elseif (isset($_POST['delete_completed'])) {
        $query = "DELETE FROM tasks WHERE status = 'completed' and user_id='$user_id' ";
        $mysqli->query($query);
        header("Location: dashboard.php");
        exit();
    }
}

// إحصائيات المهام
$stats_query = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN due_date < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks WHERE user_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$d=date('Y/m/d H:i', strtotime('-3 days'));
// المهام القريبة من تاريخ الاستحقاق
$due_soon_query = "SELECT * FROM tasks 
    WHERE user_id = ? 
    AND due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
    AND status NOT IN ('completed', 'archived')
    ORDER BY due_date ASC
    LIMIT 5";
$stmt = $conn->prepare($due_soon_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$due_soon_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo count($due_soon_tasks);
$stmt->close();

// الإشعارات الغير مقروءة
$notifications_query = "SELECT * FROM notifications 
    WHERE user_id = ? AND is_read = FALSE
    ORDER BY created_at DESC
    LIMIT 5";
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> مرحباً بك، <?php echo $_SESSION['username']; ?></h1>
            <p>آخر تسجيل دخول: <?php echo date('Y/m/d H:i'); ?></p>
        </div>
        
        <!-- إحصائيات سريعة -->
        <section class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_tasks']; ?></h3>
                    <p>إجمالي المهام</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_tasks']; ?></h3>
                    <p>مهام معلقة</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon in-progress">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['in_progress_tasks']; ?></h3>
                    <p>قيد التنفيذ</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['completed_tasks']; ?></h3>
                    <p>مهام مكتملة</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon overdue">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['overdue_tasks']; ?></h3>
                    <p>مهام متأخرة</p>
                </div>
            </div>
             <div class="overview-card progress">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0; ?>%</h3>
                    <p>معدل الإنجاز</p>
                </div>
            </div>
        </section>
         <!-- Quick Actions -->
    <section class="quick-actions">
        <h2>إجراءات سريعة</h2>
        <div class="action-buttons">
            <a href="index.php" class="action-btn primary">
                <i class="fas fa-plus"></i>
                إضافة مهمة جديدة
            </a>
            <a href="statistics.php" class="action-btn secondary">
                <i class="fas fa-chart-bar"></i>
                عرض الإحصائيات
            </a>
            <form method="POST" style="display: inline;">
                <button type="submit" name="mark_all_completed" class="action-btn warning" onclick="return confirm('هل تريد تحديد جميع المهام المعلقة كمكتملة؟')">
                    <i class="fas fa-check-double"></i>
                    تحديد الكل كمكتمل
                </button>
            </form>
            <form method="POST" style="display: inline;">
                <button type="submit" name="delete_completed" class="action-btn danger" onclick="return confirm('هل تريد حذف جميع المهام المكتملة؟ هذا الإجراء لا يمكن التراجع عنه.')">
                    <i class="fas fa-trash"></i>
                    حذف المكتملة
                </button>
            </form>
        </div>
    </section>
        
        <div class="dashboard-grid">
            <!-- المهام القريبة من الاستحقاق -->
            <section class="dashboard-section">
                <h2><i class="fas fa-calendar-day"></i> مهام قريبة من الاستحقاق</h2>
                <?php if (count($due_soon_tasks) > 0): ?>
                    <ul class="task-list">
                        <?php foreach ($due_soon_tasks as $task): ?>
                            <li class="task-item priority-<?php echo $task['priority']; ?>">
                                <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                                <div class="task-meta">
                                    <span><i class="fas fa-calendar-alt"></i> 
                                        <?php echo date('Y/m/d H:i', strtotime($task['due_date'])); ?>
                                    </span>
                                    <span class="badge badge-<?php echo $task['priority']; ?>">
                                        <?php 
                                        $priorities = [
                                            'low' => 'منخفض',
                                            'medium' => 'متوسط',
                                            'high' => 'عالي',
                                            'urgent' => 'عاجل'
                                        ];
                                        echo $priorities[$task['priority']];
                                        ?>
                                    </span>
                                </div>
                                <a href="task.php?id=<?php echo $task['id']; ?>" class="btn btn-small">
                                    <i class="fas fa-eye"></i> عرض
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>لا توجد مهام قريبة من الاستحقاق</p>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- الإشعارات الحديثة -->
            <section class="dashboard-section">
                <h2><i class="fas fa-bell"></i> الإشعارات الحديثة</h2>
                <?php if (count($notifications) > 0): ?>
                    <ul class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                <h3><?php echo htmlspecialchars($notification['title']); ?></h3>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small><?php echo date('Y/m/d H:i', strtotime($notification['created_at'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="notifications.php" class="btn btn-link">عرض جميع الإشعارات</a>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>لا توجد إشعارات جديدة</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>