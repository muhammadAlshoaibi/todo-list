<?php
//session_start();
require_once 'config.php';
$conn=db_connect();
// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// جلب إحصائيات المهام
$stats_query = "SELECT COUNT(*) as total_tasks, 
SUM(status='completed') as completed_tasks,
    SUM(CASE WHEN status!='completed' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue_tasks,
 SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as 'high_priority',
  SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as 'medium_priority',
   SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as 'low_priority' FROM tasks
    WHERE user_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// جلب توزيع المهام حسب الفئة
$categories_query = "
    SELECT  COUNT(*) as count
    FROM tasks
    WHERE user_id = ? 

    ORDER BY count DESC
    LIMIT 5
";
$stmt = $conn->prepare($categories_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// جلب توزيع المهام حسب الشهر
$monthly_query = "
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM tasks
    WHERE user_id = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
    LIMIT 6
";
$stmt = $conn->prepare($monthly_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإحصائيات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s;
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
    </style>
</head>
<body>
    <?php //include 'includes/header.php'; ?>
   
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-chart-bar me-3"></i>الإحصائيات</h1>
                    <p class="mb-0">تحليل أداء المهام وأنماط العمل</p>
                </div>
                <div class="col-md-6 text-start">
                    <div class="card bg-white text-dark stat-card">
                        <div class="card-body">
                            <h5 class="card-title">ملخص الأداء</h5>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['completed_tasks'] ?></h3>
                                    <small>مكتملة</small>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= $stats['total_tasks'] - $stats['completed_tasks'] ?></h3>
                                    <small>معلقة</small>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= $stats['overdue_tasks'] ?></h3>
                                    <small>متأخرة</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3><?= $stats['total_tasks'] ?></h3>
                        <p class="mb-0">المهام الكلية</p>
                    </div>
                </div>
            </div>
           
            <div class="col-md-4">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body text-center">
                        <h3><?= $stats['completed_tasks'] ?></h3>
                        <p class="mb-0">مهام مكتملة</p>
                    </div>
                </div>
            </div>
           
            <div class="col-md-4">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3><?= $stats['overdue_tasks'] ?></h3>
                        <p class="mb-0">مهام متأخرة</p>
                    </div>
                </div>
            </div>
        </div>
       
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">توزيع المهام حسب الأولوية</h5>
                        <div class="chart-container">
                            <canvas id="priorityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
           
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">توزيع المهام حسب الفئة</h5>
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">نشاط المهام خلال الأشهر الماضية</h5>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">معدل الإنجاز</h5>
                        <div class="progress mb-3" style="height: 30px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: <?= $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0 ?>%"
                                 aria-valuenow="<?= $stats['completed_tasks'] ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="<?= $stats['total_tasks'] ?>">
                                <?= $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0 ?>%
                            </div>
                        </div>
                        <p class="text-muted"><?= $stats['completed_tasks'] ?> من <?= $stats['total_tasks'] ?> مهمة مكتملة</p>
                    </div>
                </div>
            </div>
           
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">أعلى الفئات</h5>
                        <?php if (!empty($categories)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($categories as $category): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                      
                                        <span class="badge bg-primary rounded-pill"><?= $category['count'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info">لا توجد بيانات عن الفئات</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <script>
        // مخطط الأولويات
        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        const priorityChart = new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: ['عالية', 'متوسطة', 'منخفضة'],
                datasets: [{
                    data: [
                        <?= $stats['high_priority'] ?>,
                        <?= $stats['medium_priority'] ?>,
                        <?= $stats['low_priority'] ?>
                    ],
                    backgroundColor: [
                        '#dc3545',
                        '#ffc107',
                        '#28a745'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true
                    }
                }
            }
        });
       
        // مخطط الفئات
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($cat) { return "'" . htmlspecialchars($cat['category']) . "'"; }, $categories)); ?>],
                datasets: [{
                    label: 'عدد المهام',
                    data: [<?php echo implode(',', array_column($categories, 'count')); ?>],
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
       
        // مخطط شهري
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($month) { return "'" . $month['month'] . "'"; }, $monthly_stats)); ?>],
                datasets: [{
                    label: 'عدد المهام',
                    data: [<?php echo implode(',', array_column($monthly_stats, 'count')); ?>],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>
