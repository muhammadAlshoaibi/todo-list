<?php
//session_start();
require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/functions.php';

// إنشاء كائن اتصال بقاعدة البيانات

$db = db_Connect();

// معالجة إضافة مهمة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    
    $query = "INSERT INTO tasks (title, description, priority, due_date, created_at, user_id) 
              VALUES (?, ?, ?, ?, NOW(), ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssi", $title, $description, $priority, $due_date, $_SESSION['user_id']);
    $stmt->execute();
    
    header("Location: index.php");
    exit();
}

// جلب المهام من قاعدة البيانات
$user_id = $_SESSION['user_id'] ?? 0; // في حالة نظام تسجيل دخول
$query = "SELECT * FROM tasks WHERE user_id = ? ORDER BY 
          CASE priority 
              WHEN 'high' THEN 1 
              WHEN 'medium' THEN 2 
              WHEN 'low' THEN 3 
          END, due_date ASC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// حساب إحصائيات المهام
$total_tasks = count($tasks);
$completed_tasks = 0;
foreach ($tasks as $task) {
    if ($task['status']=='completed') {
        $completed_tasks++;
    }
}
$progress = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 'index' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        
    </style>
</head>
<body>
    <div class="container">
        <header class="app-header">
            <div class="logo">
                <i class="fas fa-tasks"></i>
                <h1><?php echo 'index' ?></h1>
            </div>
            <div class="user-profile">
                <?php if (isset($_SESSION['username'])): ?>
                    <img src="assets/images/<?php echo $_SESSION['avatar'] ?? 'default.png'; ?>" alt="صورة المستخدم">
                    <span>مرحباً، <?php echo $_SESSION['username']; ?></span>
                    <a href="logout.php" class="logout-btn">تسجيل خروج</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">تسجيل دخول</a>
                <?php endif; ?>
            </div>
        </header>

        <main class="app-content">
            <section class="sidebar">
                <nav class="main-nav">
                    <ul>
                        <li class="active"><a href="index.php"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li><a href="today.php"><i class="fas fa-calendar-alt"></i> اليوم</a></li>
                        <li><a href="important.php"><i class="fas fa-star"></i> مهم</a></li>
                        <li><a href="completed.php"><i class="fas fa-check-circle"></i> مكتمل</a></li>
                        <li><a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
                    </ul>
                </nav>

                <div class="progress-card">
                    <h3>تقدم المهام</h3>
                    <div class="progress-circle">
                        <svg class="progress-ring" width="120" height="120">
                            <circle class="progress-ring-circle-bg" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"/>
                            <circle class="progress-ring-circle" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"
                                    stroke-dasharray="326.56"
                                    stroke-dashoffset="<?php echo 326.56 - (326.56 * $progress) / 100; ?>"/>
                        </svg>
                        <div class="progress-text"><?php echo $progress; ?>%</div>
                    </div>
                    <p><?php echo $completed_tasks; ?> من <?php echo $total_tasks; ?> مهمة مكتملة</p>
                </div>
            </section>

            <section class="tasks-section">
                <div class="tasks-header">
                    <h2>مهامي</h2>
                    <div class="actions">
                        <button class="btn btn-primary" id="add-task-btn">
                            <i class="fas fa-plus"></i> إضافة مهمة
                        </button>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="task-search" placeholder="ابحث عن مهمة...">
                        </div>
                    </div>
                </div>

                <!-- نموذج إضافة مهمة (مخفى بشكل افتراضي) -->
                <div class="add-task-form" id="add-task-form" style="display: none;">
                    <form method="POST" action="index.php">
                        <div class="form-group">
                            <input type="text" name="title" placeholder="عنوان المهمة" required>
                        </div>
                        <div class="form-group">
                            <textarea name="description" placeholder="وصف المهمة"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>الأولوية:</label>
                                <select name="priority" required>
                                    <option value="high">عالي</option>
                                    <option value="medium" selected>متوسط</option>
                                    <option value="low">منخفض</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>تاريخ التسليم:</label>
                                <input type="date" name="due_date" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add_task" class="btn btn-primary">حفظ</button>
                            <button type="button" id="cancel-add-task" class="btn btn-secondary">إلغاء</button>
                        </div>
                    </form>
                </div>

                <div class="tasks-list">
                    <?php if (empty($tasks)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <p>لا توجد مهام لعرضها</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-card <?php echo $task['status'] ? 'completed' : ''; ?>">
                                <div class="task-checkbox">
                                    <a href="complete_task.php?id=<?php echo $task['id']; ?>">
                                        <?php if ($task['status']=='completed'): ?>
                                            <i class="fas fa-check-circle"></i>
                                        <?php else: ?>
                                            <i class="far fa-circle"></i>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="task-details">
                                    <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($task['description']); ?></p>
                                    <div class="task-meta">
                                        <span class="due-date"><i class="far fa-calendar-alt"></i> <?php echo formatDate($task['due_date']); ?></span>
                                        <span class="priority <?php echo $task['priority']; ?>">
                                            <?php 
                                            switch($task['priority']) {
                                                case 'high': echo 'عالي'; break;
                                                case 'medium': echo 'متوسط'; break;
                                                case 'low': echo 'منخفض'; break;
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <a href="edit_task.php?id=<?php echo $task['id']; ?>"><i class="fas fa-edit"></i></a>
                                    <a href="delete_task.php?id=<?php echo $task['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // إظهار/إخفاء نموذج إضافة مهمة
    const addTaskBtn = document.getElementById('add-task-btn');
    const addTaskForm = document.getElementById('add-task-form');
    const cancelAddTask = document.getElementById('cancel-add-task');
    
    if (addTaskBtn && addTaskForm) {
        addTaskBtn.addEventListener('click', function() {
            addTaskForm.style.display = 'block';
            addTaskBtn.style.display = 'none';
        });
    }
    
    if (cancelAddTask && addTaskForm) {
        cancelAddTask.addEventListener('click', function() {
            addTaskForm.style.display = 'none';
            addTaskBtn.style.display = 'flex';
        });
    }
    
    // البحث عن المهام
    const taskSearch = document.getElementById('task-search');
    if (taskSearch) {
        taskSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const taskCards = document.querySelectorAll('.task-card');
            
            taskCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    // تعيين تاريخ اليوم كقيمة افتراضية لتاريخ التسليم
    const dueDateInput = document.querySelector('input[name="due_date"]');
    if (dueDateInput) {
        const today = new Date().toISOString().split('T')[0];
        dueDateInput.value = today;
        dueDateInput.min = today;
    }
});
</script>
    <script src="assets/js/script.js"></script>
</body>
</html>