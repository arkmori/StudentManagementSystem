<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$search = $_GET['search'] ?? '';
$view = $_GET['view'] ?? 'all';
$sort = $_GET['sort'] ?? 'student_id';
$dir = $_GET['dir'] ?? 'asc';

$allowedSorts = [
    'student_id' => 's.student_id',
    'name' => 'l.last_name, l.first_name',
    'college' => 'c.college_name',
    'enrolled' => 'enrolled_count'
];

$filter = '';
if (strpos($view, 'sort:') === 0) {
    $sort = substr($view, 5);
} elseif (strpos($view, 'college:') === 0) {
    $filter = substr($view, 8);
} elseif ($view === 'all') {
    $filter = '';
} elseif ($view !== '') {
    $filter = $view;
}

if (!isset($allowedSorts[$sort])) {
    $sort = 'student_id';
}
if ($dir !== 'asc' && $dir !== 'desc') {
    $dir = 'asc';
}

$faculty_id = null;
if ($_SESSION['role'] === 'faculty') {
    $stmtFac = $pdo->prepare("SELECT faculty_id FROM `Faculty` WHERE user_id = ?");
    $stmtFac->execute([$_SESSION['user_id']]);
    $fac = $stmtFac->fetch();
    $faculty_id = $fac ? $fac['faculty_id'] : null;
}

function sortLink($column, $label) {
    global $sort, $dir;
    $newDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
    $params = $_GET;
    $params['sort'] = $column;
    $params['dir'] = $newDir;
    $arrow = '';
    if ($sort === $column) {
        $arrow = $dir === 'asc' ? ' ▲' : ' ▼';
    }
    return '<a href="studentlist.php?' . http_build_query($params) . '" style="color: inherit; text-decoration: none;">' . htmlspecialchars($label . $arrow) . '</a>';
}

$countSubquery = "(SELECT COUNT(*) FROM `Enrollment` e";
if ($faculty_id) {
    $countSubquery .= " JOIN `Section` sec2 ON e.section_id = sec2.section_id";
}
$countSubquery .= " WHERE e.student_id = s.student_id";
if ($faculty_id) {
    $countSubquery .= " AND sec2.faculty_id = :fid";
}
$countSubquery .= ")";

$query = "
    SELECT s.student_id, l.first_name, l.last_name, c.college_name,
           " . $countSubquery . " as enrolled_count
    FROM `Student` s
    JOIN `Login` l ON s.user_id = l.user_id
    LEFT JOIN `College` c ON s.college_id = c.college_id
    WHERE 1=1
";
$params = [];
if ($faculty_id) {
    $query .= " AND EXISTS (
        SELECT 1 FROM `Enrollment` e2
        JOIN `Section` sec2 ON e2.section_id = sec2.section_id
        WHERE e2.student_id = s.student_id AND sec2.faculty_id = :fid
    )";
    $params[':fid'] = $faculty_id;
}

if (!empty($search)) {
    $query .= " AND (l.first_name LIKE :search OR l.last_name LIKE :search OR s.student_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filter)) {
    $query .= " AND c.college_name = :filter";
    $params[':filter'] = $filter;
}

$query .= " ORDER BY " . $allowedSorts[$sort] . " " . $dir;

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
                    
                    <select name="view" class="btn-solid" style="background-color: #fff; color: var(--primary-accent) !important; border: 2px solid var(--primary-accent); cursor: pointer;">
                        <option value="all" <?php if($view == 'all') echo 'selected'; ?>>All Colleges</option>
                        <?php foreach($colleges as $col): ?>
                            <option value="college:<?php echo htmlspecialchars($col); ?>" <?php if($view == 'college:' . $col) echo 'selected'; ?>><?php echo htmlspecialchars($col); ?></option>
                        <?php endforeach; ?>
                        <option value="sort:student_id" <?php if($view == 'sort:student_id') echo 'selected'; ?>>Sort by ID</option>
                        <option value="sort:name" <?php if($view == 'sort:name') echo 'selected'; ?>>Sort by Name</option>
                        <option value="sort:college" <?php if($view == 'sort:college') echo 'selected'; ?>>Sort by College</option>
                        <option value="sort:enrolled" <?php if($view == 'sort:enrolled') echo 'selected'; ?>>Sort by Enrolled</option>
                    </select>

                    <select name="dir" class="btn-solid" style="background-color: #fff; color: var(--primary-accent) !important; border: 2px solid var(--primary-accent); cursor: pointer;">
                        <option value="asc" <?php if($dir == 'asc') echo 'selected'; ?>>Ascending</option>
                        <option value="desc" <?php if($dir == 'desc') echo 'selected'; ?>>Descending</option>
                    </select>
                    
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID or Name" style="padding: 8px 12px; border: 2px solid var(--primary-accent); border-radius: 20px; outline: none; color: var(--primary-accent);">
                    <button type="submit" class="btn-solid">Search / Filter</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo sortLink('student_id', 'Student ID'); ?></th>
                            <th><?php echo sortLink('name', 'Student Name'); ?></th>
                            <th><?php echo sortLink('college', 'College'); ?></th>
                            <th><?php echo sortLink('enrolled', 'Courses Enrolled'); ?></th>
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