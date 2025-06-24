<?php
require_once 'config.php';
require_login();

// التحقق من وجود معرف المهمة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: tasks.php');
    exit();
}

$task_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// جلب بيانات المهمة من قاعدة البيانات
$conn = db_connect();
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

// إذا لم توجد المهمة أو لا تنتمي للمستخدم
if (!$task) {
    header('Location: tasks.php');
    exit();
}

// معالجة تحديث المهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $priority = sanitize_input($_POST['priority']);
    $status = sanitize_input($_POST['status']);

    $stmt = $conn->prepare("UPDATE tasks SET
                            title = ?,
                            description = ?,
                            due_date = ?,
                            priority = ?,
                            status = ?
                            WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssssii", $title, $description, $due_date, $priority, $status, $task_id, $user_id);
   
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "تم تحديث المهمة بنجاح";
        echo $_SESSION['success_message'];
        header('Location: tasks.php');
       exit();
    } else {
        $error_message = "حدث خطأ أثناء تحديث المهمة";
    }
    $stmt->close();
}

$conn->close();

// تحضير البيانات للعرض
$priorities = [
    'low' => 'منخفض',
    'medium' => 'متوسط',
    'high' => 'عالي',
    'urgent' => 'عاجل'
];

$statuses = [
    'pending' => 'معلقة',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتملة',
    'archived' => 'مؤرشفة'
];
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المهمة - <?php echo htmlspecialchars($task['title']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* التنسيقات العامة */
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
       
        .main-content {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        }
       
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
       
        .page-header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.8em;
        }
       
        /* تنسيقات النموذج */
        .task-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
       
        .form-group {
            margin-bottom: 20px;
        }
       
        .form-group.full-width {
            grid-column: span 2;
        }
       
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a6fa5;
        }
       
        input[type="text"],
        input[type="datetime-local"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
       
        input[type="text"]:focus,
        input[type="datetime-local"]:focus,
        textarea:focus,
        select:focus {
            border-color: #4a6fa5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }
       
        textarea {
            min-height: 120px;
            resize: vertical;
        }
       
        /* تنسيقات الأزرار */
        .form-actions {
            grid-column: span 2;
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
       
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
       
        .btn-primary {
            background-color: #4a6fa5;
            color: white;
        }
       
        .btn-primary:hover {
            background-color: #3a5a80;
        }
       
        .btn-secondary {
            background-color: #e0e0e0;
            color: #333;
        }
       
        .btn-secondary:hover {
            background-color: #d0d0d0;
        }
       
        /* تنسيقات حالة الأولوية */
        .priority-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
        }
       
        .priority-low { background-color: #4caf50; }
        .priority-medium { background-color: #ffc107; }
        .priority-high { background-color: #f44336; }
        .priority-urgent { background-color: #9c27b0; }
       
        /* رسائل التنبيه */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
       
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
       
        /* التنسيق للجوّال */
        @media (max-width: 768px) {
            .task-form {
                grid-template-columns: 1fr;
            }
           
            .form-group.full-width {
                grid-column: span 1;
            }
           
            .form-actions {
                flex-direction: column-reverse;
                gap: 10px;
            }
           
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
   
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> تعديل المهمة</h1>
            <a href="tasks.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
        </div>
       
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
       
        <form method="POST" class="task-form">
            <div class="form-group full-width">
                <label for="title">عنوان المهمة</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
            </div>
           
            <div class="form-group full-width">
                <label for="description">وصف المهمة</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($task['description']); ?></textarea>
            </div>
           
            <div class="form-group">
                <label for="due_date">تاريخ الاستحقاق</label>
                <input type="datetime-local" id="due_date" name="due_date"
                       value="<?php echo $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : ''; ?>">
            </div>
           
            <div class="form-group">
                <label for="priority">الأولوية</label>
                <select id="priority" name="priority" required>
                    <?php foreach ($priorities as $value => $name): ?>
                        <option value="<?php echo $value; ?>"
                            <?php echo $task['priority'] === $value ? 'selected' : ''; ?>
                            data-priority="<?php echo $value; ?>">
                            <?php echo $name; ?>
                            <span class="priority-indicator priority-<?php echo $value; ?>"></span>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
           
            <div class="form-group">
                <label for="status">حالة المهمة</label>
                <select id="status" name="status" required>
                    <?php foreach ($statuses as $value => $name): ?>
                        <option value="<?php echo $value; ?>" <?php echo $task['status'] === $value ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
           
            <div class="form-actions">
                <a href="tasks.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ التغييرات
                </button>
            </div>
        </form>
    </main>
   
    <?php include 'includes/footer.php'; ?>
   
    <script>
        // تغيير لون حدود حقل الأولوية حسب الاختيار
        document.getElementById('priority').addEventListener('change', function() {
            const priority = this.value;
            this.style.borderLeft = `4px solid ${
                priority === 'low' ? '#4caf50' :
                priority === 'medium' ? '#ffc107' :
                priority === 'high' ? '#f44336' : '#9c27b0'
            }`;
        });
       
        // تطبيق اللون عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            const prioritySelect = document.getElementById('priority');
            prioritySelect.dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html>