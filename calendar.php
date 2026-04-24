<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Calendar</title>
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
        <div class="calendar-layout-wrapper">
            
            <div class="top-actions card">
                <div class="title-group">
                    <a href="dashboard.php" class="home-icon-link">
                        <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </a>
                    <h1 class="page-title">Calendar</h1>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-solid">Set Task</button>
                    <button class="btn-solid">Highlight Date</button>
                    <button class="btn-solid">Add Event</button>
                    <button class="btn-solid">Search Event</button>
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
                            </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer></footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthSelect = document.getElementById('month-select');
            const yearSelect = document.getElementById('year-select');
            const calendarGrid = document.getElementById('calendar-grid');

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
                generateCalendar(parseInt(monthSelect.value), parseInt(yearSelect.value));
            });
            
            yearSelect.addEventListener('change', function() {
                generateCalendar(parseInt(monthSelect.value), parseInt(yearSelect.value));
            });
        });
    </script>
</body>
</html>