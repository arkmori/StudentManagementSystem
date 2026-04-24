<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'StudentManagementSystem';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Role` (
        `role_id` INT AUTO_INCREMENT PRIMARY KEY,
        `role_name` VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `College` (
        `college_id` INT AUTO_INCREMENT PRIMARY KEY,
        `college_name` VARCHAR(100),
        `department` VARCHAR(100)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Login` (
        `user_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_name` VARCHAR(100) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `first_name` VARCHAR(100),
        `middle_name` VARCHAR(100),
        `last_name` VARCHAR(100),
        `gender` VARCHAR(20),
        `date_of_birth` DATE,
        `role_id` INT,
        FOREIGN KEY (`role_id`) REFERENCES `Role`(`role_id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `ProfileExt` (
        `profile_ext_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `civil_status` VARCHAR(50),
        `employed_since` DATE,
        `position` VARCHAR(100),
        FOREIGN KEY (`user_id`) REFERENCES `Login`(`user_id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Faculty` (
        `faculty_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `specialization` VARCHAR(100),
        `college_id` INT,
        FOREIGN KEY (`user_id`) REFERENCES `Login`(`user_id`),
        FOREIGN KEY (`college_id`) REFERENCES `College`(`college_id`)
    ) ENGINE=InnoDB");

    // Pre-populating a College for testing if none exists
    $check = $pdo->query("SELECT COUNT(*) FROM `College`")->fetchColumn();
    if ($check == 0) {
        $pdo->exec("INSERT INTO `College` (college_name, department) VALUES 
            ('College of Information Sciences', 'Information Management'),
            ('College of Engineering', 'Computer Engineering')");
    }

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}

header("Location: login.php");
exit();
?>