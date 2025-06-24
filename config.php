<?php
// config.php - Configuration file for the application
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'todo_list');
// define('DB_USER', 'root');
// define('DB_PASS', '');

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'todo_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// إعدادات التطبيق
define('APP_NAME', 'نظام إدارة المهام');
define('APP_VERSION', '3.0');
define('BASE_URL', 'http://localhost/todo-system/');
define('DEFAULT_TIMEZONE', 'Asia/Riyadh');

// إعدادات البريد الإلكتروني
define('MAIL_FROM', 'alshuaibimuhammad@gmail.com');
define('MAIL_FROM_NAME', 'نظام إدارة المهام');

// جلسة المستخدم
session_start();

// وظيفة الاتصال بقاعدة البيانات
function db_connect() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

// وظائف الأمان
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// تحقق من تسجيل الدخول
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// تحقق من صلاحيات المدير
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// توجيه المستخدم إذا لم يكن مسجل الدخول
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

// تسجيل النشاط
function log_activity($action, $description = null) {
    $conn = db_connect();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $action, $description, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>