<?php
require_once 'config.php';

$conn = db_connect();

// الحصول على التذكيرات المستحقة
$current_time = date('Y-m-d H:i:s');
$query = "SELECT r.*, t.title as task_title, u.email, u.username 
          FROM reminders r
          JOIN tasks t ON r.task_id = t.id
          JOIN users u ON r.user_id = u.id
          WHERE r.remind_at <= ? AND r.is_sent = FALSE";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $current_time);
$stmt->execute();
$reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($reminders as $reminder) {
    // إرسال الإشعارات
    if (in_array($reminder['method'], ['notification', 'both'])) {
        $title = "تذكير: " . $reminder['task_title'];
        $message = "حان وقت المهمة: " . $reminder['task_title'];
        
        $insert_query = "INSERT INTO notifications (user_id, title, message, related_type, related_id) 
                         VALUES (?, ?, ?, 'task', ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("issi", $reminder['user_id'], $title, $message, $reminder['task_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // إرسال البريد الإلكتروني
    if (in_array($reminder['method'], ['email', 'both'])) {
        $to = $reminder['email'];
        $subject = "تذكير بالمهمة: " . $reminder['task_title'];
        $message = "
            <html>
            <head>
                <title>تذكير بالمهمة</title>
            </head>
            <body>
                <h2>مرحباً {$reminder['userename']}</h2>
                <p>هذا تذكير بالمهمة: <strong>{$reminder['task_title']}</strong></p>
                <p>وقت التذكير: {$reminder['remind_at']}</p>
                <p><a href=\"".BASE_URL."task.php?id={$reminder['task_id']}\">عرض المهمة</a></p>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: ".MAIL_FROM_NAME." <".MAIL_FROM.">\r\n";
        
        mail($to, $subject, $message, $headers);
    }
    
    // تحديث حالة التذكير كمرسل
    $update_query = "UPDATE reminders SET is_sent = TRUE WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $reminder['id']);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo "تم معالجة التذكيرات بنجاح";
?>