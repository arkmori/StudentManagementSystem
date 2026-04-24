<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';
$user_id = $_SESSION['user_id'];
$message = '';

// Fetch current user details to check role and faculty ID before POST processing
$stmtInit = $pdo->prepare("SELECT l.role_id, r.role_name, f.faculty_id FROM `Login` l LEFT JOIN `Role` r ON l.role_id = r.role_id LEFT JOIN `Faculty` f ON l.user_id = f.user_id WHERE l.user_id = :uid");
$stmtInit->execute([':uid' => $user_id]);
$initUser = $stmtInit->fetch();
$is_faculty = strtolower($initUser['role_name'] ?? '') === 'faculty';
$faculty_id = $initUser['faculty_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        $stmtUser = $pdo->prepare("UPDATE `Login` SET first_name = :f, middle_name = :m, last_name = :l, gender = :g, date_of_birth = :d WHERE user_id = :uid");
        $stmtUser->execute([':f'=>$_POST['first_name'], ':m'=>$_POST['middle_name'], ':l'=>$_POST['last_name'], ':g'=>$_POST['gender'], ':d'=>$_POST['dob'], ':uid'=>$user_id]);

        $stmtExt = $pdo->prepare("INSERT INTO `ProfileExt` (user_id, civil_status, employed_since, position) VALUES (:uid, :s, :e, :p) ON DUPLICATE KEY UPDATE civil_status = :s, employed_since = :e, position = :p");
        $stmtExt->execute([':uid'=>$user_id, ':s'=>$_POST['status'], ':e'=>$_POST['employed_since'], ':p'=>$_POST['position']]);

        $stmtFac = $pdo->prepare("UPDATE `Faculty` SET college_id = :cid WHERE user_id = :uid");
        $stmtFac->execute([':cid' => $_POST['college_id'], ':uid' => $user_id]);

        // Process newly added course/section if provided
        if ($is_faculty && $faculty_id && !empty($_POST['new_course_name']) && !empty($_POST['new_section_name'])) {
            $nc = trim($_POST['new_course_name']);
            $ns = trim($_POST['new_section_name']);
            
            $stmtC = $pdo->prepare("SELECT course_id FROM `Course` WHERE course_name = :c");
            $stmtC->execute([':c' => $nc]);
            if (!$stmtC->fetch()) {
                $pdo->prepare("INSERT INTO `Course` (course_name) VALUES (:c)")->execute([':c' => $nc]);
            }
            
            $stmtS = $pdo->prepare("INSERT INTO `Section` (course_name, section_name, faculty_id) VALUES (:c, :s, :fid)");
            $stmtS->execute([':c' => $nc, ':s' => $ns, ':fid' => $faculty_id]);
        }

        $pdo->commit();
        header("Location: profile.php");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Error: " . $e->getMessage();
    }
}

// Re-fetch all data to populate the HTML form
$stmt = $pdo->prepare("SELECT l.*, pe.civil_status, pe.employed_since, pe.position, f.college_id FROM `Login` l LEFT JOIN `ProfileExt` pe ON l.user_id = pe.user_id LEFT JOIN `Faculty` f ON l.user_id = f.user_id WHERE l.user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch();

$colleges = $pdo->query("SELECT * FROM `College`")->fetchAll();

$my_classes = [];
if ($is_faculty && $faculty_id) {
    $stmtMC = $pdo->prepare("SELECT course_name, section_name FROM `Section` WHERE faculty_id = :fid ORDER BY course_name ASC");
    $stmtMC->execute([':fid' => $faculty_id]);
    $my_classes = $stmtMC->fetchAll();
}

$dropdownStyle = "width: 100%; padding: 12px 20px; border: 3px solid #BC5F04; border-radius: 30px; font-size: 1rem; color: #BC5F04; background-color: #FFFFFF; outline: none; box-sizing: border-box; cursor: pointer; appearance: none; -webkit-appearance: none;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style/styles.css">
    <title>Edit Profile</title>
    <style>
        input[type="date"] {
            color: #BC5F04;
        }
    </style>
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
        <div class="login-container" style="max-width: 600px; margin: 40px auto; background: white; padding: 40px; border-radius: 25px; border: 4px solid #2B0504;">
            <form action="" method="POST">
                <h2 class="login-title" style="text-align: center; margin-bottom: 20px;">Edit Profile</h2>
                <hr style="margin-bottom: 25px;">
                
                <?php if ($message): ?>
                    <p style="color: red; font-weight: bold; text-align: center;"><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>

                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" style="<?php echo $dropdownStyle; ?>">
                        <option value="male" <?php if(($user['gender'] ?? '') == 'male') echo 'selected'; ?>>Male</option>
                        <option value="female" <?php if(($user['gender'] ?? '') == 'female') echo 'selected'; ?>>Female</option>
                        <option value="prefer-not-to-say" <?php if(($user['gender'] ?? '') == 'prefer-not-to-say') echo 'selected'; ?>>I'd rather not say</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Civil Status</label>
                    <select name="status" style="<?php echo $dropdownStyle; ?>">
                        <option value="" disabled <?php if(empty($user['civil_status'])) echo 'selected'; ?>>Select Status</option>
                        <option value="Single" <?php if(($user['civil_status'] ?? '') == 'Single') echo 'selected'; ?>>Single</option>
                        <option value="Married" <?php if(($user['civil_status'] ?? '') == 'Married') echo 'selected'; ?>>Married</option>
                        <option value="Widowed" <?php if(($user['civil_status'] ?? '') == 'Widowed') echo 'selected'; ?>>Widowed</option>
                        <option value="Legally Separated" <?php if(($user['civil_status'] ?? '') == 'Legally Separated') echo 'selected'; ?>>Legally Separated</option>
                    </select>
                </div>
                
                <hr style="margin: 25px 0;">
                
                <div class="form-group">
                    <label>College & Department</label>
                    <select name="college_id" style="<?php echo $dropdownStyle; ?>">
                        <option value="" disabled <?php if(empty($user['college_id'])) echo 'selected'; ?>>Select College</option>
                        <?php foreach ($colleges as $c): ?>
                            <option value="<?php echo $c['college_id']; ?>" <?php if(($user['college_id'] ?? '') == $c['college_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($c['college_name'] . " - " . $c['department']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Employed Since</label>
                    <input type="date" name="employed_since" value="<?php echo htmlspecialchars($user['employed_since'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>" placeholder="e.g. Instructor I">
                </div>

                <?php if ($is_faculty): ?>
                    <hr style="margin: 25px 0;">
                    <h3 style="color: var(--primary-accent); margin-bottom: 15px;">Add Handled Course</h3>
                    
                    <?php if(count($my_classes) > 0): ?>
                        <p style="color: var(--primary-accent); font-weight: bold; margin-bottom: 5px; font-size: 0.9rem;">Currently Teaching:</p>
                        <ul style="color: var(--primary-accent); padding-left: 20px; margin-bottom: 20px; font-size: 0.9rem;">
                            <?php foreach($my_classes as $mc): ?>
                                <li><?php echo htmlspecialchars($mc['course_name'] . ' - ' . $mc['section_name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>New Course Name</label>
                        <input type="text" name="new_course_name" placeholder="e.g. CS101">
                    </div>

                    <div class="form-group">
                        <label>New Section Name</label>
                        <input type="text" name="new_section_name" placeholder="e.g. 3A">
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn" style="flex: 1; padding: 14px 20px; margin: 0;">Update Profile</button>
                    <button type="button" class="btn" style="flex: 1; padding: 14px 20px; margin: 0;" onclick="window.location.href='profile.php'">Cancel</button>
                </div>
            </form>
        </div>
    </main>

    <footer></footer>

</body>
</html>