<?php
require_once 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tasks.php");
    exit();
}

$conn = db_connect();
$user_id = $_SESSION['user_id'];

//$data = json_decode(file_get_contents('php://input'), true);
$task_id = (int)$_POST['task_id'];
$new_status = sanitize_input($_POST['new_status']);

// التحقق من أن المهمة تخص المستخدم
$query = "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $new_status, $task_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // تسجيل النشاط
    log_activity('update_task', 'تم تحديث حالة المهمة إلى ' . $new_status);
    
    // إرسال إشعار
    $task_query = "SELECT title FROM tasks WHERE id = ?";
    $task_stmt = $conn->prepare($task_query);
    $task_stmt->bind_param("i", $task_id);
    $task_stmt->execute();
    $task = $task_stmt->get_result()->fetch_assoc();
    $task_stmt->close();
    
    $notification_title = "تم تحديث حالة المهمة";
    $notification_message = "تم تغيير حالة المهمة '{$task['title']}' إلى '{$new_status}'";
    
    $notification_query = "INSERT INTO notifications (user_id, title, message, related_type, related_id) 
                          VALUES (?, ?, ?, 'task', ?)";
    $notification_stmt = $conn->prepare($notification_query);
    $notification_stmt->bind_param("issi", $user_id, $notification_title, $notification_message, $task_id);
    $notification_stmt->execute();
    $notification_stmt->close();
    
    echo 'success'  ;
    header('location:tasks.php');
} else {
    echo json_encode(['success' => false, 'error' => 'لم يتم العثور على المهمة أو لا يوجد تغيير']);
}

$stmt->close();
$conn->close();
?>