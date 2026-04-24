<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

// Ensure the Section table can link to Faculty, and assign unassigned sections to this faculty for testing
$faculty_id = null;
if ($_SESSION['role'] === 'faculty') {
    $stmtFac = $pdo->prepare("SELECT faculty_id FROM `Faculty` WHERE user_id = :uid");
    $stmtFac->execute([':uid' => $_SESSION['user_id']]);
    $fac = $stmtFac->fetch();
    $faculty_id = $fac ? $fac['faculty_id'] : null;

    try {
        $pdo->exec("ALTER TABLE `Section` ADD COLUMN IF NOT EXISTS `faculty_id` INT");
        $pdo->exec("ALTER TABLE `Section` ADD COLUMN IF NOT EXISTS `course_name` VARCHAR(100)");
        if ($faculty_id) {
            $pdo->exec("UPDATE `Section` SET faculty_id = $faculty_id WHERE faculty_id IS NULL");
        }
    } catch (PDOException $e) {
        // Silently handle if columns already exist or db driver doesn't support IF NOT EXISTS
    }
}

$query = "
    SELECT e.enrollment_id, s.student_id, l.first_name, l.last_name, 
           sec.section_name, sec.course_name,
           g.midterm_grade, g.final_grade, g.status as grade_status
    FROM `Enrollment` e
    JOIN `Student` s ON e.student_id = s.student_id
    JOIN `Login` l ON s.user_id = l.user_id
    JOIN `Section` sec ON e.section_id = sec.section_id
    LEFT JOIN `Grades` g ON e.enrollment_id = g.enrollment_id
";

$params = [];
if ($_SESSION['role'] === 'faculty' && $faculty_id) {
    $query .= " WHERE sec.faculty_id = :fid";
    $params[':fid'] = $faculty_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$grades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Grades</title>
    <link rel="stylesheet" href="style/styles.css">
</head>
<body class="layout-dashboard no-main-scroll">

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
        <div class="list-layout-wrapper">
            
            <div class="top-actions card">
                <div class="title-group">
                    <a href="dashboard.php" class="home-icon-link">
                        <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </a>
                    <h1 class="page-title">My Class Grades</h1>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-solid" onclick="window.location.reload();">Refresh List</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Student ID</th>
                            <th rowspan="2">Student Name</th>
                            <th rowspan="2">Course / Section</th>
                            <th colspan="3" class="text-center">Grades</th>
                            <th rowspan="2" class="icon-col">
                                <svg class="edit-icon" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </th>
                        </tr>
                        <tr>
                            <th class="sub-header">Midterm</th>
                            <th class="sub-header">Final</th>
                            <th class="sub-header">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($grades) > 0): ?>
                            <?php foreach ($grades as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars('S' . str_pad($row['student_id'], 4, '0', STR_PAD_LEFT)); ?></td>
                                    <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars(($row['course_name'] ?? 'Subject') . ' - ' . ($row['section_name'] ?? 'Section')); ?></td>
                                    <td><?php echo htmlspecialchars($row['midterm_grade'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['final_grade'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['grade_status'] ?? 'N/A'); ?></td>
                                    <td class="icon-col">
                                        <a href="editgrade.php?eid=<?php echo $row['enrollment_id']; ?>">
                                            <svg class="edit-icon" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center;">No students found in your assigned courses.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <footer></footer>

</body>
</html>