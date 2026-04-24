<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$search = $_GET['search'] ?? '';

$query = "
    SELECT e.enrollment_id, s.student_id, l.first_name, l.last_name, 
           c.college_name, sec.section_name, e.enrollment_date
    FROM `Enrollment` e
    JOIN `Student` s ON e.student_id = s.student_id
    JOIN `Login` l ON s.user_id = l.user_id
    LEFT JOIN `College` c ON s.college_id = c.college_id
    LEFT JOIN `Section` sec ON e.section_id = sec.section_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (l.first_name LIKE :search OR l.last_name LIKE :search OR s.student_id LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY e.enrollment_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Enrollment History</title>
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
                    <h1 class="page-title">Enrollment History</h1>
                </div>
                
                <form method="GET" class="action-buttons" style="display: flex; gap: 10px;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID or Name" style="padding: 8px 12px; border: 2px solid var(--primary-accent); border-radius: 20px; outline: none; color: var(--primary-accent);">
                    <button type="submit" class="btn-solid">Search Student</button>
                    <button type="button" class="btn-solid" onclick="window.location.href='enrollmenthistory.php'">Clear Filter</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>College</th>
                            <th>Courses / Section Enrolled</th>
                            <th>Enrollment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history) > 0): ?>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars('S' . str_pad($row['student_id'], 4, '0', STR_PAD_LEFT)); ?></td>
                                    <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['section_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['enrollment_date'] ? date('m/d/Y', strtotime($row['enrollment_date'])) : 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">No enrollment records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <footer></footer>

</body>
</html>