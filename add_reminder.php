<?php
require_once 'config.php';
//require_login();

// if (!isset($_GET['task_id'])) {
//     header("Location: tasks.php");
//     exit();
// }

$conn = db_connect();
$user_id = 1;
$task_id = (int)$_GET['task_id'];

// التحقق من أن المهمة تخص المستخدم
$task_query = "SELECT id, title FROM tasks WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($task_query);
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task_result = $stmt->get_result();

if ($task_result->num_rows === 0) {
    echo 'hello'; 
    // header("Location: tasks.php");
    // exit();
}

$task = $task_result->fetch_assoc();
$stmt->close();

// معالجة إضافة التذكير
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remind_at = sanitize_input($_POST['remind_at']);
    $method = sanitize_input($_POST['method']);
    
    $insert_query = "INSERT INTO reminders (task_id, user_id, remind_at, method) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiss", $task_id, $user_id, $remind_at, $method);
    
    if ($stmt->execute()) {
        // إضافة إشعار للمستخدم
        $notification_title = "تم إضافة تذكير جديد";
        $notification_message = "تم إضافة تذكير للمهمة: " . $task['title'];
        
        $notification_query = "INSERT INTO notifications (user_id, title, message, related_type, related_id) 
                              VALUES (?, ?, ?, 'task', ?)";
        $stmt2 = $conn->prepare($notification_query);
        $stmt2->bind_param("issi", $user_id, $notification_title, $notification_message, $task_id);
        $stmt2->execute();
        $stmt2->close();
        
        header("Location: task.php?id=$task_id");
        exit();
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تذكير</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-bell"></i> إضافة تذكير للمهمة: <?php echo htmlspecialchars($task['title']); ?></h1>
            <a href="tasks.php?id=<?php echo $task_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> رجوع
            </a>
        </div>
        
        <form method="POST" class="reminder-form">
            <div class="form-group">
                <label for="remind_at"><i class="fas fa-clock"></i> وقت التذكير</label>
                <input type="datetime-local" id="remind_at" name="remind_at" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> طريقة التذكير</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="method" value="notification" checked>
                        <i class="fas fa-bell"></i> إشعار في التطبيق
                    </label>
                    <label>
                        <input type="radio" name="method" value="email">
                        <i class="fas fa-envelope"></i> بريد إلكتروني
                    </label>
                    <label>
                        <input type="radio" name="method" value="both">
                        <i class="fas fa-bell"></i> <i class="fas fa-envelope"></i> كليهما
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> حفظ التذكير
            </button>
        </form>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>