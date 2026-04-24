<?php
session_start();
require_once 'connection.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $reenter_password = $_POST['reenter-password'] ?? '';
    $first_name = $_POST['first-name'] ?? '';
    $middle_name = $_POST['middle-name'] ?? '';
    $last_name = $_POST['last-name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth-date'] ?? '';

    if ($password !== $reenter_password) {
        $error_message = "Passwords do not match.";
    } else {
        try {
            $stmtCheck = $pdo->prepare("SELECT user_id FROM `Login` WHERE user_name = :username");
            $stmtCheck->execute([':username' => $username]);
            
            if ($stmtCheck->rowCount() > 0) {
                $error_message = "Username already exists.";
            } else {
                $pdo->beginTransaction();

                $stmtRole = $pdo->prepare("SELECT role_id FROM `Role` WHERE role_name = :role_name");
                $stmtRole->execute([':role_name' => $role]);
                $roleRow = $stmtRole->fetch();
                
                if ($roleRow) {
                    $role_id = $roleRow['role_id'];
                } else {
                    $stmtInsertRole = $pdo->prepare("INSERT INTO `Role` (role_name) VALUES (:role_name)");
                    $stmtInsertRole->execute([':role_name' => $role]);
                    $role_id = $pdo->lastInsertId();
                }

                $stmtContact = $pdo->prepare("INSERT INTO `Contact` (email) VALUES (:email)");
                $stmtContact->execute([':email' => $email]);
                $contact_id = $pdo->lastInsertId();

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmtUser = $pdo->prepare("
                    INSERT INTO `Login` 
                    (user_name, password, first_name, middle_name, last_name, gender, date_of_birth, contact_id, role_id) 
                    VALUES 
                    (:user_name, :password, :first_name, :middle_name, :last_name, :gender, :date_of_birth, :contact_id, :role_id)
                ");
                
                $stmtUser->execute([
                    ':user_name' => $username,
                    ':password' => $hashed_password,
                    ':first_name' => $first_name,
                    ':middle_name' => $middle_name,
                    ':last_name' => $last_name,
                    ':gender' => $gender,
                    ':date_of_birth' => $birth_date,
                    ':contact_id' => $contact_id,
                    ':role_id' => $role_id
                ]);

                $user_id = $pdo->lastInsertId();

                if ($role === 'faculty') {
                    $stmtFaculty = $pdo->prepare("INSERT INTO `Faculty` (user_id) VALUES (:user_id)");
                    $stmtFaculty->execute([':user_id' => $user_id]);
                } elseif ($role === 'student') {
                    $stmtStudent = $pdo->prepare("INSERT INTO `Student` (user_id) VALUES (:user_id)");
                    $stmtStudent->execute([':user_id' => $user_id]);
                }

                $pdo->commit();
                
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Register</title>
    <link rel="stylesheet" href="style/styles.css">
    <style>
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body class="layout-login">

    <header>
        <h1>STUDENT MANAGEMENT SYSTEM</h1>
    </header>

    <main>
        <div class="login-container">
            
            <div class="profile-pic-placeholder"></div>
            <h2 class="login-title">Register</h2>
            <hr>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST" id="registrationForm">
                
                <div id="step1">
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required style="width: 100%; padding: 12px 20px; border: 3px solid #BC5F04; border-radius: 30px; font-size: 1rem; color: #BC5F04; background-color: #FFFFFF; outline: none; box-sizing: border-box; cursor: pointer;">
                            <option value="" disabled selected>Select Role</option>
                            <option value="faculty">Faculty</option>
                            <option value="admin">Admin</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="username">User Name</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reenter-password">Reenter Password</label>
                        <input type="password" id="reenter-password" name="reenter-password" required>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; width: 100%; margin-top: 20px;">
                        <button type="button" class="btn" style="padding: 10px 40px;" onclick="goToStep2()">Next</button>
                    </div>
                </div>

                <div id="step2" style="display: none;">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="middle-name">Middle Name</label>
                        <input type="text" id="middle-name" name="middle-name">
                    </div>
                    
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" required>
                    </div>
                    
                    <div class="form-group-split">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required style="width: 100%; padding: 12px 20px; border: 3px solid #BC5F04; border-radius: 30px; font-size: 1rem; color: #BC5F04; background-color: #FFFFFF; outline: none; box-sizing: border-box; cursor: pointer;">
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="prefer-not-to-say">I'd rather not say</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="birth-date">Birth Date</label>
                            <input type="date" id="birth-date" name="birth-date" required>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; width: 100%; margin-top: 20px;">
                        <button type="submit" class="btn" style="padding: 10px 40px;">Register</button>
                    </div>
                </div>
                
            </form>

            <hr>

        </div>
    </main>

    <footer></footer>

    <script>
        function goToStep2() {
            const step1Inputs = document.querySelectorAll('#step1 input[required], #step1 select[required]');
            let allValid = true;

            step1Inputs.forEach(input => {
                if (!input.checkValidity()) {
                    allValid = false;
                    input.reportValidity(); 
                }
            });

            if (allValid) {
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
            }
        }
    </script>
</body>
</html>