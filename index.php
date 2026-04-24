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

    // 1. Independent Tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Role` (
        `role_id` INT AUTO_INCREMENT PRIMARY KEY,
        `role_name` VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Address` (
        `address_id` INT AUTO_INCREMENT PRIMARY KEY,
        `barangay` VARCHAR(100),
        `street` VARCHAR(100),
        `city` VARCHAR(100)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Contact` (
        `contact_id` INT AUTO_INCREMENT PRIMARY KEY,
        `contact_number` VARCHAR(20),
        `email` VARCHAR(100)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Course` (
        `course_id` INT AUTO_INCREMENT PRIMARY KEY,
        `course_name` VARCHAR(100),
        `description` TEXT
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `College` (
        `college_id` INT AUTO_INCREMENT PRIMARY KEY,
        `college_name` VARCHAR(100),
        `department` VARCHAR(100)
    ) ENGINE=InnoDB");

    // 2. Base User Table
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
        `address_id` INT,
        `contact_id` INT,
        FOREIGN KEY (`role_id`) REFERENCES `Role`(`role_id`),
        FOREIGN KEY (`address_id`) REFERENCES `Address`(`address_id`),
        FOREIGN KEY (`contact_id`) REFERENCES `Contact`(`contact_id`)
    ) ENGINE=InnoDB");

    // 3. User Extensions
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Student` (
        `student_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `year_level` INT,
        `college_id` INT,
        FOREIGN KEY (`user_id`) REFERENCES `Login`(`user_id`),
        FOREIGN KEY (`college_id`) REFERENCES `College`(`college_id`)
    ) ENGINE=InnoDB");

    // 4. Academic Tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Section` (
        `section_id` INT AUTO_INCREMENT PRIMARY KEY,
        `section_name` VARCHAR(50),
        `semester` VARCHAR(50),
        `course_name` VARCHAR(100),
        `faculty_id` INT,
        FOREIGN KEY (`faculty_id`) REFERENCES `Faculty`(`faculty_id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Enrollment` (
        `enrollment_id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT,
        `section_id` INT,
        `enrollment_date` DATE,
        `status` VARCHAR(50),
        FOREIGN KEY (`student_id`) REFERENCES `Student`(`student_id`),
        FOREIGN KEY (`section_id`) REFERENCES `Section`(`section_id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Grades` (
        `grades_id` INT AUTO_INCREMENT PRIMARY KEY,
        `enrollment_id` INT,
        `midterm_grade` DECIMAL(5,2),
        `final_grade` DECIMAL(5,2),
        `status` VARCHAR(50),
        FOREIGN KEY (`enrollment_id`) REFERENCES `Enrollment`(`enrollment_id`)
    ) ENGINE=InnoDB");

    // 5. Operational Tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Feedback` (
        `feedback_id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT,
        `faculty_id` INT,
        `subject_of_feedback` VARCHAR(255),
        `description` TEXT,
        `submission_date` DATE,
        `resolution_status` VARCHAR(50),
        FOREIGN KEY (`student_id`) REFERENCES `Student`(`student_id`),
        FOREIGN KEY (`faculty_id`) REFERENCES `Faculty`(`faculty_id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Clearance` (
        `clearance_id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT,
        `faculty_id` INT,
        `requirements` TEXT,
        `status` VARCHAR(50),
        `date_process` DATE,
        FOREIGN KEY (`student_id`) REFERENCES `Student`(`student_id`),
        FOREIGN KEY (`faculty_id`) REFERENCES `Faculty`(`faculty_id`)
    ) ENGINE=InnoDB");

    // Pre-populating Colleges for testing
    $check = $pdo->query("SELECT COUNT(*) FROM `College`")->fetchColumn();
    if ($check == 0) {
        $pdo->exec("INSERT INTO `College` (college_name, department) VALUES 
            ('College of Information Sciences', 'Information Management'),
            ('College of Engineering', 'Computer Engineering')");
    }

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header("Location: dashboard.php");
    exit();
}

header("Location: login.php");
exit();
?>