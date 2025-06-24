<?php
require_once 'config.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// تحديث الإشعارات كمقروءة عند زيارة الصفحة
$update_query = "UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// الحصول على جميع الإشعارات
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-bell"></i> الإشعارات</h1>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> رجوع
            </a>
        </div>
        
        <?php if (count($notifications) > 0): ?>
            <div class="notifications-container">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="notification-header">
                            <h3><?php echo htmlspecialchars($notification['title']); ?></h3>
                            <small><?php echo date('Y/m/d H:i', strtotime($notification['created_at'])); ?></small>
                        </div>
                        <div class="notification-body">
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        </div>
                        <?php if ($notification['related_id'] && $notification['related_type'] === 'task'): ?>
                            <div class="notification-footer">
                                <a href="tasks.php?id=<?php echo $notification['related_id']; ?>" class="btn btn-small">
                                    <i class="fas fa-eye"></i> عرض المهمة
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>لا توجد إشعارات</h3>
                <p>سيظهر هنا أي إشعارات جديدة تتلقاها</p>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>