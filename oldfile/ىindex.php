<?php
    require_once 'config.php';

    // إذا كان المستخدم مسجل الدخول، توجيهه للوحة التحكم
    // if (is_logged_in()) {
    //     header("Location: dashboards.php");
    //     exit();
    // }

    // إذا لم يكن مسجل الدخول، عرض الصفحة الرئيسية
    $conn = db_connect();

    // الحصول على إحصائيات عامة (للعرض للزوار)
    $stats_query = "SELECT 
        COUNT(*) as total_users,
        (SELECT COUNT(*) FROM tasks) as total_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status = 'completed') as completed_tasks
        FROM users";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();

    // الحصول على آخر المهام المكتملة (للعرض للزوار)
    $recent_tasks_query = "SELECT t.title, t.created_at, u.username 
                        FROM tasks t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.status = 'completed'
                        ORDER BY t.created_at DESC 
                        LIMIT 5";
    $recent_tasks_result = $conn->query($recent_tasks_query);
    $recent_tasks = $recent_tasks_result->fetch_all(MYSQLI_ASSOC);

    $conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - نظام إدارة المهام</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .feature-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<!-- في header.php أو قبل نهاية </body> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>




<body>
    <?php include 'includes/header.php'; ?>
    
    <section class="hero-section">
        <div class="container">
            <h1><i class="fas fa-tasks"></i> نظام إدارة المهام المتكامل</h1>
            <p class="lead">نظم مهامك، حدد أولوياتك، أنجز أعمالك في وقتها</p>
            
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i> إنشاء حساب جديد
                </a>
                <a href="login.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </a>
            </div>
        </div>
    </section>
    
    <main class="main-content">
        <div class="container">
            <!-- الإحصائيات السريعة -->
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>مستخدم مسجل</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_tasks']; ?></h3>
                        <p>مهمة تم إنشاؤها</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed_tasks']; ?></h3>
                        <p>مهمة مكتملة</p>
                    </div>
                </div>
            </div>
            
            <!-- المميزات الرئيسية -->
            <section class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3>نظام متعدد المستخدمين</h3>
                    <p>إدارة حسابات متعددة مع صلاحيات مختلفة لكل مستخدم</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>تواريخ الاستحقاق</h3>
                    <p>تحديد تواريخ نهائية للمهام وتنبيهات عند الاقتراب منها</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>التذكيرات والإشعارات</h3>
                    <p>تذكيرات عبر الإشعارات والبريد الإلكتروني لضمان عدم تفويت أي مهمة</p>
                </div>
            </section>
            
            <!-- آخر المهام المكتملة -->
            <section class="recent-tasks">
                <h2><i class="fas fa-history"></i> آخر المهام المكتملة</h2>
                
                <?php if (count($recent_tasks) > 0): ?>
                    <div class="tasks-list">
                        <?php foreach ($recent_tasks as $task): ?>
                            <div class="task-item">
                                <div class="task-info">
                                    <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                                    <p>
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($task['username']); ?>
                                        <span class="task-date">
                                            <i class="fas fa-calendar-alt"></i> 
                                            <?php echo date('Y/m/d', strtotime($task['created_at'])); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>لا توجد مهام مكتملة لعرضها</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <script>
    // عرض إشعار عند تحميل الصفحة (إذا كان هناك مهام متأخرة)
    <?php if ($basicStats['overdue_tasks'] > 0): ?>
    toastr.warning('لديك <?php echo $basicStats['overdue_tasks']; ?> مهام متأخرة!', 'تنبيه');
<?php endif; ?>
</script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>