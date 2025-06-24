<?php
require_once 'config.php';
require_login();
$pageTitle="Tasks";
$conn = db_connect();
$user_id = $_SESSION['user_id'];

// فلترة المهام
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// بناء استعلام SQL بناءً على الفلترة
$query = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

switch ($filter) {
    case 'pending':
        $query .= " AND status = 'pending'";
        break;
    case 'in_progress':
        $query .= " AND status = 'in_progress'";
        break;
    case 'completed':
        $query .= " AND status = 'completed'";
        break;
    case 'archived':
        $query .= " AND status = 'archived'";
        break;
    case 'overdue':
        $query .= " AND due_date < NOW() AND status NOT IN ('completed', 'archived')";
        break;
    case 'due_soon':
        $query .= " AND due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) 
                   AND status NOT IN ('completed', 'archived')";
        break;
}

$query .= " ORDER BY 
    CASE 
        WHEN due_date IS NULL THEN 1
        ELSE 0
    END,
    due_date ASC,
    CASE priority
        WHEN 'urgent' THEN 0
        WHEN 'high' THEN 1
        WHEN 'medium' THEN 2
        WHEN 'low' THEN 3
    END";

$stmt = $conn->prepare($query);

// ربط المعاملات الديناميكية
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $params[0]);
}

$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المهام</title>
    <link rel="stylesheet" href="css/styles.css?<?php echo time();?>">
    <!-- <link rel="stylesheet" href="styles.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-tasks"></i> إدارة المهام</h1>
            <a href="add_task.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة مهمة جديدة
            </a>
        </div>
        
        <!-- فلترة المهام -->
        <div class="task-filters">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="ابحث في المهام..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            
            <div class="filter-buttons">
                <a href="?filter=all" class="btn btn-small <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    الكل
                </a>
                <a href="?filter=pending" class="btn btn-small <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    معلقة
                </a>
                <a href="?filter=in_progress" class="btn btn-small <?php echo $filter === 'in_progress' ? 'active' : ''; ?>">
                    قيد التنفيذ
                </a>
                <a href="?filter=completed" class="btn btn-small <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                    مكتملة
                </a>
                <a href="?filter=overdue" class="btn btn-small <?php echo $filter === 'overdue' ? 'active' : ''; ?>">
                    متأخرة
                </a>
                <a href="?filter=due_soon" class="btn btn-small <?php echo $filter === 'due_soon' ? 'active' : ''; ?>">
                    قريبة من الاستحقاق
                </a>
            </div>
        </div>
        
        <!-- قائمة المهام -->
        <?php if (count($tasks) > 0): ?>
            <div class="tasks-container">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card status-<?php echo $task['status']; ?> priority-<?php echo $task['priority']; ?>">
                        <div class="task-header">
                            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                            <div class="task-actions">
                                <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-small btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_task.php?id=<?php echo $task['id']; ?>" 
                                   class="btn btn-small btn-delete"
                                   onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <?php if (!empty($task['description'])): ?>
                            <div class="task-description">
                                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="task-footer">
                            <div class="task-meta">
                                <?php if ($task['due_date']): ?>
                                    <span class="task-due <?php echo (strtotime($task['due_date']) < time() && !in_array($task['status'], ['completed', 'archived'])) ? 'overdue' : ''; ?>">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('Y/m/d H:i', strtotime($task['due_date'])); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                    <i class="fas fa-flag"></i>
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
                                
                                <span class="task-status status-<?php echo $task['status']; ?>">
                                    <?php 
                                    $statuses = [
                                        'pending' => 'معلقة',
                                        'in_progress' => 'قيد التنفيذ',
                                        'completed' => 'مكتملة',
                                        'archived' => 'مؤرشفة'
                                    ];
                                    echo $statuses[$task['status']];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="task-actions">
                                <form method="POST" action="update_task_status.php" class="status-form">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>معلقة</option>
                                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>مكتملة</option>
                                        <option value="archived" <?php echo $task['status'] === 'archived' ? 'selected' : ''; ?>>مؤرشفة</option>
                                    </select>
                                </form>
                                
                                <a href="add_reminder.php?task_id=<?php echo $task['id']; ?>" class="btn btn-small btn-reminder">
                                    <i class="fas fa-bell"></i> تذكير
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>لا توجد مهام لعرضها</h3>
                <p>يمكنك إضافة مهمة جديدة بالنقر على زر "إضافة مهمة جديدة" بالأعلى</p>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>