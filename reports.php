<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

try {
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM `Student`")->fetchColumn();
    $passedStudents = $pdo->query("SELECT COUNT(DISTINCT enrollment_id) FROM `Grades` WHERE status = 'Passed'")->fetchColumn();
    $totalSections = $pdo->query("SELECT COUNT(*) FROM `Section`")->fetchColumn();
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM `Course`")->fetchColumn();
    $studentsGraded = $pdo->query("SELECT COUNT(DISTINCT enrollment_id) FROM `Grades`")->fetchColumn();
    $studentsCleared = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM `Clearance` WHERE status = 'Cleared'")->fetchColumn();
    $studentConcerns = $pdo->query("SELECT COUNT(*) FROM `Feedback`")->fetchColumn();
} catch (PDOException $e) {
    $totalStudents = $passedStudents = $totalSections = $totalCourses = $studentsGraded = $studentsCleared = $studentConcerns = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Reports</title>
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
    <div class="report-layout-wrapper">
        
        <div class="top-actions card" style="justify-content: flex-start; margin-bottom: 5px;">
            <div class="title-group">
                <a href="dashboard.php" class="home-icon-link">
                    <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                </a>
                <h1 class="page-title">Reports</h1>
            </div>
        </div>

        <div class="reports-grid-container">
            
            <div class="reports-left-panel">
                <div class="stat-row">
                    <div class="stat-num-box"><?php echo htmlspecialchars($totalStudents); ?></div>
                    <div class="stat-text-box">Total Students</div>
                </div>
                <div class="stat-row">
                    <div class="stat-num-box"><?php echo htmlspecialchars($passedStudents); ?></div>
                    <div class="stat-text-box">Passed Students</div>
                </div>
                <div class="stat-row">
                    <div class="stat-num-box"><?php echo htmlspecialchars($totalSections); ?></div>
                    <div class="stat-text-box">Sections</div>
                </div>
                <div class="stat-row">
                    <div class="stat-num-box"><?php echo htmlspecialchars($totalCourses); ?></div>
                    <div class="stat-text-box">Courses</div>
                </div>
            </div>

            <div class="reports-right-panel">
                <div class="reports-right-section">
                    <div class="report-detail-card">
                        <div class="detail-text-group">
                            <span class="detail-num"><?php echo htmlspecialchars($studentsGraded); ?></span>
                            <span class="detail-desc">students graded</span>
                        </div>
                        <button type="button" class="btn-solid btn-report" onclick="window.location.href='studentgrades.php'">View/Update Grades</button>
                    </div>
                    <div class="report-detail-card">
                        <div class="detail-text-group">
                            <span class="detail-num"><?php echo htmlspecialchars($studentsCleared); ?></span>
                            <span class="detail-desc">students cleared</span>
                        </div>
                        <button type="button" class="btn-solid btn-report" onclick="window.location.href='clearance.php'">View Clearance</button>
                    </div>
                </div>

                <div class="reports-right-section">
                    <div class="report-detail-card">
                        <div class="detail-text-group">
                            <span class="detail-num">Student</span>
                            <span class="detail-desc">enrollment history</span>
                        </div>
                        <button type="button" class="btn-solid btn-report" onclick="window.location.href='enrollmenthistory.php'">View Student Enrollment</button>
                    </div>
                    <div class="report-detail-card">
                        <div class="detail-text-group">
                            <span class="detail-num"><?php echo htmlspecialchars($studentConcerns); ?></span>
                            <span class="detail-desc">student concerns</span>
                        </div>
                        <button type="button" class="btn-solid btn-report" onclick="window.location.href='complaints.php'">View Student Reports</button>
                    </div>
                </div>
            </div>

        </div> </div>
    </main>

    <footer></footer>

</body>
</html>