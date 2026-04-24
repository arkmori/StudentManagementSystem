<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastName = $_POST['lastName'] ?? '';
    $firstName = $_POST['firstName'] ?? '';
    $middleName = $_POST['middleName'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $sex = strtolower($_POST['sex'] ?? '');
    $studentId = $_POST['studentId'] ?? '';
    $collegeName = $_POST['college'] ?? '';
    $yearLevel = $_POST['yearLevel'] ?? 1;
    $sectionName = $_POST['section'] ?? '';

    try {
        $pdo->beginTransaction();

        $stmtRole = $pdo->prepare("SELECT role_id FROM `Role` WHERE role_name = 'student'");
        $stmtRole->execute();
        $roleRow = $stmtRole->fetch();
        if (!$roleRow) {
            $pdo->exec("INSERT INTO `Role` (role_name) VALUES ('student')");
            $role_id = $pdo->lastInsertId();
        } else {
            $role_id = $roleRow['role_id'];
        }

        $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
        $username = !empty($studentId) ? $studentId : strtolower($firstName . $lastName);

        $stmtUser = $pdo->prepare("
            INSERT INTO `Login` (user_name, password, first_name, middle_name, last_name, gender, date_of_birth, role_id) 
            VALUES (:uname, :pass, :fname, :mname, :lname, :gender, :dob, :rid)
        ");
        $stmtUser->execute([
            ':uname' => $username,
            ':pass' => $defaultPassword,
            ':fname' => $firstName,
            ':mname' => $middleName,
            ':lname' => $lastName,
            ':gender' => $sex,
            ':dob' => $birthdate,
            ':rid' => $role_id
        ]);
        $new_user_id = $pdo->lastInsertId();

        $stmtCol = $pdo->prepare("SELECT college_id FROM `College` WHERE college_name = :cname");
        $stmtCol->execute([':cname' => $collegeName]);
        $colRow = $stmtCol->fetch();
        if (!$colRow && !empty($collegeName)) {
            $stmtInsertCol = $pdo->prepare("INSERT INTO `College` (college_name) VALUES (:cname)");
            $stmtInsertCol->execute([':cname' => $collegeName]);
            $college_id = $pdo->lastInsertId();
        } else {
            $college_id = $colRow ? $colRow['college_id'] : NULL;
        }

        $stmtStudent = $pdo->prepare("INSERT INTO `Student` (user_id, year_level, college_id) VALUES (:uid, :ylvl, :cid)");
        $stmtStudent->execute([
            ':uid' => $new_user_id,
            ':ylvl' => $yearLevel,
            ':cid' => $college_id
        ]);
        $new_student_id = $pdo->lastInsertId();

        if (!empty($sectionName)) {
            $stmtSec = $pdo->prepare("SELECT section_id FROM `Section` WHERE section_name = :sname");
            $stmtSec->execute([':sname' => $sectionName]);
            $secRow = $stmtSec->fetch();
            if (!$secRow) {
                $stmtInsertSec = $pdo->prepare("INSERT INTO `Section` (section_name) VALUES (:sname)");
                $stmtInsertSec->execute([':sname' => $sectionName]);
                $section_id = $pdo->lastInsertId();
            } else {
                $section_id = $secRow['section_id'];
            }

            $stmtEnroll = $pdo->prepare("INSERT INTO `Enrollment` (student_id, section_id, enrollment_date, status) VALUES (:sid, :secid, CURDATE(), 'Active')");
            $stmtEnroll->execute([
                ':sid' => $new_student_id,
                ':secid' => $section_id
            ]);
        }

        $pdo->commit();
        $message = "Student successfully enrolled!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Enrollment failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Enroll Student</title>
    <link rel="stylesheet" href="style/styles.css">
</head>
<body class="layout-dashboard">

    <input type="checkbox" id="menu-toggle">

    <header>
        <label for="menu-toggle" class="hamburger-icon">
            <span></span>
            <span></span>
            <span></span>
        </label>
    </header>

    <nav class="dropdown-menu">
        <ul class="menu-list">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>

    <main>
        <div class="enroll-layout-wrapper">
            
            <div class="top-actions card" style="justify-content: flex-start;">
                <div class="title-group">
                    <a href="dashboard.php" class="home-icon-link">
                        <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </a>
                    <h1 class="page-title">Enroll Student</h1>
                </div>
            </div>

            <form action="" method="post" class="card form-card">
                
                <?php if ($message): ?>
                    <p style="color: <?php echo strpos($message, 'failed') !== false ? 'red' : 'green'; ?>; font-weight: bold; text-align: center; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <h2 class="section-title">Personal Information</h2>
                <div class="personal-info-grid">
                    
                    <div class="form-column">
                        <div class="inline-form-group">
                            <label for="lastName">Last Name:</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                        <div class="inline-form-group">
                            <label for="firstName">First Name:</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                        <div class="inline-form-group">
                            <label for="middleName">Middle Name:</label>
                            <input type="text" id="middleName" name="middleName">
                        </div>
                    </div>

                    <div class="form-column">
                        <div class="inline-form-group">
                            <label for="birthdate">Birthdate:</label>
                            <input type="date" id="birthdate" name="birthdate">
                        </div>
                        <div class="inline-form-group">
                            <label for="sex">Sex:</label>
                            <input type="text" id="sex" name="sex" placeholder="Male / Female">
                        </div>
                    </div>
                </div>

                <h2 class="section-title" style="margin-top: 45px;">Admissions</h2>
                <div class="admissions-grid">
                    <div class="inline-form-group">
                        <label for="studentId">Student Username:</label>
                        <input type="text" id="studentId" name="studentId" placeholder="Used for Login">
                    </div>
                    <div class="inline-form-group">
                        <label for="college">College:</label>
                        <input type="text" id="college" name="college" placeholder="e.g. College of Engineering">
                    </div>
                    <div class="inline-form-group">
                        <label for="yearLevel">Year Level:</label>
                        <input type="number" id="yearLevel" name="yearLevel" value="1">
                    </div>
                    <div class="inline-form-group">
                        <label for="courses">Enroll in Course/s:</label>
                        <input type="text" id="courses" name="courses">
                    </div>
                    <div class="inline-form-group">
                        <label for="section">Section:</label>
                        <input type="text" id="section" name="section">
                    </div>
                </div>

                <div class="form-submit-container">
                    <button type="submit" class="btn-solid btn-large">Enroll Student</button>
                </div>

            </form>
        </div>
    </main>

    <footer></footer>

</body>
</html>