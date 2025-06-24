<?php
//require_once 'config.php';
require_once '../db_connect.php';

//$pageTitle = getPageTitle('dashboard');
$pageSubtitle = 'إدارة شاملة لجميع مهامك';

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_all_completed'])) {
        $query = "UPDATE tasks SET status = 'completed' WHERE status = 'pending'";
        $mysqli->query($query);
        header("Location: dashboard.php");
        exit();
    } elseif (isset($_POST['delete_completed'])) {
        $query = "DELETE FROM tasks WHERE status = 'completed'";
        $mysqli->query($query);
        header("Location: dashboard.php");
        exit();
    }
}

// Get tasks statistics
$stats_query = "SELECT
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks";
$stats_result = $mysqli->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent tasks
$recent_query = "SELECT * FROM tasks ORDER BY created_at DESC LIMIT 5";
$recent_result = $mysqli->query($recent_query);

include('../includes/header.php');
?>

<main class="main-content dashboard">
    <!-- Dashboard Overview -->
    <section class="dashboard-overview">
        <div class="overview-cards">
            <div class="overview-card total">
                <div class="card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $stats['total_tasks']; ?></h3>
                    <p>إجمالي المهام</p>
                </div>
            </div>
            <div class="overview-card pending">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $stats['pending_tasks']; ?></h3>
                    <p>مهام معلقة</p>
                </div>
            </div>
            <div class="overview-card completed">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $stats['completed_tasks']; ?></h3>
                    <p>مهام مكتملة</p>
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

    <!-- Recent Tasks -->
    <section class="recent-tasks">
        <h2>المهام الحديثة</h2>
        <div class="tasks-table-container">
            <?php if ($recent_result->num_rows == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>لا توجد مهام حديثة</h3>
                    <p>ابدأ بإضافة مهمة جديدة</p>
                </div>
            <?php else: ?>
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>المهمة</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($task = $recent_result->fetch_assoc()): ?>
                            <tr class="task-row <?php echo $task['status']; ?>">
                                <td class="task-name">
                                    <a href="task_details.php?id=<?php echo $task['id']; ?>">
                                        <?php echo htmlspecialchars($task['task_name']); ?>
                                    </a>
                                </td>
                                <td class="task-status">
                                    <span class="status-badge <?php echo $task['status']; ?>">
                                        <?php echo $task['status'] == 'completed' ? 'مكتملة' : 'معلقة'; ?>
                                    </span>
                                </td>
                                <td class="task-date">
                                    <?php echo date('Y/m/d H:i', strtotime($task['created_at'])); ?>
                                </td>
                                <td class="task-actions">
                                    <a href="toggle_task.php?id=<?php echo $task['id']; ?>&status=<?php echo $task['status']; ?>" class="btn-small toggle-status">
                                        <i class="fas fa-<?php echo $task['status'] == 'completed' ? 'undo' : 'check'; ?>"></i>
                                    </a>
                                    <a href="task_details.php?id=<?php echo $task['id']; ?>" class="btn-small view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="delete_task.php?id=<?php echo $task['id']; ?>" class="btn-small delete" onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$mysqli->close();
include('../includes/footer.php');
?>