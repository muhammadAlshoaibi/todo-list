<?php
//session_start();
require_once 'config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$conn=db_connect();
// جلب بيانات المستخدم الحالي
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// جلب المهام الخاصة بالمستخدم الحالي
$query = "SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date, priority DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المهام</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .task-card {
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .task-card:hover {
            transform: translateY(-3px);
        }
        .high-priority { border-right: 4px solid #dc3545; }
        .medium-priority { border-right: 4px solid #ffc107; }
        .low-priority { border-right: 4px solid #28a745; }
        .completed { opacity: 0.7; background-color: #f8f9fa; }
        .task-title { font-weight: bold; }
        .due-date { color: #6c757d; font-size: 0.9em; }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">نظام المهام</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="team_tasks.php">مهام الفريق</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">المشاريع</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="uploads/<?= htmlspecialchars($user['avatar']) ?>" class="user-avatar me-1">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($user['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>الملف الشخصي</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>الإعدادات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- رسائل التنبيه -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- العمود الجانبي -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">لوحة التحكم</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column">
                            <a href="add_task.php" class="btn btn-success mb-3">
                                <i class="fas fa-plus me-2"></i>إضافة مهمة
                            </a>
                            <a href="calendar.php" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>الرزنامة
                            </a>
                            <a href="reports.php" class="btn btn-outline-secondary mb-3">
                                <i class="fas fa-chart-bar me-2"></i>التقارير
                            </a>
                        </div>
                       
                        <hr>
                       
                        <h6 class="mt-3">إحصائيات</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                المهام الكلية
                                <span class="badge bg-primary rounded-pill"><?= count($tasks) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                المكتملة
                                <span class="badge bg-success rounded-pill">
                                    <?= count(array_filter($tasks, fn($task) => $task['status']=='completed')) ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                المعلقة
                                <span class="badge bg-warning rounded-pill">
                                    <?= count(array_filter($tasks, fn($task) => $task['status']!='completed')) ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
           
            <!-- المحتوى الرئيسي -->
            <div class="col-lg-9">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">مهامي</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-filter me-1"></i>تصفية
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header">حالة المهمة</h6></li>
                                    <li><a class="dropdown-item" href="?filter=all">الكل</a></li>
                                    <li><a class="dropdown-item" href="?filter=completed">المكتملة</a></li>
                                    <li><a class="dropdown-item" href="?filter=pending">المعلقة</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">الأولوية</h6></li>
                                    <li><a class="dropdown-item" href="?priority=high">عالية</a></li>
                                    <li><a class="dropdown-item" href="?priority=medium">متوسطة</a></li>
                                    <li><a class="dropdown-item" href="?priority=low">منخفضة</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-tasks fa-2x mb-3"></i>
                                <h5>لا توجد مهام مسجلة حالياً</h5>
                                <p class="mb-0">يمكنك إضافة مهمة جديدة بالضغط على زر "إضافة مهمة"</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الحالة</th>
                                            <th>المهمة</th>
                                            <th>الأولوية</th>
                                            <th>تاريخ الاستحقاق</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                            <tr class="<?= $task['status']=='completed' ? 'completed' : '' ?>">
                                                <td>
                                                    <form action="toggle_task.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                        <button type="submit" class="btn btn-sm">
                                                            <i class="fas fa-<?= $task['status']=='completed' ? 'check-circle text-success' : 'circle' ;?>"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <a href="view_task.php?id=<;?= $task['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($task['title']) ?>
                                                        <?php if (!empty($task['category'])): ?>
                                                            <span class="badge bg-secondary ms-2"><?= htmlspecialchars($task['category']) ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php if ($task['priority'] == 'high'): ?>
                                                        <span class="badge bg-danger">عالية</span>
                                                    <?php elseif ($task['priority'] == 'medium'): ?>
                                                        <span class="badge bg-warning text-dark">متوسطة</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">منخفضة</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($task['due_date']): ?>
                                                        <?= date('Y-m-d', strtotime($task['due_date'])) ?>
                                                        <?php if (date('Y-m-d') > $task['due_date'] && $task['status']!='completed'): ?>
                                                            <span class="badge bg-danger ms-2">متأخرة</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">بدون تاريخ</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="edit_task.php?id=<;?= $task['id'] ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete_task.php?id=<;?= $task['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
               
                <!-- مهام الفريق (إذا كان المستخدم جزء من فريق) -->
                <?php
                $team_query = "SELECT * FROM tasks
                              
                              WHERE user_id = ?";
                $stmt = $conn->prepare($team_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
               
                if (!empty($teams)): ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">مهام الفريق</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="teamTabs" role="tablist">
                                <?php foreach ($teams as $index => $team): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $index === 0 ? 'active' : '' ?>"
                                                id="team-<?= $team['id'] ?>-tab"
                                                data-bs-toggle="tab"
                                                data-bs-target="#team-<?= $team['id'] ?>"
                                                type="button">
                                            <?= htmlspecialchars($team['title']) ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="tab-content p-3 border border-top-0 rounded-bottom">
                                <?php foreach ($teams as $index => $team): ?>
                                    <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>"
                                         id="team-<?= $team['id'] ?>"
                                         role="tabpanel">
                                        <?php
                                        $team_tasks_query = "SELECT tasks.*, users.username as assignee_name
                                                           FROM tasks
                                                          
                                                           JOIN users ON tasks.user_id = users.id
                                                           WHERE tasks.user_id = ?";
                                        $stmt = $conn->prepare($team_tasks_query);
                                        $stmt->bind_param("i", $team['user_id']);
                                        $stmt->execute();
                                        $team_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                        ?>
                                       
                                        <?php if (empty($team_tasks)): ?>
                                            <div class="alert alert-info">
                                                لا توجد مهام لفريق <?= htmlspecialchars($team['name']) ?> حالياً
                                            </div>
                                        <?php else: ?>
                                            <div class="list-group">
                                                <?php foreach ($team_tasks as $team_task): ?>
                                                    <a href="view_task.php?id=<;?= $team_task['id'] ?>"
                                                       class="list-group-item list-group-item-action">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1">
                                                                <?= htmlspecialchars($team_task['title']) ?>
                                                                <?php if ($team_task['user_id'] == $user_id): ?>
                                                                    <span class="badge bg-primary">مسندة لي</span>
                                                                <?php endif; ?>
                                                            </h6>
                                                            <small>
                                                                <?= date('Y-m-d', strtotime($team_task['due_date'])) ?>
                                                            </small>
                                                        </div>
                                                        <p class="mb-1"><?= htmlspecialchars(substr($team_task['description'], 0, 100)) ?>...</p>
                                                        <small>
                                                            <i class="fas fa-user"></i>
                                                            <?= htmlspecialchars($team_task['assignee_name']) ?>
                                                        </small>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تفعيل عناصر Bootstrap التي تحتاج إلى JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // تفعيل dropdowns
            var dropdownElements = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElements.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
           
            // تفعيل tabs
            var tabElements = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'));
            tabElements.map(function (tabEl) {
                return new bootstrap.Tab(tabEl);
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>