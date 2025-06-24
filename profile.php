
<?php
//session_start();
require_once 'config.php';
$conn=db_connect();
// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];
   
    // معالجة رفع الصورة
    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $target_dir = "uploads/";
        $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
       
        // التحقق من أن الملف صورة
        $check = getimagesize($_FILES['avatar']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                // حذف الصورة القديمة إذا كانت موجودة
                if (!empty($avatar)) {
                    unlink($target_dir . $avatar);
                }
                $avatar = $new_filename;
            }
        }
    }
   
    // تحديث البيانات في قاعدة البيانات
    $update_query = "UPDATE users SET name = ?, email = ?, bio = ?, avatar = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $name, $email, $bio, $avatar, $user_id);
   
    if ($stmt->execute()) {
        $_SESSION['message'] = "تم تحديث الملف الشخصي بنجاح";
        $_SESSION['message_type'] = "success";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['message'] = "حدث خطأ أثناء تحديث الملف الشخصي";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
   
    <div class="profile-header text-center">
        <div class="container">
            <div class="d-flex justify-content-center mb-3">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['avatar']) ?>" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-user fa-4x text-secondary"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <p class="mb-0"><?= htmlspecialchars($user['email']) ?></p>
        </div>
    </div>
   
    <div class="container">
        <div class="row">
            <!-- العمود الجانبي -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">معلومات العضو</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-alt me-2"></i>تاريخ التسجيل</span>
                                <span><?= date('Y-m-d', strtotime($user['created_at'])) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-user-clock me-2"></i>آخر نشاط</span>
                                <span><?= date('Y-m-d H:i', strtotime($user['last_login'])) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-tasks me-2"></i>المهام المكتملة</span>
                                <span class="badge bg-success rounded-pill">
                                    <?php
                                    $completed_query = "SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status='completed'";
                                    $stmt = $conn->prepare($completed_query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    echo $stmt->get_result()->fetch_row()[0];
                                    ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
               
                <!-- <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">فرقي</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $team_query = "SELECT teams.id, teams.name FROM team_members
                                      JOIN teams ON team_members.team_id = teams.id
                                      WHERE user_id = ?";
                        $stmt = $conn->prepare($team_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>
                       
                        <?php if (empty($teams)): ?>
                            <div class="alert alert-info">لا تنتمي إلى أي فريق حالياً</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($teams as $team): ?>
                                    <a href="team.php?id=<;?= $team['id'] ?>" class="list-group-item list-group-item-action">
                                        <i class="fas fa-users me-2"></i><?= htmlspecialchars($team['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div> -->
           
           
            <!-- المحتوى الرئيسي -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">تعديل الملف الشخصي</h5>
                    </div>
                    <div class="card-body">
                        <?php //if (isset($_SESSION['message'])); ?>
                            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
                                <?php //$_SESSION['message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php //unset($_SESSION['message']); ?>
                        <?php //endif; ?>
                       
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">الاسم الكامل</label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                           
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                           
                            <div class="mb-3">
                                <label for="bio" class="form-label">نبذة عني</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($user['bio']) ?></textarea>
                            </div>
                           
                            <div class="mb-3">
                                <label for="avatar" class="form-label">صورة الملف الشخصي</label>
                                <input class="form-control" type="file" id="avatar" name="avatar" accept="image/*">
                                <small class="text-muted">الصور المسموح بها: JPG, PNG, GIF (الحجم الأقصى 2MB)</small>
                            </div>
                           
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            </div>
                        </form>
                       
                        <hr class="my-4">
                       
                        <div class="d-grid gap-2">
                            <a href="change_password.php" class="btn btn-outline-secondary">تغيير كلمة المرور</a>
                        </div>
                    </div>
                </div>
               
                <!-- إحصائيات سريعة -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body text-center">
                                <h3>
                                    <?php
                                    $total_query = "SELECT COUNT(*) FROM tasks WHERE user_id = ?";
                                    $stmt = $conn->prepare($total_query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    echo $stmt->get_result()->fetch_row()[0];
                                    ?>
                                </h3>
                                <p class="mb-0">المهام الكلية</p>
                            </div>
                        </div>
                    </div>
                   
                    <div class="col-md-4">
                        <div class="card stat-card bg-warning text-dark">
                            <div class="card-body text-center">
                                <h3>
                                    <?php
                                    $pending_query = "SELECT COUNT(*) FROM tasks WHERE user_id = ? AND completed = 0";
                                    $stmt = $conn->prepare($pending_query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    echo $stmt->get_result()->fetch_row()[0];
                                    ?>
                                </h3>
                                <p class="mb-0">المهام المعلقة</p>
                            </div>
                        </div>
                    </div>
                   
                    <div class="col-md-4">
                        <div class="card stat-card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3>
                                    <?php
                                    $overdue_query = "SELECT COUNT(*) FROM tasks WHERE user_id = ? AND completed = 0 AND due_date < CURDATE()";
                                    $stmt = $conn->prepare($overdue_query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    echo $stmt->get_result()->fetch_row()[0];
                                    ?>
                                </h3>
                                <p class="mb-0">مهام متأخرة</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>