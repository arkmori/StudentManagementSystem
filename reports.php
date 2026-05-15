<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

try {
    if ($_SESSION['role'] === 'faculty') {
        // Retrieve the specific faculty ID for the logged-in user
        $stmtFac = $pdo->prepare("SELECT faculty_id FROM `Faculty` WHERE user_id = ?");
        $stmtFac->execute([$_SESSION['user_id']]);
        $faculty_id = $stmtFac->fetchColumn();

        if ($faculty_id) {
            // Count unique students enrolled in any section taught by this faculty
            $stmtTotalStudents = $pdo->prepare("
                SELECT COUNT(DISTINCT student_id) 
                FROM `Enrollment` 
                WHERE section_id IN (SELECT section_id FROM `Section` WHERE faculty_id = ?)
            ");
            $stmtTotalStudents->execute([$faculty_id]);
            $totalStudents = $stmtTotalStudents->fetchColumn();

            // Count unique passed students in this faculty's sections
            $stmtPassed = $pdo->prepare("
                SELECT COUNT(DISTINCT enrollment_id) 
                FROM `Grades` 
                WHERE status = 'Passed' AND enrollment_id IN (
                    SELECT enrollment_id FROM `Enrollment` WHERE section_id IN (
                        SELECT section_id FROM `Section` WHERE faculty_id = ?
                    )
                )
            ");
            $stmtPassed->execute([$faculty_id]);
            $passedStudents = $stmtPassed->fetchColumn();

            // Count sections assigned to this faculty
            $stmtSections = $pdo->prepare("SELECT COUNT(*) FROM `Section` WHERE faculty_id = ?");
            $stmtSections->execute([$faculty_id]);
            $totalSections = $stmtSections->fetchColumn();

            // Count unique courses taught by this faculty
            $stmtCourses = $pdo->prepare("SELECT COUNT(DISTINCT course_name) FROM `Section` WHERE faculty_id = ?");
            $stmtCourses->execute([$faculty_id]);
            $totalCourses = $stmtCourses->fetchColumn();

            // Count unique graded enrollments in this faculty's sections
            $stmtGraded = $pdo->prepare("
                SELECT COUNT(DISTINCT enrollment_id) 
                FROM `Grades` 
                WHERE enrollment_id IN (
                    SELECT enrollment_id FROM `Enrollment` WHERE section_id IN (
                        SELECT section_id FROM `Section` WHERE faculty_id = ?
                    )
                )
            ");
            $stmtGraded->execute([$faculty_id]);
            $studentsGraded = $stmtGraded->fetchColumn();

            // Count unique cleared students among those taught by this faculty
            $stmtCleared = $pdo->prepare("
                SELECT COUNT(DISTINCT student_id) 
                FROM `Clearance` 
                WHERE status = 'Cleared' AND student_id IN (
                    SELECT student_id FROM `Enrollment` WHERE section_id IN (
                        SELECT section_id FROM `Section` WHERE faculty_id = ?
                    )
                )
            ");
            $stmtCleared->execute([$faculty_id]);
            $studentsCleared = $stmtCleared->fetchColumn();

            // Count feedback/concerns from students taught by this faculty
            $stmtConcerns = $pdo->prepare("
                SELECT COUNT(*) 
                FROM `Feedback` 
                WHERE student_id IN (
                    SELECT student_id FROM `Enrollment` WHERE section_id IN (
                        SELECT section_id FROM `Section` WHERE faculty_id = ?
                    )
                )
            ");
            $stmtConcerns->execute([$faculty_id]);
            $studentConcerns = $stmtConcerns->fetchColumn();
        } else {
            // Fallback to zeros if a faculty record somehow doesn't exist for this user
            $totalStudents = $passedStudents = $totalSections = $totalCourses = $studentsGraded = $studentsCleared = $studentConcerns = 0;
        }
    } else {
        // Non-faculty roles (e.g., admin) see global analytics
        $totalStudents = $pdo->query("SELECT COUNT(*) FROM `Student`")->fetchColumn();
        $passedStudents = $pdo->query("SELECT COUNT(DISTINCT enrollment_id) FROM `Grades` WHERE status = 'Passed'")->fetchColumn();
        $totalSections = $pdo->query("SELECT COUNT(*) FROM `Section`")->fetchColumn();
        $totalCourses = $pdo->query("SELECT COUNT(*) FROM `Course`")->fetchColumn();
        $studentsGraded = $pdo->query("SELECT COUNT(DISTINCT enrollment_id) FROM `Grades`")->fetchColumn();
        $studentsCleared = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM `Clearance` WHERE status = 'Cleared'")->fetchColumn();
        $studentConcerns = $pdo->query("SELECT COUNT(*) FROM `Feedback`")->fetchColumn();
    }
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
                    <div class="stat-num-box"><?php echo htmlspecialchars($studentsCleared); ?></div>
                    <div class="stat-text-box">Cleared</div>
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