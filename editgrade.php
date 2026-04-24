<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$enrollment_id = $_GET['eid'] ?? '';
$message = '';

// Determine the student_id from the passed enrollment_id
$stmtStudent = $pdo->prepare("SELECT student_id FROM `Enrollment` WHERE enrollment_id = :eid");
$stmtStudent->execute([':eid' => $enrollment_id]);
$student_id = $stmtStudent->fetchColumn();

// Fetch all courses this student is enrolled in for the dropdown
$allEnrollments = [];
if ($student_id) {
    $stmtAll = $pdo->prepare("
        SELECT e.enrollment_id, sec.course_name, sec.section_name 
        FROM `Enrollment` e
        JOIN `Section` sec ON e.section_id = sec.section_id
        WHERE e.student_id = :sid
    ");
    $stmtAll->execute([':sid' => $student_id]);
    $allEnrollments = $stmtAll->fetchAll();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $enrollment_id) {
    try {
        $midterm = !empty($_POST['midterm_grade']) ? (float)$_POST['midterm_grade'] : null;
        $final = !empty($_POST['final_grade']) ? (float)$_POST['final_grade'] : null;
        
        // Dynamically enforce status based on backend logic if final grade exists
        $status = $_POST['status'];
        if ($final !== null) {
            if ($final >= 1.0 && $final <= 3.0) {
                $status = 'Passed';
            } elseif ($final > 3.0 && $final <= 4.0) {
                $status = 'Removal';
            } elseif ($final > 4.0) {
                $status = 'Failed';
            }
        }

        $check = $pdo->prepare("SELECT grades_id FROM `Grades` WHERE enrollment_id = :eid");
        $check->execute([':eid' => $enrollment_id]);
        
        if ($check->rowCount() > 0) {
            $stmtGradeUpdate = $pdo->prepare("UPDATE `Grades` SET midterm_grade = :mid, final_grade = :fin, status = :stat WHERE enrollment_id = :eid");
            $stmtGradeUpdate->execute([
                ':mid' => $midterm,
                ':fin' => $final,
                ':stat' => $status,
                ':eid' => $enrollment_id
            ]);
        } else {
            $stmtGradeInsert = $pdo->prepare("INSERT INTO `Grades` (enrollment_id, midterm_grade, final_grade, status) VALUES (:eid, :mid, :fin, :stat)");
            $stmtGradeInsert->execute([
                ':eid' => $enrollment_id,
                ':mid' => $midterm,
                ':fin' => $final,
                ':stat' => $status
            ]);
        }
        $message = "Grades updated successfully!";
    } catch (PDOException $e) {
        $message = "Update failed: " . $e->getMessage();
    }
}

$stmtData = $pdo->prepare("
    SELECT l.first_name, l.last_name, sec.section_name, sec.course_name, g.midterm_grade, g.final_grade, g.status 
    FROM `Enrollment` e
    JOIN `Student` s ON e.student_id = s.student_id
    JOIN `Login` l ON s.user_id = l.user_id 
    JOIN `Section` sec ON e.section_id = sec.section_id
    LEFT JOIN `Grades` g ON e.enrollment_id = g.enrollment_id 
    WHERE e.enrollment_id = :eid 
");
$stmtData->execute([':eid' => $enrollment_id]);
$gradeData = $stmtData->fetch();

if (!$gradeData) {
    echo "Enrollment record not found.";
    exit();
}

$dropdownStyle = "width: 100%; padding: 12px 20px; border: 3px solid #BC5F04; border-radius: 30px; font-size: 1rem; color: #BC5F04; background-color: #FFFFFF; outline: none; box-sizing: border-box; cursor: pointer; appearance: none; -webkit-appearance: none;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grades</title>
    <link rel="stylesheet" href="style/styles.css">
    <script>
        function calculateStatus() {
            const finalGradeInput = document.getElementById('final_grade').value;
            const statusSelect = document.getElementById('status');
            
            if (finalGradeInput) {
                const fg = parseFloat(finalGradeInput);
                if (fg >= 1.0 && fg <= 3.0) {
                    statusSelect.value = 'Passed';
                } else if (fg > 3.0 && fg <= 4.0) {
                    statusSelect.value = 'Removal';
                } else if (fg > 4.0) {
                    statusSelect.value = 'Failed';
                }
            }
        }
    </script>
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
                <h2 class="login-title" style="text-align: center; margin-bottom: 5px;">
                    Update Grades
                </h2>
                <p style="text-align: center; font-size: 1.5rem; color: #2B0504; font-weight: bold; margin-bottom: 25px;">
                    <?php echo htmlspecialchars($gradeData['first_name'] . ' ' . $gradeData['last_name']); ?>
                </p>
                <hr style="margin-bottom: 25px;">
                
                <?php if ($message): ?>
                    <p style="color: <?php echo strpos($message, 'failed') !== false ? 'red' : 'green'; ?>; font-weight: bold; text-align: center; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <div class="form-group">
                    <label>Select Course / Section</label>
                    <select onchange="window.location.href='editgrade.php?eid=' + this.value" style="<?php echo $dropdownStyle; ?>">
                        <?php foreach($allEnrollments as $enr): ?>
                            <option value="<?php echo $enr['enrollment_id']; ?>" <?php if($enr['enrollment_id'] == $enrollment_id) echo 'selected'; ?>>
                                <?php echo htmlspecialchars(($enr['course_name'] ?? 'Subject') . ' - ' . ($enr['section_name'] ?? 'Section')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Midterm Grade</label>
                    <input type="number" step="0.01" name="midterm_grade" value="<?php echo htmlspecialchars($gradeData['midterm_grade'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Final Grade</label>
                    <input type="number" step="0.01" id="final_grade" name="final_grade" value="<?php echo htmlspecialchars($gradeData['final_grade'] ?? ''); ?>" oninput="calculateStatus()">
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" style="<?php echo $dropdownStyle; ?>">
                        <option value="Passed" <?php if(($gradeData['status'] ?? '') == 'Passed') echo 'selected'; ?>>Passed</option>
                        <option value="Failed" <?php if(($gradeData['status'] ?? '') == 'Failed') echo 'selected'; ?>>Failed</option>
                        <option value="Removal" <?php if(($gradeData['status'] ?? '') == 'Removal') echo 'selected'; ?>>Removal</option>
                        <option value="Incomplete" <?php if(($gradeData['status'] ?? '') == 'Incomplete') echo 'selected'; ?>>Incomplete</option>
                        <option value="Dropped" <?php if(($gradeData['status'] ?? '') == 'Dropped') echo 'selected'; ?>>Dropped</option>
                    </select>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn" style="flex: 1; padding: 14px 20px; margin: 0;">Save Grades</button>
                    <button type="button" class="btn" style="flex: 1; padding: 14px 20px; margin: 0;" onclick="window.location.href='studentgrades.php'">Back to Grades</button>
                </div>
            </form>
        </div>
    </main>
    
    <footer></footer>

</body>
</html>