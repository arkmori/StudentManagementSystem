<?php
$host = 'localhost';
$dbname = 'StudentManagementSystem';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check for "Remember Me" cookies and auto-login
if (isset($_COOKIE['remember_user_id']) && isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    $stored_user_id = $_COOKIE['remember_user_id'];
    $stored_token = $_COOKIE['remember_token'];
    
    // Validate token from cookie
    $stmt = $pdo->prepare("
        SELECT l.user_id, l.user_name, r.role_name 
        FROM `Login` l 
        LEFT JOIN `Role` r ON l.role_id = r.role_id 
        WHERE l.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $stored_user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user && hash_equals($stored_token, hash('sha256', $user['user_name'] . $user['user_id']))) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = strtolower($user['role_name']);
    }
}
?>