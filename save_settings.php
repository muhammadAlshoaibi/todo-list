<?php
require_once 'config.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جمع البيانات من النموذج
    $color_theme = sanitize_input($_POST['color_theme'] ?? 'default');
    $font_size = sanitize_input($_POST['font_size'] ?? 'medium');
    $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
    $sound_notifications = isset($_POST['sound_notifications']) ? 1 : 0;
    
    // التحقق من صحة البيانات
    $allowed_themes = ['default', 'green', 'blue', 'orange'];
    if (!in_array($color_theme, $allowed_themes)) {
        $errors['color_theme'] = 'نمط الألوان غير صالح';
    }
    
    $allowed_sizes = ['small', 'medium', 'large'];
    if (!in_array($font_size, $allowed_sizes)) {
        $errors['font_size'] = 'حجم الخط غير صالح';
    }
    
    // إذا لم تكن هناك أخطاء، حفظ الإعدادات
    if (empty($errors)) {
        // في نظام حقيقي، قد يتم حفظ هذه الإعدادات في جدول مستخدمين أو جدول إعدادات منفصل
        // هنا نستخدم الجلسة كمثال فقط
        $_SESSION['user_settings'] = [
            'color_theme' => $color_theme,
            'font_size' => $font_size,
            'enable_notifications' => $enable_notifications,
            'sound_notifications' => $sound_notifications
        ];
        
        // تسجيل النشاط
        log_activity('update_settings', 'تم تحديث إعدادات المستخدم');
        
        $success = true;
        
        // إرسال إشعار للمستخدم
        $notification_title = "تم تحديث الإعدادات";
        $notification_message = "تم حفظ تغييرات الإعدادات بنجاح";
        
        $notification_query = "INSERT INTO notifications (user_id, title, message, related_type) 
                              VALUES (?, ?, ?, 'system')";
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();

if ($success) {
    header("Location: settings.php?success=1");
} else {
    header("Location: settings.php?error=" . urlencode(implode(', ', $errors)));
}
exit();