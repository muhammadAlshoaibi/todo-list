<?php
function formatDate($date) {
    if (empty($date)) return 'بدون تاريخ';
    
    $timestamp = strtotime($date);
    $arabic_months = array(
        'Jan' => 'يناير',
        'Feb' => 'فبراير',
        'Mar' => 'مارس',
        'Apr' => 'أبريل',
        'May' => 'مايو',
        'Jun' => 'يونيو',
        'Jul' => 'يوليو',
        'Aug' => 'أغسطس',
        'Sep' => 'سبتمبر',
        'Oct' => 'أكتوبر',
        'Nov' => 'نوفمبر',
        'Dec' => 'ديسمبر'
    );
    
    $month_en = date('M', $timestamp);
    $month_ar = $arabic_months[$month_en];
    
    return date('d', $timestamp) . ' ' . $month_ar . ' ' . date('Y', $timestamp);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>