<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$student_id = $_GET['id'] ?? '';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($student_id)) {
    try {
        $pdo->beginTransaction();

        $stmtUser = $pdo->prepare("
            UPDATE `Login` l 
            JOIN `Student` s ON l.user_id = s.user_id 
            SET l.first_name = :fname, l.middle_name = :mname, l.last_name = :lname, l.gender = :sex 
            WHERE s.student_id = :sid
        ");
        $stmtUser->execute([
            ':fname' => $_POST['first_name'],
            ':mname' => $_POST['middle_name'],
            ':lname' => $_POST['last_name'],
            ':sex' => $_POST['sex'],
            ':sid' => $student_id
        ]);

        $stmtStudent = $pdo->prepare("UPDATE `Student` SET college_id = :cid, year_level = :ylvl WHERE student_id = :sid");
        $stmtStudent->execute([
            ':cid' => $_POST['college_id'],
            ':ylvl' => $_POST['year_level'],
            ':sid' => $student_id
        ]);

        $stmtCheckClearance = $pdo->prepare("SELECT clearance_id FROM `Clearance` WHERE student_id = :sid ORDER BY date_process DESC LIMIT 1");
        $stmtCheckClearance->execute([':sid' => $student_id]);
        $clearData = $stmtCheckClearance->fetch();

        if ($clearData) {
            $stmtClearance = $pdo->prepare("UPDATE `Clearance` SET status = :stat, date_process = CURDATE() WHERE clearance_id = :cid");
            $stmtClearance->execute([':stat' => $_POST['clearance_status'], ':cid' => $clearData['clearance_id']]);
        } else {
            $stmtClearance = $pdo->prepare("INSERT INTO `Clearance` (student_id, status, date_process) VALUES (:sid, :stat, CURDATE())");
            $stmtClearance->execute([':sid' => $student_id, ':stat' => $_POST['clearance_status']]);
        }

        // Enrollment logic now purely relies on the dropdown ID
        $new_section_id = $_POST['section_id'] ?? '';
        if (!empty($new_section_id)) {
            $stmtCheckEnroll = $pdo->prepare("SELECT enrollment_id FROM `Enrollment` WHERE student_id = :sid ORDER BY enrollment_date DESC LIMIT 1");
            $stmtCheckEnroll->execute([':sid' => $student_id]);
            $enrollRow = $stmtCheckEnroll->fetch();

            if ($enrollRow) {
                $stmtUpdateEnroll = $pdo->prepare("UPDATE `Enrollment` SET section_id = :secid WHERE enrollment_id = :eid");
                $stmtUpdateEnroll->execute([':secid' => $new_section_id, ':eid' => $enrollRow['enrollment_id']]);
            } else {
                $stmtInsertEnroll = $pdo->prepare("INSERT INTO `Enrollment` (student_id, section_id, enrollment_date, status) VALUES (:sid, :secid, CURDATE(), 'Active')");
                $stmtInsertEnroll->execute([':sid' => $student_id, ':secid' => $new_section_id]);
            }
        }

        $pdo->commit();
        $message = "Student information updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Update failed: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("
    SELECT s.*, l.first_name, l.middle_name, l.last_name, l.gender 
    FROM `Student` s 
    JOIN `Login` l ON s.user_id = l.user_id 
    WHERE s.student_id = :sid
");
$stmt->execute([':sid' => $student_id]);
$student = $stmt->fetch();

$stmtClear = $pdo->prepare("SELECT status FROM `Clearance` WHERE student_id = :sid ORDER BY date_process DESC LIMIT 1");
$stmtClear->execute([':sid' => $student_id]);
$clearanceInfo = $stmtClear->fetch();
$clearance_status = $clearanceInfo ? $clearanceInfo['status'] : 'Not Cleared';

// Fetch current section ID to auto-select in the dropdown
$stmtEnroll = $pdo->prepare("SELECT section_id FROM `Enrollment` WHERE student_id = :sid ORDER BY enrollment_date DESC LIMIT 1");
$stmtEnroll->execute([':sid' => $student_id]);
$enrollmentInfo = $stmtEnroll->fetch();
$current_section_id = $enrollmentInfo ? $enrollmentInfo['section_id'] : '';

$colleges = $pdo->query("SELECT * FROM `College`")->fetchAll();

// Fetch all available sections registered by faculties to populate the dropdown
$all_sections = $pdo->query("
    SELECT sec.section_id, sec.course_name, sec.section_name, l.last_name 
    FROM `Section` sec 
    LEFT JOIN `Faculty` f ON sec.faculty_id = f.faculty_id 
    LEFT JOIN `Login` l ON f.user_id = l.user_id
    ORDER BY sec.course_name ASC
")->fetchAll();

if (!$student) {
    echo "Student not found.";
    exit();
}

$dropdownStyle = "width: 100%; padding: 12px 20px; border: 3px solid #BC5F04; border-radius: 30px; font-size: 1rem; color: #BC5F04; background-color: #FFFFFF; outline: none; box-sizing: border-box; cursor: pointer; appearance: none; -webkit-appearance: none;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Information</title>
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
        <div class="login-container" style="max-width: 600px; margin: 40px auto; background: white; padding: 40px; border-radius: 25px; border: 4px solid #2B0504;">
            <form action="" method="POST">
                <h2 class="login-title" style="text-align: center; margin-bottom: 20px;">Edit Student</h2>
                <hr style="margin-bottom: 25px;">
                
                <?php if ($message): ?>
                    <p style="color: <?php echo strpos($message, 'failed') !== false ? 'red' : 'green'; ?>; font-weight: bold; text-align: center; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <input type="text" name="sex" value="<?php echo htmlspecialchars($student['gender'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Year Level</label>
                    <input type="number" name="year_level" value="<?php echo htmlspecialchars($student['year_level'] ?? '1'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>College</label>
                    <select name="college_id" style="<?php echo $dropdownStyle; ?>">
                        <?php foreach ($colleges as $c): ?>
                            <option value="<?php echo $c['college_id']; ?>" <?php if($student['college_id'] == $c['college_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($c['college_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr style="margin: 25px 0;">
                
                <h3 style="color: var(--primary-accent); margin-bottom: 15px;">Enrollment & Clearance</h3>

                <div class="form-group">
                    <label>Assign Course / Section</label>
                    <select name="section_id" style="<?php echo $dropdownStyle; ?>">
                        <option value="">-- No Enrollment --</option>
                        <?php foreach($all_sections as $sec): ?>
                            <option value="<?php echo $sec['section_id']; ?>" <?php if($sec['section_id'] == $current_section_id) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($sec['course_name'] . ' - ' . $sec['section_name'] . ' (Prof. ' . ($sec['last_name'] ?? 'Unassigned') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Clearance Status</label>
                    <select name="clearance_status" style="<?php echo $dropdownStyle; ?>">
                        <option value="Cleared" <?php if($clearance_status === 'Cleared') echo 'selected'; ?>>Cleared</option>
                        <option value="Not Cleared" <?php if($clearance_status !== 'Cleared') echo 'selected'; ?>>Not Cleared</option>
                    </select>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn" style="flex: 1; padding: 14px 20px; margin: 0;">Update Student</button>
                    <button type="button" class="btn" style="flex: 1; padding: 14px 20px; margin: 0;" onclick="window.location.href='studentlist.php'">Back to List</button>
                </div>
            </form>
        </div>
    </main>
    
    <footer></footer>

</body>
</html>