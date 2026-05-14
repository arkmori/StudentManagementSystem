<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

// Create tasks table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS Tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_date DATE NOT NULL,
    task_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Login(user_id)
)");

// Create highlighted dates table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS HighlightedDates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    highlight_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Login(user_id),
    UNIQUE KEY unique_user_date (user_id, highlight_date)
)");

// Create events table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS Events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_date DATE NOT NULL,
    event_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Login(user_id)
)");

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_task') {
            $task_date = $_POST['task_date'];
            $task_description = $_POST['task_description'];
            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO Tasks (user_id, task_date, task_description) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $task_date, $task_description]);
        } elseif ($_POST['action'] == 'edit_task') {
            $task_id = $_POST['task_id'];
            $task_date = $_POST['task_date'];
            $task_description = $_POST['task_description'];
            $stmt = $pdo->prepare("UPDATE Tasks SET task_date = ?, task_description = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_date, $task_description, $task_id, $_SESSION['user_id']]);
        } elseif ($_POST['action'] == 'delete_task') {
            $task_id = $_POST['task_id'];
            $stmt = $pdo->prepare("DELETE FROM Tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $_SESSION['user_id']]);
        } elseif ($_POST['action'] == 'add_highlight') {
            $highlight_date = $_POST['highlight_date'];
            $stmt = $pdo->prepare("INSERT IGNORE INTO HighlightedDates (user_id, highlight_date) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $highlight_date]);
        } elseif ($_POST['action'] == 'remove_highlight') {
            $highlight_date = $_POST['highlight_date'];
            $stmt = $pdo->prepare("DELETE FROM HighlightedDates WHERE user_id = ? AND highlight_date = ?");
            $stmt->execute([$_SESSION['user_id'], $highlight_date]);
        } elseif ($_POST['action'] == 'clear_all_highlights') {
            $stmt = $pdo->prepare("DELETE FROM HighlightedDates WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } elseif ($_POST['action'] == 'add_event') {
            $event_date = $_POST['event_date'];
            $event_description = $_POST['event_description'];
            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO Events (user_id, event_date, event_description) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $event_date, $event_description]);
        } elseif ($_POST['action'] == 'edit_event') {
            $event_id = $_POST['event_id'];
            $event_date = $_POST['event_date'];
            $event_description = $_POST['event_description'];
            $stmt = $pdo->prepare("UPDATE Events SET event_date = ?, event_description = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$event_date, $event_description, $event_id, $_SESSION['user_id']]);
        } elseif ($_POST['action'] == 'delete_event') {
            $event_id = $_POST['event_id'];
            $stmt = $pdo->prepare("DELETE FROM Events WHERE id = ? AND user_id = ?");
            $stmt->execute([$event_id, $_SESSION['user_id']]);
        }
    }
    header("Location: calendar.php");
    exit();
}

// Get all tasks for the user
$tasksStmt = $pdo->prepare("SELECT * FROM Tasks WHERE user_id = ? ORDER BY task_date");
$tasksStmt->execute([$_SESSION['user_id']]);
$tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all highlighted dates for the user
$highlightsStmt = $pdo->prepare("SELECT highlight_date FROM HighlightedDates WHERE user_id = ?");
$highlightsStmt->execute([$_SESSION['user_id']]);
$highlightedDates = $highlightsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get all events for the user
$eventsStmt = $pdo->prepare("SELECT * FROM Events WHERE user_id = ? ORDER BY event_date");
$eventsStmt->execute([$_SESSION['user_id']]);
$events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Calendar</title>
    <link rel="stylesheet" href="style/styles.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-content form {
            display: flex;
            flex-direction: column;
        }
        .modal-content label {
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--primary-accent);
        }
        .modal-content input[type="date"] {
            padding: 10px;
            border: 2px solid var(--primary-accent);
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .modal-content textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            border: 2px solid var(--primary-accent);
            border-radius: 5px;
            resize: none;
            font-family: inherit;
        }
        .modal-content button {
            margin-top: 20px;
            align-self: center;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover {
            color: black;
            cursor: pointer;
        }
        .cal-cell.selected-date {
            background-color: #4caf50 !important;
            color: white;
        }
        .cal-cell.has-task {
            background-color: #4caf50;
            color: white;
            border: 2px solid #4caf50;
        }
        .cal-cell.highlighted {
            background-color: #4caf50;
            color: white;
        }
        .cal-cell.has-event {
            background-color: #4caf50;
            color: white;
            border: 2px solid #4caf50;
        }
        .reminder-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .event-badge {
            background-color: var(--primary-accent);
            color: white !important;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            max-width: 100%;
            word-wrap: break-word;
        }
        .reminder-actions {
            display: flex;
            gap: 10px;
        }
        .edit-btn, .delete-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
        }
        .edit-btn svg, .delete-btn svg {
            width: 20px;
            height: 20px;
        }
        .edit-btn svg path {
            fill: var(--primary-accent);
        }
        .delete-btn svg path {
            fill: var(--primary-dark);
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
        <div class="calendar-layout-wrapper">
            
            <div class="top-actions card">
                <div class="title-group">
                    <a href="dashboard.php" class="home-icon-link">
                        <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </a>
                    <h1 class="page-title">Calendar</h1>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-solid" onclick="openTaskModal()">Set Task</button>
                    <button class="btn-solid" onclick="openHighlightModal()">Highlight Date</button>
                    <button class="btn-solid" onclick="openEventModal()">Add Event</button>
                </div>
            </div>

            <div class="card calendar-main-card">
                
                <div class="calendar-left">
                    
                    <div class="calendar-controls">
                        <select class="cal-dropdown" id="month-select">
                            <option value="0">January</option>
                            <option value="1">February</option>
                            <option value="2">March</option>
                            <option value="3">April</option>
                            <option value="4">May</option>
                            <option value="5">June</option>
                            <option value="6">July</option>
                            <option value="7">August</option>
                            <option value="8" selected>September</option>
                            <option value="9">October</option>
                            <option value="10">November</option>
                            <option value="11">December</option>
                        </select>
                        <select class="cal-dropdown" id="year-select">
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                        </select>
                    </div>

                    <div class="calendar-grid" id="calendar-grid">
                        <div class="cal-header">Sunday</div>
                        <div class="cal-header">Monday</div>
                        <div class="cal-header">Tuesday</div>
                        <div class="cal-header">Wednesday</div>
                        <div class="cal-header">Thursday</div>
                        <div class="cal-header">Friday</div>
                        <div class="cal-header">Saturday</div>
                        
                        </div>
                </div>

                <div class="calendar-right">
                    <div class="reminders-container">
                        <div class="reminders-pill">Reminders</div>
                        <div class="reminders-content">
                            <?php
                            foreach ($tasks as $task) {
                                echo '<div class="reminder-item" data-task-id="' . $task['id'] . '">';
                                echo '<div class="reminder-text">' . htmlspecialchars($task['task_description']) . ' - ' . htmlspecialchars($task['task_date']) . '</div>';
                                echo '<div class="reminder-actions">';
                                echo '<button class="edit-btn" onclick="editTask(' . $task['id'] . ')"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>';
                                echo '<button class="delete-btn" onclick="deleteTask(' . $task['id'] . ')"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>';
                                echo '</div>';
                                echo '</div>';
                            }
                            foreach ($events as $event) {
                                echo '<div class="reminder-item event-item" data-event-id="' . $event['id'] . '">';
                                echo '<div class="event-badge">' . htmlspecialchars($event['event_description']) . ' - ' . htmlspecialchars($event['event_date']) . '</div>';
                                echo '<div class="reminder-actions">';
                                echo '<button class="edit-btn" onclick="editEvent(' . $event['id'] . ')"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>';
                                echo '<button class="delete-btn" onclick="deleteEvent(' . $event['id'] . ')"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>';
                                echo '</div>';
                                echo '</div>';
                            }
                            if (empty($tasks) && empty($events)) {
                                echo '<p>No tasks or events scheduled.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Task Modal -->
    <div id="task-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modal-title">Set Task</h2>
            <form id="task-form" method="POST">
                <input type="hidden" name="action" id="task-action" value="add_task">
                <input type="hidden" name="task_id" id="task-id">
                <label for="task-date">Date:</label>
                <input type="date" id="task-date" name="task_date" required>
                <label for="task-description">Task:</label>
                <textarea id="task-description" name="task_description" required></textarea>
                <button type="submit" class="btn-solid">Save Task</button>
            </form>
        </div>
    </div>

    <!-- Highlight Modal -->
    <div id="highlight-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeHighlightModal()">&times;</span>
            <h2>Highlight Date</h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <form method="POST">
                    <input type="hidden" name="action" value="add_highlight">
                    <label for="highlight-date">Date:</label>
                    <input type="date" id="highlight-date" name="highlight_date" required>
                    <button type="submit" class="btn-solid" style="margin-top: 10px;">Highlight Date</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_all_highlights">
                    <button type="submit" class="btn-solid" style="background-color: var(--primary-accent);">Clear All Highlights</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Modal -->
    <div id="event-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEventModal()">&times;</span>
            <h2 id="event-modal-title">Add Event</h2>
            <form id="event-form" method="POST">
                <input type="hidden" name="action" id="event-action" value="add_event">
                <input type="hidden" name="event_id" id="event-id">
                <label for="event-date">Date:</label>
                <input type="date" id="event-date" name="event_date" required>
                <label for="event-description">Event:</label>
                <textarea id="event-description" name="event_description" required></textarea>
                <button type="submit" class="btn-solid">Save Event</button>
            </form>
        </div>
    </div>

    <footer></footer>

    <script>
        const tasks = <?php echo json_encode($tasks); ?>;
        const highlightedDates = <?php echo json_encode($highlightedDates); ?>;
        const events = <?php echo json_encode($events); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const monthSelect = document.getElementById('month-select');
            const yearSelect = document.getElementById('year-select');
            const calendarGrid = document.getElementById('calendar-grid');

            // Load saved values from localStorage
            const savedMonth = localStorage.getItem('calendarMonth');
            const savedYear = localStorage.getItem('calendarYear');
            if (savedMonth !== null) monthSelect.value = savedMonth;
            if (savedYear !== null) yearSelect.value = savedYear;

            function generateCalendar(month, year) {
                const existingDays = calendarGrid.querySelectorAll('.cal-cell');
                existingDays.forEach(day => day.remove());

                const firstDay = new Date(year, month, 1).getDay(); 
                const daysInMonth = new Date(year, month + 1, 0).getDate();

                for (let i = 0; i < firstDay; i++) {
                    const emptyCell = document.createElement('div');
                    emptyCell.className = 'cal-cell empty';
                    calendarGrid.appendChild(emptyCell);
                }

                for (let i = 1; i <= daysInMonth; i++) {
                    const dayCell = document.createElement('div');
                    dayCell.className = 'cal-cell';
                    dayCell.textContent = i;
                    
                    // Check if there's a task on this date
                    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                    const hasTask = tasks.some(task => task.task_date === dateStr);
                    const isHighlighted = highlightedDates.includes(dateStr);
                    const hasEvent = events.some(event => event.event_date === dateStr);
                    if (hasTask) {
                        dayCell.classList.add('has-task');
                    } else if (hasEvent) {
                        dayCell.classList.add('has-event');
                    } else if (isHighlighted) {
                        dayCell.classList.add('highlighted');
                    }
                    
                    dayCell.addEventListener('click', function() {
                        toggleHighlight(dateStr);
                    });
                    
                    calendarGrid.appendChild(dayCell);
                }

                const totalCells = firstDay + daysInMonth;
                const remainingCells = (7 - (totalCells % 7)) % 7;
                for (let i = 0; i < remainingCells; i++) {
                    const emptyCell = document.createElement('div');
                    emptyCell.className = 'cal-cell empty';
                    calendarGrid.appendChild(emptyCell);
                }
            }

            generateCalendar(parseInt(monthSelect.value), parseInt(yearSelect.value));

            monthSelect.addEventListener('change', function() {
                localStorage.setItem('calendarMonth', monthSelect.value);
                generateCalendar(parseInt(monthSelect.value), parseInt(yearSelect.value));
            });
            
            yearSelect.addEventListener('change', function() {
                localStorage.setItem('calendarYear', yearSelect.value);
                generateCalendar(parseInt(monthSelect.value), parseInt(yearSelect.value));
            });
        });

        function openTaskModal() {
            document.getElementById('task-modal').style.display = 'block';
            document.getElementById('modal-title').textContent = 'Set Task';
            document.getElementById('task-action').value = 'add_task';
            document.getElementById('task-id').value = '';
            document.getElementById('task-date').value = '';
            document.getElementById('task-description').value = '';
            document.getElementById('task-date').addEventListener('change', highlightSelectedDate);
        }

        function editTask(taskId) {
            const task = tasks.find(t => t.id == taskId);
            if (task) {
                document.getElementById('task-modal').style.display = 'block';
                document.getElementById('modal-title').textContent = 'Edit Task';
                document.getElementById('task-action').value = 'edit_task';
                document.getElementById('task-id').value = task.id;
                document.getElementById('task-date').value = task.task_date;
                document.getElementById('task-description').value = task.task_description;
                document.getElementById('task-date').addEventListener('change', highlightSelectedDate);
                highlightSelectedDate(); // Highlight current date
            }
        }

        function deleteTask(taskId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_task">
                <input type="hidden" name="task_id" value="${taskId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function highlightSelectedDate() {
            // Clear previous selection
            const selectedCells = document.querySelectorAll('.cal-cell.selected-date');
            selectedCells.forEach(cell => cell.classList.remove('selected-date'));

            const dateValue = document.getElementById('task-date').value;
            if (dateValue) {
                const selectedDate = new Date(dateValue);
                const currentMonth = parseInt(document.getElementById('month-select').value);
                const currentYear = parseInt(document.getElementById('year-select').value);

                if (selectedDate.getMonth() === currentMonth && selectedDate.getFullYear() === currentYear) {
                    const day = selectedDate.getDate();
                    const dayCells = document.querySelectorAll('.cal-cell');
                    dayCells.forEach(cell => {
                        if (parseInt(cell.textContent) === day && !cell.classList.contains('empty')) {
                            cell.classList.add('selected-date');
                        }
                    });
                }
            }
        }

        function openHighlightModal() {
            document.getElementById('highlight-modal').style.display = 'block';
        }

        function closeHighlightModal() {
            document.getElementById('highlight-modal').style.display = 'none';
        }

        function toggleHighlight(dateStr) {
            const isHighlighted = highlightedDates.includes(dateStr);
            const action = isHighlighted ? 'remove_highlight' : 'add_highlight';
            
            fetch('calendar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&highlight_date=${dateStr}`
            })
            .then(response => response.text())
            .then(() => {
                // Reload the page to update the calendar
                location.reload();
            });
        }

        function openEventModal() {
            document.getElementById('event-modal').style.display = 'block';
            document.getElementById('event-modal-title').textContent = 'Add Event';
            document.getElementById('event-action').value = 'add_event';
            document.getElementById('event-id').value = '';
            document.getElementById('event-date').value = '';
            document.getElementById('event-description').value = '';
            document.getElementById('event-date').addEventListener('change', highlightSelectedDate);
        }

        function editEvent(eventId) {
            const event = events.find(e => e.id == eventId);
            if (event) {
                document.getElementById('event-modal').style.display = 'block';
                document.getElementById('event-modal-title').textContent = 'Edit Event';
                document.getElementById('event-action').value = 'edit_event';
                document.getElementById('event-id').value = event.id;
                document.getElementById('event-date').value = event.event_date;
                document.getElementById('event-description').value = event.event_description;
                document.getElementById('event-date').addEventListener('change', highlightSelectedDate);
                highlightSelectedDate(); // Highlight current date
            }
        }

        function deleteEvent(eventId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_event">
                <input type="hidden" name="event_id" value="${eventId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function closeEventModal() {
            document.getElementById('event-modal').style.display = 'none';
            // Clear selection
            const selectedCells = document.querySelectorAll('.cal-cell.selected-date');
            selectedCells.forEach(cell => cell.classList.remove('selected-date'));
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const taskModal = document.getElementById('task-modal');
            const highlightModal = document.getElementById('highlight-modal');
            const eventModal = document.getElementById('event-modal');
            if (event.target == taskModal) {
                taskModal.style.display = 'none';
                // Clear selection
                const selectedCells = document.querySelectorAll('.cal-cell.selected-date');
                selectedCells.forEach(cell => cell.classList.remove('selected-date'));
            }
            if (event.target == highlightModal) {
                highlightModal.style.display = 'none';
            }
            if (event.target == eventModal) {
                eventModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>