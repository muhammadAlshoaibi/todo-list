<?php
require_once 'config.php';

$m=sanitize_input("lion");
echo $m;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password, email,  role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password===$user['password']) {
            // تسجيل بيانات المستخدم في الجلسة
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // تحديث وقت آخر دخول
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // تسجيل النشاط
            log_activity('login', 'تم تسجيل الدخول بنجاح');
            
            header("Location: dashboard.php");
            exit();
        }
    }
    
    $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> اسم المستخدم</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> كلمة المرور</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </button>
            </form>
            
            <div class="auth-links">
                <a href="register.php"><i class="fas fa-user-plus"></i> إنشاء حساب جديد</a>
                <a href="forgot-password.php"><i class="fas fa-key"></i> نسيت كلمة المرور؟</a>
            </div>
        </div>
    </div>
</body>
</html>