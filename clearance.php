<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$search = $_GET['search'] ?? '';

$query = "
    SELECT s.student_id, l.first_name, l.last_name, c.college_name, cl.status
    FROM `Student` s
    JOIN `Login` l ON s.user_id = l.user_id
    LEFT JOIN `College` c ON s.college_id = c.college_id
    LEFT JOIN `Clearance` cl ON s.student_id = cl.student_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (s.student_id LIKE :search OR l.first_name LIKE :search OR l.last_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clearances = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Clearance</title>
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
                    <h1 class="page-title">Clearance</h1>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-solid" onclick="window.location.href='clearance.php'">Clear Filter</button>
                </div>
            </div>

            <div class="clearance-content-grid">
                
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>College</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($clearances) > 0): ?>
                                <?php foreach ($clearances as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars('S' . str_pad($row['student_id'], 4, '0', STR_PAD_LEFT)); ?></td>
                                        <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'Cleared'): ?>
                                                <span class="status-badge badge-cleared">
                                                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                                    Cleared
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge badge-error">
                                                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                                    <?php echo htmlspecialchars($row['status'] ?? 'Not Cleared'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center;">No clearance records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card clearance-sidebar">
                    <form method="GET" class="search-input-wrapper">
                        <input type="text" name="search" class="search-input" placeholder="Student ID or Name" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" style="background: none; border: none; padding: 0; margin: 0; cursor: pointer;">
                            <svg class="search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <footer></footer>

</body>
</html>