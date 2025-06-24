<?php
require_once 'config.php';
require_once 'db_connect.php';

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId <= 0) {
    header("Location: index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_task'])) {
        $task_name = trim($_POST['task_name']);
        $status = $_POST['status'];
        
        $query = "UPDATE tasks SET task_name = ?, status = ? WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ssi", $task_name, $status, $taskId);
        $stmt->execute();
        
        header("Location: task_details.php?id=$taskId");
        exit();
    } elseif (isset($_POST['delete_task'])) {
        $query = "DELETE FROM tasks WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        
        header("Location: index.php");
        exit();
    }
}

// Get task details
$query = "SELECT * FROM tasks WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    header("Location: index.php");
    exit();
}

$pageTitle = "تفاصيل المهمة: " . htmlspecialchars($task['task_name']);
includeHeader($pageTitle);
?>

<main class="main-content task-details">
    <!-- Task Header -->
    <section class="task-header">
        <div class="task-header-content">
            <div class="task-info">
                <h1 class="task-title"><?php echo htmlspecialchars($task['task_name']); ?></h1>
                <div class="task-meta">
                    <span class="status-badge <?php echo $task['status']; ?>">
                        <i class="fas fa-<?php echo $task['status'] == 'completed' ? 'check-circle' : 'clock'; ?>"></i>
                        <?php echo $task['status'] == 'completed' ? 'مكتملة' : 'معلقة'; ?>
                    </span>
                    <span class="task-date">
                        <i class="fas fa-calendar"></i>
                        تم الإنشاء: <?php echo date('Y/m/d H:i', strtotime($task['created_at'])); ?>
                    </span>
                    <span class="task-id">
                        <i class="fas fa-hashtag"></i>
                        رقم المهمة: <?php echo $task['id']; ?>
                    </span>
                </div>
            </div>
            <div class="task-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="status" value="<?php echo $task['status'] == 'completed' ? 'pending' : 'completed'; ?>">
                    <button type="submit" name="update_task" class="btn primary">
                        <i class="fas fa-<?php echo $task['status'] == 'completed' ? 'undo' : 'check'; ?>"></i>
                        <?php echo $task['status'] == 'completed' ? 'إلغاء الإنجاز' : 'تحديد كمكتملة'; ?>
                    </button>
                </form>
                <button class="btn secondary" onclick="document.getElementById('editForm').classList.toggle('hidden')">
                    <i class="fas fa-edit"></i>
                    تعديل
                </button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="delete_task" class="btn danger" onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                        <i class="fas fa-trash"></i>
                        حذف
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Task Details -->
    <section class="task-details-section">
        <div class="details-grid">
            <div class="detail-card">
                <h3><i class="fas fa-info-circle"></i> معلومات المهمة</h3>
                
                <!-- Edit Form (Hidden by default) -->
                <form id="editForm" method="POST" class="hidden">
                    <div class="form-group">
                        <label>اسم المهمة:</label>
                        <input type="text" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>الحالة:</label>
                        <select name="status" class="form-control">
                            <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>معلقة</option>
                            <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>مكتملة</option>
                        </select>
                    </div>
                    <button type="submit" name="update_task" class="btn primary">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                </form>
                
                <!-- Display Info -->
                <div id="displayInfo">
                    <div class="detail-item">
                        <label>اسم المهمة:</label>
                        <span><?php echo htmlspecialchars($task['task_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>الحالة:</label>
                        <span class="status-badge <?php echo $task['status']; ?>">
                            <?php echo $task['status'] == 'completed' ? 'مكتملة' : 'معلقة'; ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <label>تاريخ الإنشاء:</label>
                        <span><?php echo date('Y/m/d H:i', strtotime($task['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>عدد الأحرف:</label>
                        <span><?php echo mb_strlen($task['task_name'], 'UTF-8'); ?> حرف</span>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <h3><i class="fas fa-chart-pie"></i> إحصائيات سريعة</h3>
                <div class="detail-content">
                    <div class="stat-item">
                        <span class="stat-label">الوقت منذ الإنشاء:</span>
                        <span class="stat-value">
                            <?php
                            $created = new DateTime($task['created_at']);
                            $now = new DateTime();
                            $interval = $now->diff($created);
                            
                            if ($interval->y > 0) echo $interval->y . ' سنة ';
                            if ($interval->m > 0) echo $interval->m . ' شهر ';
                            if ($interval->d > 0) echo $interval->d . ' يوم ';
                            if ($interval->h > 0) echo $interval->h . ' ساعة ';
                            if ($interval->i > 0) echo $interval->i . ' دقيقة';
                            if ($interval->y == 0 && $interval->m == 0 && $interval->d == 0 && $interval->h == 0 && $interval->i == 0) {
                                echo 'أقل من دقيقة';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">نوع المهمة:</span>
                        <span class="stat-value">
                            <?php 
                            $length = mb_strlen($task['task_name'], 'UTF-8');
                            echo $length > 50 ? 'مهمة مفصلة' : 'مهمة بسيطة';
                            ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">الأولوية:</span>
                        <span class="stat-value priority-normal">عادية</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Task History -->
    <section class="task-history">
        <h3><i class="fas fa-history"></i> سجل المهمة</h3>
        <div class="history-timeline">
            <div class="timeline-item">
                <div class="timeline-icon created">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="timeline-content">
                    <h4>تم إنشاء المهمة</h4>
                    <p><?php echo date('Y/m/d H:i', strtotime($task['created_at'])); ?></p>
                </div>
            </div>
            <?php if ($task['status'] == 'completed'): ?>
            <div class="timeline-item">
                <div class="timeline-icon completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="timeline-content">
                    <h4>تم إنجاز المهمة</h4>
                    <p>تم تحديد المهمة كمكتملة</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Navigation -->
    <section class="task-navigation">
        <div class="nav-buttons">
            <a href="dashboard.php" class="btn secondary">
                <i class="fas fa-arrow-right"></i>
                العودة للوحة التحكم
            </a>
            <a href="index.php" class="btn primary">
                <i class="fas fa-home"></i>
                الصفحة الرئيسية
            </a>
        </div>
    </section>
</main>

<script>
// Minimal JavaScript for toggle functionality
function toggleEditForm() {
    document.getElementById('editForm').classList.toggle('hidden');
    document.getElementById('displayInfo').classList.toggle('hidden');
}
</script>

<?php 
$mysqli->close();
includeFooter(); 
?>