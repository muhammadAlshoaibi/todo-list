<?php
require_once 'config.php';
require_once 'db_connect.php';

//$pageTitle = getPageTitle('settings');
$pageSubtitle = 'إعدادات التطبيق والتخصيص';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'export_data':
            exportData();
            break;
        case 'import_data':
            $result = importData();
            $message = $result['message'];
            $messageType = $result['type'];
            break;
        case 'clear_completed':
            $result = clearCompletedTasks();
            $message = $result['message'];
            $messageType = $result['type'];
            break;
        case 'reset_all':
            $result = resetAllData();
            $message = $result['message'];
            $messageType = $result['type'];
            break;
    }
}

function exportData() {
    global $mysqli;
    
    $query = "SELECT * FROM tasks ORDER BY created_at DESC";
    $result = $mysqli->query($query);
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    
    $filename = 'todo_backup_' . date('Y-m-d_H-i-s') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo json_encode([
        'export_date' => date('Y-m-d H:i:s'),
        'total_tasks' => count($tasks),
        'tasks' => $tasks
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

function importData() {
    global $mysqli;
    
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        return ['message' => 'يرجى اختيار ملف صحيح', 'type' => 'error'];
    }
    
    $fileContent = file_get_contents($_FILES['import_file']['tmp_name']);
    $data = json_decode($fileContent, true);
    
    if (!$data || !isset($data['tasks'])) {
        return ['message' => 'ملف غير صحيح أو تالف', 'type' => 'error'];
    }
    
    $mysqli->autocommit(FALSE);
    $imported = 0;
    
    try {
        $query = "INSERT INTO tasks (task_name, status, created_at) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        
        foreach ($data['tasks'] as $task) {
            if (isset($task['task_name']) && !empty($task['task_name'])) {
                $stmt->bind_param("sss", 
                    $task['task_name'],
                    $task['status'] ?? 'pending',
                    $task['created_at'] ?? date('Y-m-d H:i:s')
                );
                $stmt->execute();
                $imported++;
            }
        }
        
        $mysqli->commit();
        return ['message' => "تم استيراد {$imported} مهمة بنجاح", 'type' => 'success'];
        
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['message' => 'حدث خطأ أثناء استيراد البيانات', 'type' => 'error'];
    }
}

function clearCompletedTasks() {
    global $mysqli;
    
    $query = "DELETE FROM tasks WHERE status = 'completed'";
    $result = $mysqli->query($query);
    $deleted = $mysqli->affected_rows;
    
    return ['message' => "تم حذف {$deleted} مهمة مكتملة", 'type' => 'success'];
}

function resetAllData() {
    global $mysqli;
    
    $query = "DELETE FROM tasks";
    $result = $mysqli->query($query);
    $deleted = $mysqli->affected_rows;
    
    return ['message' => "تم حذف جميع المهام ({$deleted} مهمة)", 'type' => 'success'];
}

include 'includes/header.php';
?>

<main class="main-content settings">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Application Settings -->
    <section class="settings-section">
        <h2><i class="fas fa-cog"></i> إعدادات التطبيق</h2>
        <div class="settings-grid">
            <div class="setting-card">
                <div class="setting-header">
                    <h3><i class="fas fa-palette"></i> المظهر والألوان</h3>
                    <p>تخصيص مظهر التطبيق</p>
                </div>
                <div class="setting-content">
                    <form method="POST" action="save_settings.php">
                        <div class="setting-item">
                            <label>نمط الألوان:</label>
                            <select name="color_theme" class="setting-select">
                                <option value="default" selected>الافتراضي (بنفسجي-أزرق)</option>
                                <option value="green">أخضر طبيعي</option>
                                <option value="blue">أزرق احترافي</option>
                                <option value="orange">برتقالي دافئ</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <label>حجم الخط:</label>
                            <select name="font_size" class="setting-select">
                                <option value="small">صغير</option>
                                <option value="medium" selected>متوسط</option>
                                <option value="large">كبير</option>
                            </select>
                        </div>
                        <button type="submit" class="btn primary">حفظ الإعدادات</button>
                    </form>
                </div>
            </div>

            <div class="setting-card">
                <div class="setting-header">
                    <h3><i class="fas fa-bell"></i> الإشعارات</h3>
                    <p>إدارة إشعارات التطبيق</p>
                </div>
                <div class="setting-content">
                    <form method="POST" action="save_settings.php">
                        <div class="setting-item">
                            <label>تفعيل الإشعارات:</label>
                            <label class="switch">
                                <input type="checkbox" name="enable_notifications" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <label>أصوات الإشعارات:</label>
                            <label class="switch">
                                <input type="checkbox" name="sound_notifications">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <button type="submit" class="btn primary">حفظ الإعدادات</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Data Management -->
    <section class="settings-section">
        <h2><i class="fas fa-database"></i> إدارة البيانات</h2>
        <div class="settings-grid">
            <div class="setting-card">
                <div class="setting-header">
                    <h3><i class="fas fa-download"></i> تصدير البيانات</h3>
                    <p>احفظ نسخة احتياطية من مهامك</p>
                </div>
                <div class="setting-content">
                    <form method="post">
                        <input type="hidden" name="action" value="export_data">
                        <button type="submit" class="btn primary">
                            <i class="fas fa-download"></i>
                            تصدير جميع المهام
                        </button>
                    </form>
                </div>
            </div>

            <div class="setting-card">
                <div class="setting-header">
                    <h3><i class="fas fa-upload"></i> استيراد البيانات</h3>
                    <p>استعادة المهام من ملف احتياطي</p>
                </div>
                <div class="setting-content">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_data">
                        <div class="file-input-container">
                            <label class="file-input-label">
                                <i class="fas fa-file-upload"></i>
                                اختر ملف JSON
                                <input type="file" name="import_file" accept=".json" required style="display: none;">
                            </label>
                        </div>
                        <button type="submit" class="btn secondary">
                            <i class="fas fa-upload"></i>
                            استيراد المهام
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Maintenance -->
    <section class="settings-section">
        <h2><i class="fas fa-tools"></i> صيانة النظام</h2>
        <div class="settings-grid">
            <div class="setting-card">
                <div class="setting-header">
                    <h3><i class="fas fa-broom"></i> تنظيف البيانات</h3>
                    <p>إزالة المهام المكتملة</p>
                </div>
                <div class="setting-content">
                    <form method="post" onsubmit="return confirm('هل أنت متأكد من حذف جميع المهام المكتملة؟')">
                        <input type="hidden" name="action" value="clear_completed">
                        <button type="submit" class="btn warning">
                            <i class="fas fa-broom"></i>
                            حذف المهام المكتملة
                        </button>
                    </form>
                </div>
            </div>

            <div class="setting-card danger">
                <div class="setting-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> إعادة تعيين كاملة</h3>
                    <p>حذف جميع البيانات (لا يمكن التراجع)</p>
                </div>
                <div class="setting-content">
                    <form method="post" onsubmit="return confirm('تحذير: سيتم حذف جميع المهام نهائياً. هل أنت متأكد؟')">
                        <input type="hidden" name="action" value="reset_all">
                        <button type="submit" class="btn danger">
                            <i class="fas fa-trash-alt"></i>
                            حذف جميع البيانات
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Application Info -->
    <section class="settings-section">
        <h2><i class="fas fa-info-circle"></i> معلومات التطبيق</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">اسم التطبيق:</span>
                <span class="info-value"><?php echo APP_NAME; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">الإصدار:</span>
                <span class="info-value"><?php echo APP_VERSION; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">تاريخ آخر تحديث:</span>
                <span class="info-value"><?php echo date('Y-m-d'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">التقنيات المستخدمة:</span>
                <span class="info-value">PHP, MySQL, HTML5, CSS3</span>
            </div>
        </div>
    </section>
</main>

<script>
// Settings page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
    setupSettingsHandlers();
});

function loadSettings() {
    // Load saved settings from localStorage
    const colorTheme = localStorage.getItem('colorTheme') || 'default';
    const fontSize = localStorage.getItem('fontSize') || 'medium';
    const enableNotifications = localStorage.getItem('enableNotifications') !== 'false';
    const soundNotifications = localStorage.getItem('soundNotifications') === 'true';
    
    document.getElementById('colorTheme').value = colorTheme;
    document.getElementById('fontSize').value = fontSize;
    document.getElementById('enableNotifications').checked = enableNotifications;
    document.getElementById('soundNotifications').checked = soundNotifications;
    
    // Apply settings
    applyColorTheme(colorTheme);
    applyFontSize(fontSize);
}

function setupSettingsHandlers() {
    // Color theme change
    document.getElementById('colorTheme').addEventListener('change', function()) {
        const theme = this.value;
        localStorage.setItem('colorTheme', theme);
        applyColorTheme(theme);
        showToast('تم تغيير نمط الألوان', 'success');
}};
    
    // Font size change
    document.getElementById('fontSize').addEventListener('change', function() {
        const size = this.value;
        localStorage.setItem('fontSize', size);
        applyFontSize(size);
        showToast('تم تغيير حجم الخط', 'success');
    });
    
    // Notifications toggle
    document.getElementById('enableNotifications').addEventListener('change', function() {
        localStorage.setItem('enableNotifications', this.checked);
        showToast(this.checked ? 'تم تفعيل الإشعارات' : 'تم إلغاء الإشعارات', 'success');
    });
    
    // Sound notifications toggle
    document.getElementById('soundNotifications').addEventListener('change', function() {
        localStorage.setItem('soundNotifications', this.checked);
        showToast(this.checked ? 'تم تفعيل أصوات الإشعارات' : 'تم إلغاء أصوات الإشعارات', 'success');
    });
        function applyColorTheme(theme) {
    const root = document.documentElement;
    
    switch(theme) {
        case 'green':
            root.style.setProperty('--primary-color', '#48bb78');
            root.style.setProperty('--primary-dark', '#38a169');
            root.style.setProperty('--secondary-color', '#68d391');
            break;
        case 'blue':
            root.style.setProperty('--primary-color', '#4299e1');
            root.style.setProperty('--primary-dark', '#3182ce');
            root.style.setProperty('--secondary-color', '#63b3ed');
            break;
        case 'orange':
            root.style.setProperty('--primary-color', '#ed8936');
            root.style.setProperty('--primary-dark', '#dd6b20');
            root.style.setProperty('--secondary-color', '#f6ad55');
            break;
        default:
            root.style.setProperty('--primary-color', '#667eea');
            root.style.setProperty('--primary-dark', '#5a67d8');
            root.style.setProperty('--secondary-color', '#764ba2');
    }
}

function applyFontSize(size) {
    const root = document.documentElement;
    
    switch(size) {
        case 'small':
            root.style.setProperty('--base-font-size', '14px');
            break;
        case 'large':
            root.style.setProperty('--base-font-size', '18px');
            break;
        default:
            root.style.setProperty('--base-font-size', '16px');
    }
}




    </script>
<?php 
$mysqli->close();
include('includes/footer.php'); 
?>