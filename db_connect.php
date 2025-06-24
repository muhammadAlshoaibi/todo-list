<?php
// Database configuration
$host = 'localhost';
$dbname = 'todo_system';
$username = 'root';
$password = '';

// Create connection
$mysqli = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$mysqli) {
    die("Connection failed: " );
}

// Set charset to utf8
$mysqli->set_charset("utf8");
?>
<?php
//require_once 'config.php';

$conn = $mysqli;

// إضافة مستخدمين عشوائيين
// $users = [
//     ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin@example.com', 'مدير النظام', 'admin'],
//     ['user1', password_hash('user1123', PASSWORD_DEFAULT), 'user1@example.com', 'محمد أحمد', 'user'],
//     ['user2', password_hash('user2123', PASSWORD_DEFAULT), 'user2@example.com', 'أحمد علي', 'user'],
//     ['user3', password_hash('user3123', PASSWORD_DEFAULT), 'user3@example.com', 'فاطمة محمد', 'user']
// ];

// foreach ($users as $user) {
//     $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
//     $stmt->bind_param("sssss", $user[0], $user[1], $user[2], $user[3], $user[4]);
//     $stmt->execute();
//     $user_ids[] = $conn->insert_id;
//     $stmt->close();
// }

?>
