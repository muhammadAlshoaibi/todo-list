<?php 
      require_once 'config.php';
    
    $pageSubtitle = '   انشاء حساب لادارة مهامك بسهولة ';

   include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/styles.css">
<!-- <link rel="stylesheet" href="css/style.css"> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<!-- <div class="auth-container">
    <h2>إنشاء حساب جديد</h2> -->
    <form action="../controllers/AuthController.php?action=register" method="POST">
        <!-- <div class="form-group">
            <label for="name">الاسم الكامل:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">كلمة المرور:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">تأكيد كلمة المرور:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn">إنشاء الحساب</button>
    </form>
    <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول</a></p>
</div> -->

  <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-user-plus"></i>  إنشاء حساب جديد </h1>
            
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
                    <label for="username"><i class="fas fa-google"></i>  البريد الالكتروني </label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> كلمة المرور</label>
                    <input type="password" id="password" name="password" required>
                </div>
                     <div class="form-group">
                    <label for="cpassword"><i class="fas fa-lock"></i> تأكيد كلمة المرور </label>
                    <input type="password" id="cpassword" name="cpassword" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                   <i class="fas fa-user-plus"></i>   إنشاء حساب 
                </button>
            </form>
            
            <div class="auth-links">
                <a href="register.php"><i class="fas fa-sign-in-alt"></i>  تسجيل دخول </a>
                <a href="forgot-password.php"><i class="fas fa-key"></i> نسيت كلمة المرور؟</a>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>