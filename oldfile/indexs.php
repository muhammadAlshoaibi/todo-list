<?php
require_once 'config.php';

// إذا كان المستخدم مسجل الدخول، توجيهه للوحة التحكم
// if (is_logged_in()) {
//     header("Location: dashboard.php");
//     exit();
// }

// إذا لم يكن مسجل الدخول، عرض الصفحة الرئيسية
$conn = db_connect();

// الحصول على إحصائيات عامة (للعرض للزوار)
$stats_query = "SELECT 
    COUNT(*) as total_users,
    (SELECT COUNT(*) FROM tasks) as total_tasks,
    (SELECT COUNT(*) FROM tasks WHERE status = 'completed') as completed_tasks,
    (SELECT COUNT(*) FROM tasks WHERE status = 'in_progress') as in_progress_tasks,
    (SELECT COUNT(DISTINCT user_id) FROM tasks ) as total_tasks
    FROM users";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// الحصول على آخر المهام المكتملة (للعرض للزوار)
$recent_tasks_query = "SELECT t.title, t.created_at, u.username, u.avatar
                      FROM tasks t 
                      JOIN users u ON t.user_id = u.id 
                      
                      WHERE t.status = 'completed'
                      ORDER BY t.created_at DESC 
                      LIMIT 5";
$recent_tasks_result = $conn->query($recent_tasks_query);
$recent_tasks = $recent_tasks_result->fetch_all(MYSQLI_ASSOC);

// الحصول على أحدث المستخدمين المسجلين
$new_users_query = "SELECT username, avatar, created_at FROM users ORDER BY created_at DESC LIMIT 4";
$new_users_result = $conn->query($new_users_query);
$new_users = $new_users_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - نظام إدارة المهام المتكامل</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #ef233c;
            --border-radius: 12px;
            --box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 6rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('images/pattern.png') repeat;
            opacity: 0.1;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-section h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-section .lead {
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--accent-color);
            border: 2px solid var(--accent-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: transparent;
            color: var(--accent-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(247, 37, 133, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn-secondary:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .btn-lg {
            padding: 1rem 2.2rem;
            font-size: 1.1rem;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 3rem 0;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        
        .feature-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
            border-bottom: 4px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            border-bottom-color: var(--accent-color);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .feature-card h3 {
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .section-title h2 {
            display: inline-block;
            font-size: 2rem;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }
        
        .section-title h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            margin: 1rem auto 0;
            border-radius: 2px;
        }
        
        .tasks-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .task-item {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .task-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .task-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 1rem;
            border: 3px solid var(--light-color);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .task-info {
            flex: 1;
        }
        
        .task-info h4 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .task-meta {
            display: flex;
            gap: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .task-meta i {
            margin-left: 0.3rem;
            color: var(--primary-color);
        }
        
        .task-project {
            background: var(--light-color);
            color: var(--primary-color);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1.5rem;
        }
        
        .empty-state p {
            color: #888;
            font-size: 1.1rem;
        }
        
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .user-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 3px solid var(--light-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--dark-color);
        }
        
        .user-join-date {
            color: #888;
            font-size: 0.8rem;
        }
        
        .testimonials {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 4rem 2rem;
            border-radius: var(--border-radius);
            margin: 4rem 0;
        }
        
        .testimonial-slider {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .testimonial-item {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin: 0 1rem;
            text-align: center;
        }
        
        .testimonial-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 3px solid var(--light-color);
        }
        
        .testimonial-text {
            font-style: italic;
            color: #555;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .testimonial-role {
            color: #888;
            font-size: 0.9rem;
        }
        
        .pricing-section {
            margin: 4rem 0;
        }
        
        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .pricing-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .pricing-card.popular {
            border: 2px solid var(--accent-color);
        }
        
        .popular-badge {
            position: absolute;
            top: 0;
            left: 0;
            background: var(--accent-color);
            color: white;
            padding: 0.3rem 1.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            transform: translateY(-50%) rotate(-45deg);
            transform-origin: left top;
        }
        
        .pricing-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .pricing-period {
            font-size: 1rem;
            color: #888;
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem;
        }
        
        .pricing-features li {
            padding: 0.7rem 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        
        .pricing-features li:last-child {
            border-bottom: none;
        }
        
        .pricing-features i {
            margin-left: 0.5rem;
            color: var(--success-color);
        }
        
        .faq-section {
            margin: 4rem 0;
        }
        
        .faq-item {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .faq-question {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 600;
            color: var(--dark-color);
            background: var(--light-color);
            transition: var(--transition);
        }
        
        .faq-question:hover {
            background: #e9ecef;
        }
        
        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .faq-item.active .faq-answer {
            padding: 1.5rem;
            max-height: 500px;
        }
        
        .faq-toggle {
            transition: transform 0.3s ease;
        }
        
        .faq-item.active .faq-toggle {
            transform: rotate(180deg);
        }
        
        .newsletter-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            margin: 4rem 0;
        }
        
        .newsletter-form {
            max-width: 500px;
            margin: 2rem auto 0;
            display: flex;
            gap: 1rem;
        }
        
        .newsletter-input {
            flex: 1;
            padding: 0.8rem 1.2rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
        }
        
        .newsletter-submit {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .newsletter-submit:hover {
            background: white;
            color: var(--accent-color);
        }
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-section .lead {
                font-size: 1.2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-submit {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <section class="hero-section animate__animated animate__fadeIn">
        <div class="hero-content">
            <h1><i class="fas fa-tasks"></i> نظام إدارة المهام المتكامل</h1>
            <p class="lead">أداة قوية لتنظيم عملك وزيادة إنتاجيتك وإنجاز المهام في الوقت المحدد</p>
            
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i> إنشاء حساب جديد
                </a>
                <a href="login.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </a>
            </div>
        </div>
    </section>
    
    <main class="main-content">
        <div class="container">
            <!-- الإحصائيات السريعة -->
            <div class="quick-stats animate__animated animate__fadeInUp">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>مستخدم مسجل</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_tasks']); ?></h3>
                        <p>مهمة تم إنشاؤها</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['completed_tasks']); ?></h3>
                        <p>مهمة مكتملة</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php //echo number_format($stats['total_projects']); ?></h3>
                        <p>مشروع نشط</p>
                    </div>
                </div>
            </div>
            
            <!-- المميزات الرئيسية -->
            <section class="features-section">
                <div class="section-title">
                    <h2><i class="fas fa-star"></i> مميزات النظام</h2>
                    <p>اكتشف كيف يمكن لنظامنا مساعدتك في إدارة مهامك بكفاءة</p>
                </div>
                
                <div class="features">
                    <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                        <div class="feature-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <h3>نظام متعدد المستخدمين</h3>
                        <p>إدارة حسابات متعددة مع صلاحيات مختلفة لكل مستخدم، وتخصيص واجهة كل مستخدم حسب احتياجاته</p>
                    </div>
                    
                    <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>تواريخ الاستحقاق</h3>
                        <p>تحديد تواريخ نهائية للمهام وتنبيهات عند الاقتراب منها مع إمكانية تتبع التقدم الزمني</p>
                    </div>
                    
                    <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>التذكيرات والإشعارات</h3>
                        <p>تذكيرات عبر الإشعارات والبريد الإلكتروني لضمان عدم تفويت أي مهمة مع إمكانية التخصيص</p>
                    </div>
                    
                    <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>تقارير وإحصائيات</h3>
                        <p>تقارير مفصلة عن أداء الفريق وإحصائيات عن إنجاز المهام مع رسوم بيانية توضيحية</p>
                    </div>
                    
                    <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>واجهة متجاوبة</h3>
                        <p>تصميم متكامل يعمل على جميع الأجهزة من حواسيب وأجهزة لوحية وهواتف ذكية</p>
                    </div>
                    
                    <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3>أمان وحماية</h3>
                        <p>نظام حماية متكامل مع تشفير البيانات ونسخ احتياطي تلقائي لحماية معلوماتك</p>
                    </div>
                </div>
            </section>
            
            <!-- آخر المهام المكتملة -->
            <section class="recent-tasks animate__animated animate__fadeIn">
                <div class="section-title">
                    <h2><i class="fas fa-history"></i> آخر المهام المكتملة</h2>
                    <p>أحدث المهام التي تم إنجازها من قبل مستخدمينا</p>
                </div>
                
                <?php if (count($recent_tasks) > 0): ?>
                    <div class="tasks-list">
                        <?php foreach ($recent_tasks as $task): ?>
                            <div class="task-item">
                                <img src="<?php echo $task['avatar'] ? 'uploads/avatars/'.$task['avatar'] : 'images/default-avatar.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($task['username']); ?>" 
                                     class="task-avatar">
                                <div class="task-info">
                                    <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                                    <div class="task-meta">
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($task['username']); ?></span>
                                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('Y/m/d', strtotime($task['created_at'])); ?></span>
                                        <?php if ($task['title']): ?>
                                            <span class="task-project"><i class="fas fa-project-diagram"></i> <?php echo htmlspecialchars($task['title']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>لا توجد مهام مكتملة لعرضها</p>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- أحدث المستخدمين -->
            <section class="new-users animate__animated animate__fadeIn">
                <div class="section-title">
                    <h2><i class="fas fa-user-plus"></i> أحدث المستخدمين</h2>
                    <p>أحدث الأعضاء الذين انضموا إلى منصتنا</p>
                </div>
                
                <div class="users-grid">
                    <?php foreach ($new_users as $user): ?>
                        <div class="user-card">
                            <img src="<?php echo $user['avatar'] ? 'uploads/avatars/'.$user['avatar'] : 'images/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                 class="user-avatar">
                            <h4 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h4>
                            <p class="user-join-date">منذ <?php echo $user['created_at']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- آراء العملاء -->
            <!-- <section class="testimonials animate__animated animate__fadeIn">
                <div class="section-title">
                    <h2><i class="fas fa-quote-left"></i> آراء عملائنا</h2>
                    <p>ما يقوله مستخدمونا عن تجربتهم مع النظام</p>
                </div>
                
                <div class="testimonial-slider">
                    <div class="testimonial-item">
                        <img src="images/testimonial1.jpg" alt="محمد أحمد" class="testimonial-avatar">
                        <p class="testimonial-text">
                            "هذا النظام غير طريقة إدارتي للمهام تماماً، أصبحت أكثر تنظيماً وإنتاجية. التكامل مع التقويم والإشعارات ممتاز!"
                        </p>
                        <h4 class="testimonial-author">محمد أحمد</h4>
                        <p class="testimonial-role">مدير مشاريع في شركة التقنية</p>
                    </div>
                </div>
            </section> -->
            
            <!-- خطط الأسعار -->
            <!-- <section class="pricing-section animate__animated animate__fadeIn">
                <div class="section-title">
                    <h2><i class="fas fa-tags"></i> خطط الأسعار</h2>
                    <p>اختر الخطة التي تناسب احتياجاتك</p>
                </div>
                
                <div class="pricing-cards">
                    <div class="pricing-card">
                        <h3 class="pricing-title">الاساسية</h3>
                        <div class="pricing-price">$9 <span class="pricing-period">/شهر</span></div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> حتى 5 مستخدمين</li>
                            <li><i class="fas fa-check"></i> 100 مهمة شهرياً</li>
                            <li><i class="fas fa-check"></i> 3 مشاريع نشطة</li>
                            <li><i class="fas fa-check"></i> دعم عبر البريد الإلكتروني</li>
                        </ul>
                        <a href="register.php" class="btn btn-secondary">اختر هذه الخطة</a>
                    </div>
                    
                    <div class="pricing-card popular">
                        <div class="popular-badge">الأكثر شعبية</div>
                        <h3 class="pricing-title">المتقدمة</h3>
                        <div class="pricing-price">$19 <span class="pricing-period">/شهر</span></div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> حتى 15 مستخدم</li>
                            <li><i class="fas fa-check"></i> مهمات غير محدودة</li>
                            <li><i class="fas fa-check"></i> 10 مشاريع نشطة</li>
                            <li><i class="fas fa-check"></i> دعم فني سريع</li>
                            <li><i class="fas fa-check"></i> تقارير متقدمة</li>
                        </ul>
                        <a href="register.php" class="btn btn-primary">اختر هذه الخطة</a>
                    </div>
                    
                    <div class="pricing-card">
                        <h3 class="pricing-title">الشركات</h3>
                        <div class="pricing-price">$49 <span class="pricing-period">/شهر</span></div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> مستخدمون غير محدودين</li>
                            <li><i class="fas fa-check"></i> مشاريع غير محدودة</li>
                            <li><i class="fas fa-check"></i> دعم فني على مدار الساعة</li>
                            <li><i class="fas fa-check"></i> تكامل مع أنظمة أخرى</li>
                            <li><i class="fas fa-check"></i> تدريب فريق مخصص</li>
                        </ul>
                        <a href="register.php" class="btn btn-secondary">اختر هذه الخطة</a>
                    </div>
                </div>
            </section> -->
            
            <!-- الأسئلة الشائعة -->
            <section class="faq-section animate__animated animate__fadeIn">
                <div class="section-title">
                    <h2><i class="fas fa-question-circle"></i> الأسئلة الشائعة</h2>
                    <p>إجابات على أكثر الأسئلة شيوعاً</p>
                </div>
                
                <div class="faq-list">
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>كيف يمكنني تغيير كلمة المرور الخاصة بي؟</span>
                            <i class="fas fa-chevron-down faq-toggle"></i>
                        </div>
                        <div class="faq-answer">
                            <p>يمكنك تغيير كلمة المرور من خلال الذهاب إلى صفحة الملف الشخصي والنقر على "تغيير كلمة المرور". ستحتاج إلى إدخال كلمة المرور الحالية ثم كلمة المرور الجديدة مرتين لتأكيدها.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>هل يمكنني مشاركة المهام مع مستخدمين آخرين؟</span>
                            <i class="fas fa-chevron-down faq-toggle"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نعم، يمكنك مشاركة المهام مع أي مستخدم في فريقك. عند إنشاء أو تعديل مهمة، ستجد خيار "المشاركة" حيث يمكنك اختيار المستخدمين الذين تريد مشاركة المهمة معهم.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>ما هي طرق الدفع المتاحة؟</span>
                            <i class="fas fa-chevron-down faq-toggle"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نحن نقبل جميع بطاقات الائتمان الرئيسية (Visa, MasterCard, American Express) بالإضافة إلى PayPal. يمكنك أيضًا الدفع عن طريق التحويل البنكي للخطط السنوية.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>هل هناك نسخة تجريبية مجانية؟</span>
                            <i class="fas fa-chevron-down faq-toggle"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نعم، نقدم نسخة تجريبية مجانية لمدة 14 يومًا لجميع الخطط. يمكنك تجربة جميع الميزات دون أي التزام. بعد انتهاء الفترة التجريبية، يمكنك اختيار الخطة التي تناسبك.</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- النشرة البريدية -->
            <section class="newsletter-section animate__animated animate__fadeIn">
                <h2><i class="fas fa-envelope"></i> اشترك في نشرتنا البريدية</h2>
                <p>احصل على آخر التحديثات والعروض الخاصة مباشرة إلى بريدك الإلكتروني</p>
                
                <form class="newsletter-form" action="subscribe.php" method="POST">
                    <input type="email" name="email" placeholder="بريدك الإلكتروني" class="newsletter-input" required>
                    <button type="submit" class="newsletter-submit">اشترك الآن</button>
                </form>
            </section>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // تفعيل الأسئلة الشائعة
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
            });
        });
        
        // تأثير التمرير السلس للروابط
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // إضافة تأثيرات الحركة عند التمرير
        window.addEventListener('scroll', () => {
            const scrollPosition = window.scrollY;
            const windowHeight = window.innerHeight;
            
            document.querySelectorAll('.animate__animated').forEach(element => {
                const elementPosition = element.getBoundingClientRect().top + scrollPosition;
                
                if (scrollPosition > elementPosition - windowHeight + 100) {
                    element.style.opacity = '1';
                }
            });
        });
    </script>
</body>
</html>