<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$query = "
    SELECT s.student_id, l.first_name, l.last_name, c.college_name,
           (SELECT COUNT(*) FROM `Enrollment` e WHERE e.student_id = s.student_id) as enrolled_count
    FROM `Student` s
    JOIN `Login` l ON s.user_id = l.user_id
    LEFT JOIN `College` c ON s.college_id = c.college_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (l.first_name LIKE :search OR l.last_name LIKE :search OR s.student_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filter)) {
    $query .= " AND c.college_name = :filter";
    $params[':filter'] = $filter;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

$colleges = $pdo->query("SELECT college_name FROM `College`")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Student List</title>
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
                    <h1 class="page-title">Student List</h1>
                </div>
                
                <form method="GET" class="action-buttons" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="btn-solid" onclick="window.location.href='studentenrollment.php'">Add Student</button>
                    <button type="button" class="btn-solid" onclick="window.location.href='studentgrades.php'">View/Update Grades</button>
                    
                    <select name="filter" class="btn-solid" style="background-color: #fff; color: var(--primary-accent) !important; border: 2px solid var(--primary-accent); cursor: pointer;">
                        <option value="">All Colleges</option>
                        <?php foreach($colleges as $col): ?>
                            <option value="<?php echo htmlspecialchars($col); ?>" <?php if($filter == $col) echo 'selected'; ?>><?php echo htmlspecialchars($col); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID or Name" style="padding: 8px 12px; border: 2px solid var(--primary-accent); border-radius: 20px; outline: none; color: var(--primary-accent);">
                    <button type="submit" class="btn-solid">Search / Filter</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>College</th>
                            <th>Courses Enrolled</th>
                            <th>Status</th>
                            <th class="icon-col">
                                <svg class="edit-icon" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars('S' . str_pad($row['student_id'], 4, '0', STR_PAD_LEFT)); ?></td>
                                    <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['enrolled_count']); ?></td>
                                    <td>Active</td>
                                    <td class="icon-col">
                                        <a href="editstudent.php?id=<?php echo $row['student_id']; ?>">
                                            <svg class="edit-icon" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center;">No students found matching the criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <footer></footer>

</body>
</html>