<?php
//session_start();
require 'config.php';

// الاتصال بقاعدة البيانات
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات: " . mysqli_connect_error());
}

// معالجة عمليات التحديث والحذف
// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     if (isset($_POST['update_status'])) {
//         $task_id = (int)$_POST['task_id'];
//         $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
//         mysqli_query($conn, "UPDATE tasks SET status='$new_status', updated_at=NOW() WHERE id=$task_id");
//     } elseif (isset($_POST['delete_task'])) {
//         $task_id = (int)$_POST['task_id'];
//         mysqli_query($conn, "DELETE FROM tasks WHERE id=$task_id");
//     }
// }

// جلب المهام مع فلترة حسب الأولوية والحالة
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$filter_priority = isset($_GET['priority']) ? mysqli_real_escape_string($conn, $_GET['priority']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$query = "SELECT * FROM tasks WHERE user_id=$user_id and status='completed'";
if (!empty($filter_priority)) {
    $query .= " AND priority='$filter_priority'";
}
if (!empty($filter_status)) {
    $query .= " AND status='$filter_status'";
}
$query .= " ORDER BY
    CASE priority
        WHEN 'high' THEN 1
        WHEN 'medium' THEN 2
        WHEN 'low' THEN 3
        ELSE 4
    END, due_date ASC";

$result = mysqli_query($conn, $query);
$tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);

// إحصائيات المهام
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN due_date < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue
FROM tasks WHERE user_id=$user_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المهام</title>
    <style>
        :root {
            --high-priority: #ff6b6b;
            --medium-priority: #ffd166;
            --low-priority: #06d6a0;
            --completed: #4ecdc4;
            --pending: #ff9f1c;
            --overdue: #ef476f;
        }
        body {
            font-family: 'Tajawal', Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        select, button {
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            width: 100%;
            font-family: inherit;
        }
        .task-list {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .task-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        .task-item:hover {
            background: #f8f9fa;
        }
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .task-title {
            font-weight: bold;
            font-size: 1.2rem;
            margin: 0;
        }
        .priority {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .priority-high { background: var(--high-priority); color: white; }
        .priority-medium { background: var(--medium-priority); color: #333; }
        .priority-low { background: var(--low-priority); color: white; }
        .task-meta {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            font-size: 0.9rem;
            color: #666;
        }
        .task-due {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
        }
        .status-pending { background: var(--pending); color: white; }
        .status-completed { background: var(--completed); color: white; }
        .status-overdue { background: var(--overdue); color: white; }
        .task-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-edit {
            background: #4cc9f0;
            color: white;
        }
        .btn-delete {
            background: #ef476f;
            color: white;
        }
        .btn-status {
            background: #7209b7;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php';?>
    <div class="container">
        <div class="header">
            <h1>نظام إدارة المهام</h1>
            <p>تابع مهامك وأنشطتك في مكان واحد</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>إجمالي المهام</h3>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <p>جميع المهام المسجلة</p>
            </div>
            <div class="stat-card">
                <h3>مكتملة</h3>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <p>المهام المنتهية</p>
            </div>

            <div class="stat-card">
                <h3>متأخرة</h3>
                <div class="stat-value"><?php echo $stats['overdue']; ?></div>
                <p>المهام المتأخرة عن الموعد</p>
            </div>
        </div>

        <div class="filters">
            <!-- <div class="filter-group">
                <select id="priority-filter" onchange="applyFilters()">
                    <option value="">جميع الأولويات</option>
                    <option value="high" <?php echo $filter_priority=='high'?'selected':''; ?>>عالي</option>
                    <option value="medium" <?php echo $filter_priority=='medium'?'selected':''; ?>>متوسط</option>
                    <option value="low" <?php echo $filter_priority=='low'?'selected':''; ?>>منخفض</option>
                </select>
            </div> -->
            <div class="filter-group">
                <!-- <select id="status-filter" onchange="applyFilters()">
                    <option value="">جميع الحالات</option>
                    <option value="pending" <?php echo $filter_status=='pending'?'selected':''; ?>>قيد الانتظار</option>
                    <option value="completed" <?php echo $filter_status=='completed'?'selected':''; ?>>مكتملة</option>
                </select> -->
            </div>
            <!-- <div class="filter-group">
                <button onclick="resetFilters()">إعادة تعيين الفلاتر</button>
            </div> -->
        </div>
            <div></div>
        <div class="task-list">
            <h2> المهام المكتملة </h2>
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <h3>لا توجد مهام لعرضها</h3>
                    <p>يمكنك إضافة مهام جديدة لتبدأ في تنظيم عملك</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item">
                        <div class="task-header">
                            <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                            <span class="priority priority-<?php echo $task['priority']; ?>">
                                <?php
                                    echo $task['priority'] == 'high' ? 'عالي' :
                                         ($task['priority'] == 'medium' ? 'متوسط' : 'منخفض');
                                ?>
                            </span>
                        </div>
                       
                        <?php if (!empty($task['description'])): ?>
                            <p><?php echo htmlspecialchars($task['description']); ?></p>
                        <?php endif; ?>
                       
                        <div class="task-meta">
                            <div class="task-due">
                                <span>تاريخ التسليم:</span>
                                <strong><?php echo date('Y-m-d', strtotime($task['due_date'])); ?></strong>
                                <?php if ($task['status'] != 'completed' && strtotime($task['due_date']) < time()): ?>
                                    <span class="status status-overdue">متأخرة</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span>الحالة:</span>
                                <span class="status status-<?php echo $task['status']; ?>">
                                    <?php echo $task['status'] == 'completed' ? 'مكتملة' : 'قيد الانتظار'; ?>
                                </span>
                            </div>
                            <div>
                                <span>آخر تحديث:</span>
                                <strong><?php echo date('Y-m-d H:i', strtotime($task['updated_at'])); ?></strong>
                            </div>
                        </div>
                       
                        <div class="task-actions">
                            <!-- <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <select name="new_status" onchange="this.form.submit()" class="btn btn-status">
                                    <option value="pending" <?php echo $task['status']=='pending'?'selected':''; ?>>قيد الانتظار</option>
                                    <option value="completed" <?php echo $task['status']=='completed'?'selected':''; ?>>مكتملة</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form> -->
                            <!-- <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="delete_task" class="btn btn-delete">حذف</button>
                            </form> -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>   
        <a href="tasks.php" class="btn"><button  type="button"><i class="fa fas-eyes"></i>view all tasks</button></a>
    </div>
 

    <script>
        function applyFilters() {
            const priority = document.getElementById('priority-filter').value;
            const status = document.getElementById('status-filter').value;
           
            let url = 'index.php?';
            if (priority) url += `priority=${priority}&`;
            if (status) url += `status=${status}`;
           
            window.location.href = url;
        }
       
        function resetFilters() {
            window.location.href = 'index.php';
        }
    </script>
    <?php include 'includes/footer.php';?>
</body>
</html>