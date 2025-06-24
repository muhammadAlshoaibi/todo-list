
<?php
require_once 'config.php';
require_once 'db_connect.php';

$pageTitle ='index';
$pageSubtitle = 'نظم مهامك اليومية بسهولة';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_task'])) {
        $task_name = trim($_POST['task_name']);
        if (!empty($task_name)) {
            $query = "INSERT INTO tasks (task_name) VALUES (?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("s", $task_name);
            $stmt->execute();
            header("Location: index.php");
            exit();
        }
    } elseif (isset($_POST['delete_task'])) {
        $task_id = (int)$_POST['task_id'];
        $query = "DELETE FROM tasks WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        header("Location: index.php");
        exit();
    } elseif (isset($_POST['toggle_status'])) {
        $task_id = (int)$_POST['task_id'];
        $query = "UPDATE tasks SET status = IF(status='completed', 'pending', 'completed') WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        header("Location: index.php");
        exit();
    }
}

// Get tasks based on filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter == 'pending') {
    $where = "WHERE status = 'pending'";
} elseif ($filter == 'completed') {
    $where = "WHERE status = 'completed'";
}

$query = "SELECT * FROM tasks $where ORDER BY created_at DESC";
$result = $mysqli->query($query);

// Get statistics
$stats_query = "SELECT
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks";
$stats_result = $mysqli->query($stats_query);
$stats = $stats_result->fetch_assoc();

include('includes/header.php');
?>

<main class="main-content">
    <!-- Add Task Section -->
    <section class="add-task-section">
        <form method="POST" class="input-container">
            <input
                type="text"
                name="task_name"
                placeholder="أضف مهمة جديدة..."
                class="task-input"
                maxlength="255"
                required
            >
            <button type="submit" name="add_task" class="add-btn">
                <i class="fas fa-plus"></i>
                إضافة
            </button>
        </form>
    </section>

    <!-- Tasks Statistics -->
    <section class="stats-section">
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $stats['pending_tasks']; ?></span>
                <span class="stat-label">مهام معلقة</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $stats['completed_tasks']; ?></span>
                <span class="stat-label">مهام مكتملة</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $stats['total_tasks']; ?></span>
                <span class="stat-label">إجمالي المهام</span>
            </div>
        </div>
    </section>

    <!-- Filter Buttons -->
    <section class="filter-section">
        <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            جميع المهام
        </a>
        <a href="?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i>
            المعلقة
        </a>
        <a href="?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">
            <i class="fas fa-check"></i>
            المكتملة
        </a>
    </section>

    <!-- Tasks List -->
    <section class="tasks-section">
        <div id="tasksContainer" class="tasks-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($task = $result->fetch_assoc()): ?>
                    <div class="task-item <?php echo $task['status'] == 'completed' ? 'completed' : ''; ?>">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" name="toggle_status" class="task-checkbox <?php echo $task['status'] == 'completed' ? 'checked' : ''; ?>">
                                <?php if ($task['status'] == 'completed'): ?>
                                    <i class="fas fa-check"></i>
                                <?php endif; ?>
                            </button>
                        </form>
                        <div class="task-content">
                            <div class="task-text <?php echo $task['status'] == 'completed' ? 'completed' : ''; ?>">
                                <?php echo htmlspecialchars($task['task_name']); ?>
                            </div>
                            <div class="task-date">
                                <?php echo date('Y/m/d H:i', strtotime($task['created_at'])); ?>
                            </div>
                        </div>
                        <div class="task-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="delete_task" class="task-btn delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>لا توجد مهام حتى الآن</h3>
                    <p>ابدأ بإضافة مهمة جديدة لتنظيم يومك</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$mysqli->close();
includeFooter();
?>
