<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resolve_id'])) {
    $resolve_id = $_POST['resolve_id'];
    $stmtUpdate = $pdo->prepare("UPDATE `Feedback` SET resolution_status = 'Resolved' WHERE feedback_id = :id");
    $stmtUpdate->execute([':id' => $resolve_id]);
    
    header("Location: complaints.php");
    exit();
}

$search = $_GET['search'] ?? '';
$query = "
    SELECT f.feedback_id, f.student_id, f.subject_of_feedback 
    FROM `Feedback` f 
    WHERE (f.resolution_status IS NULL OR f.resolution_status != 'Resolved')
";
$params = [];

if (!empty($search)) {
    $query .= " AND (f.student_id LIKE :search OR f.subject_of_feedback LIKE :search)";
    $params[':search'] = "%$search%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Complaints</title>
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
        <div class="complaints-layout-wrapper">
            
            <div class="top-actions card">
                <div class="title-group">
                    <a href="dashboard.php" class="home-icon-link">
                        <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </a>
                    <h1 class="page-title">Student Complaints</h1>
                </div>
                
                <form method="GET" class="action-buttons" style="display: flex; gap: 10px;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Complaint" style="padding: 8px 12px; border: 2px solid var(--primary-accent); border-radius: 20px; outline: none; color: var(--primary-accent);">
                    <button type="submit" class="btn-solid">Search Complaint</button>
                    <button type="button" class="btn-solid" onclick="window.location.href='complaints.php'">Clear Filter</button>
                </form>
            </div>

            <div class="complaints-list">
                <?php if (count($complaints) > 0): ?>
                    <?php $count = 1; ?>
                    <?php foreach ($complaints as $row): ?>
                        <div class="card complaint-card" onclick="window.location.href='complaint_details.php?id=<?php echo $row['feedback_id']; ?>'">
                            <div class="complaint-content-left">
                                <div class="complaint-number"><?php echo $count++; ?></div>
                                <div class="complaint-details">
                                    <span>Student ID: <?php echo htmlspecialchars('S' . str_pad($row['student_id'], 4, '0', STR_PAD_LEFT)); ?></span>
                                    <span>Subject: <?php echo htmlspecialchars($row['subject_of_feedback']); ?></span>
                                </div>
                            </div>
                            <div class="complaint-content-right">
                                <form action="" method="POST" style="margin: 0; padding: 0;">
                                    <input type="hidden" name="resolve_id" value="<?php echo $row['feedback_id']; ?>">
                                    <button type="submit" class="btn-solid btn-resolve" onclick="event.stopPropagation(); alert('Complaint Resolved!');">Mark Resolved</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card complaint-card" style="justify-content: center;">
                        <h2 style="color: var(--primary-accent);">No pending complaints found.</h2>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer></footer>

</body>
</html>