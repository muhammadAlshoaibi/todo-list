<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php //echo $pageTitle . ' - ' . APP_NAME; ?></title>
    <!-- الخطوط وأيقونات Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

<style>
    .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}
</style>
    <link rel="stylesheet" href="styles.css?<?php echo time();?>">
    <!-- <link rel="stylesheet" href="css/styles.css"> -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-tasks"></i>
                <span><?php //echo APP_NAME; ?></span>
            </div>
                  <div class="cta-buttons"  >
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i> 
                </a>
                <a href="logout.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-sign-in-alt"></i>  
                </a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        الرئيسية
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboards.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        لوحة التحكم
                    </a>
                </li>
                    <li class="nav-item">
                    <a href="tasks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                         المهام
                    </a>
                </li>
                <li class="nav-item">
                    <a href="statistics.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        الإحصائيات
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        الإعدادات
                    </a>
                </li>
            </ul>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="container">
        <header class="page-header">
            <h1 class="page-title">
                <?php //echo $pageTitle; ?>
            </h1>
            <p class="page-subtitle">
                <?php echo isset($pageSubtitle) ? $pageSubtitle : 'نظم مهامك اليومية بسهولة'; ?>
            </p>
        </header>

