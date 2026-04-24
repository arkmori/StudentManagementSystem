<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$user_id = $_SESSION['user_id'];

$name = 'N/A';
$email = 'N/A';
$gender = 'N/A';
$dob = 'N/A';
$role_name = 'N/A';
$status = 'N/A';
$faculty_id = 'N/A';
$department = 'N/A';
$college = 'N/A';
$employed_since = 'N/A';
$position = 'N/A';

try {
    $stmt = $pdo->prepare("
        SELECT 
            l.first_name, l.middle_name, l.last_name, l.gender, l.date_of_birth,
            c.email,
            r.role_name,
            f.faculty_id,
            col.college_name, col.department,
            pe.civil_status, pe.employed_since, pe.position
        FROM `Login` l
        LEFT JOIN `Contact` c ON l.contact_id = c.contact_id
        LEFT JOIN `Role` r ON l.role_id = r.role_id
        LEFT JOIN `Faculty` f ON l.user_id = f.user_id
        LEFT JOIN `College` col ON f.college_id = col.college_id
        LEFT JOIN `ProfileExt` pe ON l.user_id = pe.user_id
        WHERE l.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        $middle_initial = !empty($user['middle_name']) ? ' ' . $user['middle_name'] : '';
        $name = trim($user['last_name'] . ', ' . $user['first_name'] . $middle_initial);
        $email = $user['email'] ?: 'N/A';
        $gender = ucfirst($user['gender'] ?: 'N/A');
        $dob = $user['date_of_birth'] ? date('m/d/Y', strtotime($user['date_of_birth'])) : 'N/A';
        $role_name = ucfirst($user['role_name'] ?: 'N/A');
        $status = $user['civil_status'] ?: 'N/A';
        
        if (strtolower($user['role_name']) === 'faculty') {
            $faculty_id = $user['faculty_id'] ? 'F' . str_pad($user['faculty_id'], 4, '0', STR_PAD_LEFT) : 'N/A';
            $department = $user['department'] ?: 'N/A';
            $college = $user['college_name'] ?: 'N/A';
            $employed_since = $user['employed_since'] ? date('m/d/Y', strtotime($user['employed_since'])) : 'N/A';
            $position = $user['position'] ?: 'N/A';
        }
    }
} catch (PDOException $e) {
    $name = 'Error loading profile';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Profile</title>
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
        <div class="my-profile-wrapper">
            
            <div class="top-actions card">
                <div class="title-group">
                    <a href="dashboard.php" class="home-icon-link">
                        <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </a>
                    <h1 class="page-title">Profile</h1>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn-solid" onclick="window.location.href='editprofile.php'">Edit Profile</button>
                </div>
            </div>

            <div class="card profile-split-card">
                
                <div class="profile-col">
                    <h2 class="profile-col-title">Personal Information</h2>
                    
                    <div class="profile-square-avatar"></div>
                    
                    <div class="profile-data-list">
                        <div class="profile-data-item">Name: <?php echo htmlspecialchars($name); ?></div>
                        <div class="profile-data-item">Email: <?php echo htmlspecialchars($email); ?></div>
                        <div class="profile-data-item">Sex: <?php echo htmlspecialchars($gender); ?></div>
                        <div class="profile-data-item">Birth Date: <?php echo htmlspecialchars($dob); ?></div>
                        <div class="profile-data-item">Status: <?php echo htmlspecialchars($status); ?></div>
                        <div class="profile-data-item"><?php echo htmlspecialchars($role_name); ?></div>
                    </div>
                </div>

                <div class="profile-line"></div>

                <div class="profile-col">
                    <h2 class="profile-col-title">Faculty Information</h2>
                    
                    <div class="profile-data-list" style="margin-top: 55px;">
                        <div class="profile-data-item">Faculty ID: <?php echo htmlspecialchars($faculty_id); ?></div>
                        <div class="profile-data-item">Department: <?php echo htmlspecialchars($department); ?></div>
                        <div class="profile-data-item">College/Office: <?php echo htmlspecialchars($college); ?></div>
                        <div class="profile-data-item">Employed since: <?php echo htmlspecialchars($employed_since); ?></div>
                        <div class="profile-data-item">Position: <?php echo htmlspecialchars($position); ?></div>
                        <div class="profile-data-item">Courses: N/A</div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer></footer>

</body>
</html>