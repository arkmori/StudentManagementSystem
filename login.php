<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'faculty') {
        header("Location: dashboard.php");
        exit();
    }
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'connection.php';

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']) ? true : false;

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("
            SELECT l.*, r.role_name 
            FROM `Login` l 
            LEFT JOIN `Role` r ON l.role_id = r.role_id 
            WHERE l.user_name = :username
        ");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = strtolower($user['role_name']);
            
            // Set "Remember Me" cookies if checkbox is checked
            if ($remember_me) {
                $token = hash('sha256', $user['user_name'] . $user['user_id']);
                setcookie('remember_user_id', $user['user_id'], time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Login</title>
    
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
            
            <h2 class="login-title">Log In</h2>
            
            <hr>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="username">User Name</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                    <input type="checkbox" id="show-password" onclick="togglePassword()" style="width: auto; margin: 0; cursor: pointer; accent-color: #BC5F04;">
                    <label for="show-password" style="margin: 0; font-size: 0.9rem; cursor: pointer; color: #BC5F04;">Show Password</label>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                    <input type="checkbox" id="remember_me" name="remember_me" style="width: auto; margin: 0; cursor: pointer; accent-color: #BC5F04;">
                    <label for="remember_me" style="margin: 0; font-size: 0.9rem; cursor: pointer; color: #BC5F04;">Remember Me</label>
                </div>
                
                <button type="submit" class="btn btn-primary">Log In</button>
                
                <div class="secondary-actions">
                    <button type="button" class="btn btn-secondary">Forget Password</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='register.php'">Sign Up</button>
                </div>
            </form>
            
            <hr>

        </div>
    </main>

    <footer></footer>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }
    </script>
</body>
</html>