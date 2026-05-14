<?php
session_start();
session_unset();
session_destroy();

// Clear "Remember Me" cookies
setcookie('remember_user_id', '', time() - 3600, '/');
setcookie('remember_token', '', time() - 3600, '/');

header("Location: login.php");
exit();
?>