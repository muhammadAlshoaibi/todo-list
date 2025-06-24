<?php
require_once 'config.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

$errors = [];
$priorities = ['low' => 'منخفض', 'medium' => 'متوسط', 'high' => 'عالي', 'urgent' => 'عاجل'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جمع البيانات من النموذج
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $priority = sanitize_input($_POST['priority']);
    $due_date = !empty($_POST['due_date']) ? sanitize_input($_POST['due_date']) : null;
    $reminder = isset($_POST['reminder']) ? sanitize_input($_POST['reminder']) : null;
    $reminder_time = !empty($_POST['reminder_time']) ? sanitize_input($_POST['reminder_time']) : null;
    $reminder_method = isset($_POST['reminder_method']) ? sanitize_input($_POST['reminder_method']) : 'notification';

    // التحقق من صحة البيانات
    if (empty($title)) {
        $errors['title'] = 'عنوان المهمة مطلوب';
    }

    if (empty($priority) || !array_key_exists($priority, $priorities)) {
        $errors['priority'] = 'يجب اختيار أولوية صحيحة';
    }

    // إذا لم تكن هناك أخطاء، إضافة المهمة
    if (empty($errors)) {
        $insert_query = "INSERT INTO tasks (user_id, title, description, priority, due_date) 
                         VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("issss", $user_id, $title, $description, $priority, $due_date);
        
        if ($stmt->execute()) {
            $task_id = $stmt->insert_id;
            
            // إضافة تذكير إذا تم تحديده
            if ($reminder && $reminder_time) {
                $reminder_query = "INSERT INTO reminders (task_id, user_id, remind_at, method) 
                                  VALUES (?, ?, ?, ?)";
                $reminder_stmt = $conn->prepare($reminder_query);
                $reminder_stmt->bind_param("iiss", $task_id, $user_id, $reminder_time, $reminder_method);
                $reminder_stmt->execute();
                $reminder_stmt->close();
            }
            
            // إضافة إشعار للمستخدم
            $notification_title = "تم إضافة مهمة جديدة";
            $notification_message = "تم إضافة المهمة: " . $title;
            
            $notification_query = "INSERT INTO notifications (user_id, title, message, related_type, related_id) 
                                  VALUES (?, ?, ?, 'task', ?)";
            $notification_stmt = $conn->prepare($notification_query);
            $notification_stmt->bind_param("issi", $user_id, $notification_title, $notification_message, $task_id);
            $notification_stmt->execute();
            $notification_stmt->close();
            
            // تسجيل النشاط
            log_activity('add_task', 'تم إضافة مهمة جديدة: ' . $title);
            
            header("Location: task.php?id=$task_id");
            exit();
        } else {
            $errors['general'] = 'حدث خطأ أثناء إضافة المهمة';
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مهمة جديدة</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .task-form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            flex: 1;
        }
        
        .reminder-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> إضافة مهمة جديدة</h1>
            <a href="tasks.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> رجوع
            </a>
        </div>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="task-form">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading"></i> عنوان المهمة *</label>
                <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                <?php if (isset($errors['title'])): ?>
                    <span class="error-message"><?php echo $errors['title']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> الوصف</label>
                <textarea id="description" name="description" rows="5"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="priority"><i class="fas fa-flag"></i> الأولوية *</label>
                    <select id="priority" name="priority" required>
                        <?php foreach ($priorities as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['priority']) && $_POST['priority'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['priority'])): ?>
                        <span class="error-message"><?php echo $errors['priority']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="due_date"><i class="fas fa-calendar-alt"></i> تاريخ الاستحقاق</label>
                    <input type="datetime-local" id="due_date" name="due_date" value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                </div>
            </div>
            
            <div class="reminder-section">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="reminder" name="reminder" <?php echo isset($_POST['reminder']) ? 'checked' : ''; ?>>
                        <i class="fas fa-bell"></i> إضافة تذكير
                    </label>
                </div>
                
                <div id="reminder_fields" style="<?php echo isset($_POST['reminder']) ? '' : 'display: none;'; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reminder_time"><i class="fas fa-clock"></i> وقت التذكير</label>
                            <input type="datetime-local" id="reminder_time" name="reminder_time" value="<?php echo isset($_POST['reminder_time']) ? htmlspecialchars($_POST['reminder_time']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-paper-plane"></i> طريقة التذكير</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="reminder_method" value="notification" <?php echo (!isset($_POST['reminder_method']) || $_POST['reminder_method'] === 'notification') ? 'checked' : ''; ?>>
                                    <i class="fas fa-bell"></i> إشعار
                                </label>
                                <label>
                                    <input type="radio" name="reminder_method" value="email" <?php echo (isset($_POST['reminder_method']) && $_POST['reminder_method'] === 'email') ? 'checked' : ''; ?>>
                                    <i class="fas fa-envelope"></i> بريد إلكتروني
                                </label>
                                <label>
                                    <input type="radio" name="reminder_method" value="both" <?php echo (isset($_POST['reminder_method']) && $_POST['reminder_method'] === 'both') ? 'checked' : ''; ?>>
                                    <i class="fas fa-bell"></i> <i class="fas fa-envelope"></i> كليهما
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> حفظ المهمة
            </button>
        </form>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // إظهار/إخفاء حقول التذكير
        document.getElementById('reminder').addEventListener('change', function() {
            document.getElementById('reminder_fields').style.display = this.checked ? 'block' : 'none';
        });
        
        // تعيين وقت التذكير افتراضياً قبل تاريخ الاستحقاق بساعة
        document.getElementById('due_date').addEventListener('change', function() {
            if (this.value && document.getElementById('reminder').checked && !document.getElementById('reminder_time').value) {
                const dueDate = new Date(this.value);
                dueDate.setHours(dueDate.getHours() - 1);
                document.getElementById('reminder_time').value = dueDate.toISOString().slice(0, 16);
            }
        });
    </script>
</body>
</html>