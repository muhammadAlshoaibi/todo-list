<?php
require_once 'config.php';
require_login();

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

// تحضير البيانات للعرض
$priorities = [
    'low' => ['name' => 'منخفض', 'class' => 'low', 'icon' => 'arrow-down'],
    'medium' => ['name' => 'متوسط', 'class' => 'medium', 'icon' => 'equals'],
    'high' => ['name' => 'عالي', 'class' => 'high', 'icon' => 'arrow-up'],
    'urgent' => ['name' => 'عاجل', 'class' => 'urgent', 'icon' => 'exclamation']
];

$statuses = [
    'pending' => ['name' => 'معلقة', 'class' => 'pending', 'icon' => 'clock'],
    'in_progress' => ['name' => 'قيد التنفيذ', 'class' => 'in-progress', 'icon' => 'spinner'],
    'completed' => ['name' => 'مكتملة', 'class' => 'completed', 'icon' => 'check'],
    'archived' => ['name' => 'مؤرشفة', 'class' => 'archived', 'icon' => 'archive']
];
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المهام</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* تنسيقات إضافية للجدول */
        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
       
        .tasks-table thead {
            background-color: #4a6fa5;
            color: white;
        }
       
        .tasks-table th, .tasks-table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #e0e0e0;
        }
       
        .tasks-table th {
            font-weight: 600;
        }
       
        .tasks-table tbody tr:hover {
            background-color: #f5f7fa;
        }
       
        .tasks-table tbody tr:last-child td {
            border-bottom: none;
        }
       
        .task-title {
            font-weight: 600;
            color: #2c3e50;
        }
       
        .task-description {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 5px;
        }
       
        .status-badge, .priority-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
       
        .status-badge i, .priority-badge i {
            margin-left: 5px;
            font-size: 0.8em;
        }
       
        /* ألوان الحالات */
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
       
        .status-in-progress {
            background-color: #cce5ff;
            color: #004085;
        }
       
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
       
        .status-archived {
            background-color: #e2e3e5;
            color: #383d41;
        }
       
        /* ألوان الأولويات */
        .priority-low {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
       
        .priority-medium {
            background-color: #fff8e1;
            color: #ff8f00;
        }
       
        .priority-high {
            background-color: #ffebee;
            color: #c62828;
        }
       
        .priority-urgent {
            background-color: #fce4ec;
            color: #ad1457;
        }
       
        /* تنسيق تاريخ الاستحقاق */
        .due-date {
            display: inline-flex;
            align-items: center;
        }
       
        .due-date.overdue {
            color: #d32f2f;
            font-weight: 500;
        }
       
        .due-date i {
            margin-left: 5px;
        }
       
        /* تنسيق أزرار الإجراءات */
        .action-buttons {
            display: flex;
            gap: 5px;
        }
       
        .action-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: none;
            background: #f0f0f0;
            color: #555;
            cursor: pointer;
            transition: all 0.2s;
        }
       
        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
       
        .action-btn.edit {
            color: #1976d2;
        }
       
        .action-btn.delete {
            color: #d32f2f;
        }
       
        .action-btn.reminder {
            color: #ff9800;
        }
       
        /* تنسيق القائمة المنسدلة للحالة */
        .status-select {
            padding: 5px 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 0.85em;
            background-color: white;
            cursor: pointer;
        }
       
        /* تنسيق حالة عدم وجود مهام */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 20px;
        }
       
        .empty-state i {
            font-size: 3em;
            color: #b0bec5;
            margin-bottom: 15px;
        }
       
        .empty-state h3 {
            color: #546e7a;
            margin-bottom: 10px;
        }
       
        .empty-state p {
            color: #90a4ae;
        }
         /* تنسيقات جديدة للفلترة */
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
       
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
       
        .filter-label {
            font-weight: 600;
            color: #4a6fa5;
        }
       
        .filter-select, .search-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 0.95em;
            min-width: 180px;
        }
       
        .search-input {
            flex-grow: 1;
            min-width: 250px;
        }
       
        .search-button {
            padding: 8px 15px;
            background-color: #4a6fa5;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
       
        .search-button:hover {
            background-color: #3a5a80;
        }
       
        @media (max-width: 768px) {
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
           
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
           
            .filter-select, .search-input {
                width: 100%;
            }
        }
    </style>
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
         
               <!-- فلترة المهام الجديدة -->
        <form method="GET" class="filter-container">
            <div class="filter-group">
                <label for="filter" class="filter-label">فلترة حسب:</label>
                <select name="filter" id="filter" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>جميع المهام</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>مهام معلقة</option>
                    <option value="in_progress" <?php echo $filter === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                    <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>مهام مكتملة</option>
                    <option value="overdue" <?php echo $filter === 'overdue' ? 'selected' : ''; ?>>مهام متأخرة</option>
                    <option value="due_soon" <?php echo $filter === 'due_soon' ? 'selected' : ''; ?>>قريبة من الاستحقاق</option>
                    <option value="archived" <?php echo $filter === 'archived' ? 'selected' : ''; ?>>مهام مؤرشفة</option>
                </select>
            </div>
           
            <div class="filter-group" style="flex-grow: 1;">
                <input type="text" name="search" class="search-input" placeholder="ابحث في المهام..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i> بحث
                </button>
            </div>
        </form>
           
         
        </div>
       
        <!-- قائمة المهام في جدول -->
        <?php if (count($tasks) > 0): ?>
            <div class="table-responsive">
                <table class="tasks-table">
                    <thead>
                        <tr>
                           
                            <th>المهمة</th>
                            <th>الحالة</th>
                            <th>الأولوية</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task):
                            $isOverdue = $task['due_date'] && 
                            strtotime($task['due_date']) < time() &&
                             !in_array($task['status'], ['completed', 'archived']); 
                           
                        ?> 
                            <tr>
                              
                                    <td>   
                                    <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                    <?php if (!empty($task['description'])): ?>
                                        <div class="task-description"><?php echo nl2br(htmlspecialchars($task['description'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $statuses[$task['status']]['class']; ?>">
                                        <i class="fas fa-<?php echo $statuses[$task['status']]['icon']; ?>"></i>
                                        <?php echo $statuses[$task['status']]['name']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?php echo $priorities[$task['priority']]['class']; ?>">
                                        <i class="fas fa-<?php echo $priorities[$task['priority']]['icon']; ?>"></i>
                                        <?php echo $priorities[$task['priority']]['name']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($task['due_date']): ?>
                                        <span class="due-date <?php echo $isOverdue ? 'overdue' : ''; ?>">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('Y/m/d H:i', strtotime($task['due_date'])); ?>
                                            <?php if ($isOverdue): ?>
                                                <i class="fas fa-exclamation-circle" style="color: #d32f2f; margin-right: 5px;"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="due-date">لا يوجد</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="action-btn edit" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_task.php?id=<?php echo $task['id']; ?>"
                                           class="action-btn delete"
                                           title="حذف"
                                           onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="add_reminder.php?task_id=<?php echo $task['id']; ?>" class="action-btn reminder" title="إضافة تذكير">
                                            <i class="fas fa-bell"></i>
                                        </a>
                                        <form method="POST" action="update_task_status.php" class="status-form">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <select name="new_status" onchange="this.form.submit()" class="status-select" title="تغيير الحالة">
                                                <?php foreach ($statuses as $key => $status): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $task['status'] === $key ? 'selected' : ''; ?>>
                                                        <?php echo $status['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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