<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';
$message = '';

$existingColleges = $pdo->query("SELECT college_name FROM `College` ORDER BY college_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$defaultColleges = [
    'College of Nursing',
    'College of Business Management',
    'College of Human Ecology'
];
$collegeOptions = array_unique(array_merge($defaultColleges, $existingColleges));

$facultyCourses = [];
$faculty_id = null;
$sections = [];
if ($_SESSION['role'] === 'faculty') {
    $stmtFaculty = $pdo->prepare("SELECT faculty_id FROM `Faculty` WHERE user_id = ?");
    $stmtFaculty->execute([$_SESSION['user_id']]);
    $faculty = $stmtFaculty->fetch();
    $faculty_id = $faculty ? $faculty['faculty_id'] : null;
    if ($faculty_id) {
        $stmtCourses = $pdo->prepare("SELECT DISTINCT course_name FROM `Section` WHERE faculty_id = ? ORDER BY course_name ASC");
        $stmtCourses->execute([$faculty_id]);
        $facultyCourses = array_column($stmtCourses->fetchAll(PDO::FETCH_ASSOC), 'course_name');

        $stmtSections = $pdo->prepare("SELECT section_id, course_name, section_name FROM `Section` WHERE faculty_id = ? ORDER BY course_name ASC, section_name ASC");
        $stmtSections->execute([$faculty_id]);
        $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($_SESSION['role'] !== 'faculty' || !$faculty_id) {
    $sections = $pdo->query("SELECT section_id, course_name, section_name FROM `Section` ORDER BY course_name ASC, section_name ASC")->fetchAll();
    $facultyCourses = $pdo->query("SELECT course_name FROM `Course` ORDER BY course_name ASC")->fetchAll(PDO::FETCH_COLUMN);
}

function getSectionId(PDO $pdo, string $name) {
    $course = null;
    $section = trim($name);
    if (strpos($name, '-') !== false) {
        $parts = explode('-', $name, 2);
        $course = trim($parts[0]);
        $section = trim($parts[1]);
    }
    if ($course !== null && $course !== '') {
        $stmt = $pdo->prepare("SELECT section_id FROM `Section` WHERE course_name = :course AND section_name = :section");
        $stmt->execute([':course' => $course, ':section' => $section]);
    } else {
        $stmt = $pdo->prepare("SELECT section_id FROM `Section` WHERE section_name = :section");
        $stmt->execute([':section' => $section]);
    }
    $row = $stmt->fetch();
    if ($row) {
        return $row['section_id'];
    }
    $stmtInsert = $pdo->prepare("INSERT INTO `Section` (course_name, section_name) VALUES (:course, :section)");
    $stmtInsert->execute([':course' => $course, ':section' => $section]);
    return $pdo->lastInsertId();
}

function getSectionIdForCourse(PDO $pdo, ?string $courseName, string $section) {
    $section = trim($section);
    if ($courseName === null) {
        return getSectionId($pdo, $section);
    }
    $stmt = $pdo->prepare("SELECT section_id FROM `Section` WHERE course_name = :course AND section_name = :section");
    $stmt->execute([':course' => $courseName, ':section' => $section]);
    $row = $stmt->fetch();
    if ($row) {
        return $row['section_id'];
    }
    $stmtInsert = $pdo->prepare("INSERT INTO `Section` (course_name, section_name, faculty_id) VALUES (:course, :section, :fid)");
    $stmtInsert->execute([':course' => $courseName, ':section' => $section, ':fid' => $GLOBALS['faculty_id'] ?? null]);
    return $pdo->lastInsertId();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastName = $_POST['lastName'] ?? '';
    $firstName = $_POST['firstName'] ?? '';
    $middleName = $_POST['middleName'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $sex = strtolower($_POST['sex'] ?? '');
    $studentId = $_POST['studentId'] ?? '';
    $collegeName = $_POST['college'] ?? '';
    $yearLevel = $_POST['yearLevel'] ?? 1;
    $courseSelection = $_POST['course_id'] ?? '';
    $newCourseName = trim($_POST['new_course_name'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmtRole = $pdo->prepare("SELECT role_id FROM `Role` WHERE role_name = 'student'");
        $stmtRole->execute();
        $roleRow = $stmtRole->fetch();
        if (!$roleRow) {
            $pdo->exec("INSERT INTO `Role` (role_name) VALUES ('student')");
            $role_id = $pdo->lastInsertId();
        } else {
            $role_id = $roleRow['role_id'];
        }

        $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
        if (!empty($studentId)) {
            $username = $studentId;
            $useGeneratedUsername = false;
        } else {
            $username = 'tmp_' . bin2hex(random_bytes(4));
            $useGeneratedUsername = true;
        }

        $stmtUser = $pdo->prepare("
            INSERT INTO `Login` (user_name, password, first_name, middle_name, last_name, gender, date_of_birth, role_id) 
            VALUES (:uname, :pass, :fname, :mname, :lname, :gender, :dob, :rid)
        ");
        $stmtUser->execute([
            ':uname' => $username,
            ':pass' => $defaultPassword,
            ':fname' => $firstName,
            ':mname' => $middleName,
            ':lname' => $lastName,
            ':gender' => $sex,
            ':dob' => $birthdate,
            ':rid' => $role_id
        ]);
        $new_user_id = $pdo->lastInsertId();

        if (!empty($useGeneratedUsername)) {
            $newUsernameBase = 'S' . str_pad($new_user_id, 4, '0', STR_PAD_LEFT);
            $newUsername = $newUsernameBase;
            $suffix = 1;
            $stmtCheckUser = $pdo->prepare("SELECT COUNT(*) FROM `Login` WHERE user_name = :uname");
            while (true) {
                $stmtCheckUser->execute([':uname' => $newUsername]);
                if ($stmtCheckUser->fetchColumn() == 0) {
                    break;
                }
                $newUsername = $newUsernameBase . '_' . $suffix;
                $suffix++;
            }
            $stmtUpdateUser = $pdo->prepare("UPDATE `Login` SET user_name = :uname WHERE user_id = :uid");
            $stmtUpdateUser->execute([':uname' => $newUsername, ':uid' => $new_user_id]);
            $username = $newUsername;
        }

        $stmtCol = $pdo->prepare("SELECT college_id FROM `College` WHERE college_name = :cname");
        $stmtCol->execute([':cname' => $collegeName]);
        $colRow = $stmtCol->fetch();
        if (!$colRow && !empty($collegeName)) {
            $stmtInsertCol = $pdo->prepare("INSERT INTO `College` (college_name) VALUES (:cname)");
            $stmtInsertCol->execute([':cname' => $collegeName]);
            $college_id = $pdo->lastInsertId();
        } else {
            $college_id = $colRow ? $colRow['college_id'] : NULL;
        }

        $courseName = null;
        if ($courseSelection === '__add_new_course__' && $newCourseName !== '') {
            $stmtCourseCheck = $pdo->prepare("SELECT course_id FROM `Course` WHERE course_name = :c");
            $stmtCourseCheck->execute([':c' => $newCourseName]);
            if (!$stmtCourseCheck->fetch()) {
                $stmtInsertCourse = $pdo->prepare("INSERT INTO `Course` (course_name) VALUES (:c)");
                $stmtInsertCourse->execute([':c' => $newCourseName]);
            }
            $courseName = $newCourseName;
        } elseif ($courseSelection !== '' && $courseSelection !== '__add_new_course__') {
            $courseName = $courseSelection;
        }

        $stmtStudent = $pdo->prepare("INSERT INTO `Student` (user_id, year_level, college_id) VALUES (:uid, :ylvl, :cid)");
        $stmtStudent->execute([
            ':uid' => $new_user_id,
            ':ylvl' => $yearLevel,
            ':cid' => $college_id
        ]);
        $new_student_id = $pdo->lastInsertId();

        $sectionIds = [];
        if (!empty($_POST['section_ids'])) {
            $selected = $_POST['section_ids'];
            if (!is_array($selected)) {
                $selected = [$selected];
            }
            foreach ($selected as $sid) {
                if ($sid === '__add_new_section__') {
                    continue;
                }
                $sid = intval($sid);
                if ($sid > 0) {
                    $sectionIds[] = $sid;
                }
            }
        }

        if (!empty($newCourseName) && $courseSelection === '__add_new_course__') {
            // Ensure the new course is visible for current session
            if (!in_array($newCourseName, $facultyCourses, true)) {
                $facultyCourses[] = $newCourseName;
            }
        }

        $newSectionName = trim($_POST['new_section_name'] ?? '');
        if (!empty($newSectionName)) {
            $sectionIds[] = getSectionIdForCourse($pdo, $courseName, $newSectionName);
        }

        $sectionIds = array_unique(array_filter($sectionIds, function($id) {
            return $id > 0;
        }));

        if (!empty($sectionIds)) {
            $stmtEnroll = $pdo->prepare("INSERT INTO `Enrollment` (student_id, section_id, enrollment_date, status) VALUES (:sid, :secid, CURDATE(), 'Active')");
            $stmtCheckEnroll = $pdo->prepare("SELECT COUNT(*) FROM `Enrollment` WHERE student_id = :sid AND section_id = :secid");
            foreach ($sectionIds as $section_id) {
                $stmtCheckEnroll->execute([':sid' => $new_student_id, ':secid' => $section_id]);
                if ($stmtCheckEnroll->fetchColumn() == 0) {
                    $stmtEnroll->execute([
                        ':sid' => $new_student_id,
                        ':secid' => $section_id
                    ]);
                }
            }
        }

        $pdo->commit();
        $message = "Student successfully enrolled!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Enrollment failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Enroll Student</title>
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
        <div class="enroll-layout-wrapper">
            
            <div class="top-actions card" style="justify-content: flex-start;">
                <div class="title-group">
                    <a href="dashboard.php" class="home-icon-link">
                        <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </a>
                    <h1 class="page-title">Enroll Student</h1>
                </div>
            </div>

            <form action="" method="post" class="card form-card">
                
                <?php if ($message): ?>
                    <p style="color: <?php echo strpos($message, 'failed') !== false ? 'red' : 'green'; ?>; font-weight: bold; text-align: center; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <h2 class="section-title">Personal Information</h2>
                <div class="personal-info-grid">
                    
                    <div class="form-column">
                        <div class="inline-form-group">
                            <label for="lastName">Last Name:</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                        <div class="inline-form-group">
                            <label for="firstName">First Name:</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                        <div class="inline-form-group">
                            <label for="middleName">Middle Name:</label>
                            <input type="text" id="middleName" name="middleName">
                        </div>
                    </div>

                    <div class="form-column">
                        <div class="inline-form-group">
                            <label for="birthdate">Birthdate:</label>
                            <input type="date" id="birthdate" name="birthdate">
                        </div>
                        <div class="inline-form-group">
                            <label for="sex">Sex:</label>
                            <select id="sex" name="sex" style="flex-grow: 1; padding: 8px 12px; border: 2px solid var(--primary-accent); border-radius: 4px; font-size: 1rem; color: var(--primary-accent); background-color: var(--white); transition: box-shadow 0.2s, border-color 0.2s; outline: none;">
                                <option value="">Select Sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                </div>

                <h2 class="section-title" style="margin-top: 45px;">Admissions</h2>
                <div class="admissions-grid">
                    <div class="inline-form-group">
                        <label for="studentId">Student ID (optional):</label>
                        <input type="text" id="studentId" name="studentId" placeholder="Enter existing Student ID if already assigned">
                    </div>
                    <div class="inline-form-group">
                        <label for="college">College:</label>
                        <select id="college" name="college" style="flex-grow: 1; padding: 8px 12px; border: 2px solid var(--primary-accent); border-radius: 4px; font-size: 1rem; color: var(--primary-accent); background-color: var(--white); transition: box-shadow 0.2s, border-color 0.2s; outline: none;">
                            <option value="">Select College</option>
                            <?php foreach ($collegeOptions as $collegeOption): ?>
                                <option value="<?php echo htmlspecialchars($collegeOption); ?>"><?php echo htmlspecialchars($collegeOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="inline-form-group">
                        <label for="yearLevel">Year Level:</label>
                        <input type="number" id="yearLevel" name="yearLevel" value="1">
                    </div>
                    <div class="inline-form-group">
                        <label for="course_id">Enroll Course:</label>
                        <select id="course_id" name="course_id" style="flex-grow: 1; padding: 8px 12px; border: 2px solid var(--primary-accent); border-radius: 4px; font-size: 1rem; color: var(--primary-accent); background-color: var(--white); transition: box-shadow 0.2s, border-color 0.2s; outline: none;">
                            <option value="">Select a course</option>
                            <?php foreach ($facultyCourses as $courseName): ?>
                                <option value="<?php echo htmlspecialchars($courseName); ?>"><?php echo htmlspecialchars($courseName); ?></option>
                            <?php endforeach; ?>
                            <option value="__add_new_course__">Add New Course...</option>
                        </select>
                    </div>
                    <div class="inline-form-group" id="new-course-container">
                        <label for="new_course_name">New Course Name</label>
                        <input type="text" id="new_course_name" name="new_course_name" placeholder="Enter new course name">
                    </div>
                    <div class="inline-form-group section-group">
                        <label for="course_id">Additional Courses:</label>
                        <div class="course-content">
                            <div id="course-rows" class="course-rows">
                                <div class="course-row">
                                    <select name="course_ids[]" class="course-select">
                                        <option value="">Select a course</option>
                                        <?php foreach ($facultyCourses as $courseName): ?>
                                            <option value="<?php echo htmlspecialchars($courseName); ?>"><?php echo htmlspecialchars($courseName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="course-actions">
                                <button type="button" id="add-course-button" class="btn-solid course-add-button">Add Another Course</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-submit-container">
                    <button type="submit" class="btn-solid btn-large">Enroll Student</button>
                </div>

            </form>
        </div>
    </main>

    <footer></footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const courseSelect = document.getElementById('course_id');
            const newCourseContainer = document.getElementById('new-course-container');
            const sectionRows = document.getElementById('section-rows');
            const addSectionButton = document.getElementById('add-section-button');
            const newSectionContainer = document.getElementById('new-section-container');
            const newSectionName = document.getElementById('new_section_name');

            const sectionOptions = [
                { value: '', label: 'Select a section' },
                <?php foreach ($sections as $sec): ?>
                    { value: '<?php echo $sec['section_id']; ?>', label: '<?php echo htmlspecialchars(($sec['course_name'] ?? 'Course') . ' - ' . ($sec['section_name'] ?? 'Section'), ENT_QUOTES); ?>' },
                <?php endforeach; ?>
                { value: '__add_new_section__', label: 'Add New Section...' }
            ];

            function createSectionSelect(value = '') {
                const wrapper = document.createElement('div');
                wrapper.className = 'section-row';

                const select = document.createElement('select');
                select.name = 'section_ids[]';
                select.style.flexGrow = '1';
                select.style.padding = '8px 12px';
                select.style.border = '2px solid var(--primary-accent)';
                select.style.borderRadius = '4px';
                select.style.fontSize = '1rem';
                select.style.color = 'var(--primary-accent)';
                select.style.backgroundColor = 'var(--white)';
                select.style.outline = 'none';

                sectionOptions.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.value;
                    option.textContent = opt.label;
                    if (opt.value === value) option.selected = true;
                    select.appendChild(option);
                });

                select.addEventListener('change', function() {
                    updateNewSectionVisibility();
                    updateSectionControls();
                });

                wrapper.appendChild(select);

                if (sectionRows.children.length > 0) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = 'Remove';
                    removeBtn.className = 'btn-solid';
                    removeBtn.style.padding = '10px 18px';
                    removeBtn.addEventListener('click', () => {
                        wrapper.remove();
                        updateSectionControls();
                    });
                    wrapper.appendChild(removeBtn);
                }

                return wrapper;
            }

            function updateNewCourseVisibility() {
                if (!courseSelect || !newCourseContainer) return;
                newCourseContainer.style.display = courseSelect.value === '__add_new_course__' ? 'block' : 'none';
            }

            function updateNewSectionVisibility() {
                const hasNewSection = Array.from(sectionRows.querySelectorAll('select')).some(select => select.value === '__add_new_section__');
                newSectionContainer.style.display = hasNewSection ? 'block' : 'none';
                if (!hasNewSection) {
                    newSectionName.value = '';
                }
            }

            function updateSectionControls() {
                const selectedAny = Array.from(sectionRows.querySelectorAll('select')).some(select => select.value !== '');
                if (addSectionButton) {
                    addSectionButton.style.display = selectedAny ? 'inline-flex' : 'none';
                }
            }

            addSectionButton?.addEventListener('click', function() {
                sectionRows.appendChild(createSectionSelect());
                updateSectionControls();
            });

            sectionRows.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    updateNewSectionVisibility();
                    updateSectionControls();
                });
            });

            courseSelect?.addEventListener('change', updateNewCourseVisibility);
            updateNewCourseVisibility();
            updateNewSectionVisibility();
            updateSectionControls();
        });
    </script>
</body>
</html>