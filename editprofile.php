<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';
$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        $stmtUser = $pdo->prepare("UPDATE `Login` SET first_name = :f, middle_name = :m, last_name = :l, gender = :g, date_of_birth = :d WHERE user_id = :uid");
        $stmtUser->execute([':f'=>$_POST['first_name'], ':m'=>$_POST['middle_name'], ':l'=>$_POST['last_name'], ':g'=>$_POST['gender'], ':d'=>$_POST['dob'], ':uid'=>$user_id]);

        $stmtExt = $pdo->prepare("INSERT INTO `ProfileExt` (user_id, civil_status, employed_since, position) VALUES (:uid, :s, :e, :p) ON DUPLICATE KEY UPDATE civil_status = :s, employed_since = :e, position = :p");
        $stmtExt->execute([':uid'=>$user_id, ':s'=>$_POST['status'], ':e'=>$_POST['employed_since'], ':p'=>$_POST['position']]);

        $stmtFac = $pdo->prepare("UPDATE `Faculty` SET college_id = :cid WHERE user_id = :uid");
        $stmtFac->execute([':cid' => $_POST['college_id'], ':uid' => $user_id]);

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

$stmt = $pdo->prepare("SELECT l.*, pe.civil_status, pe.employed_since, pe.position, f.college_id FROM `Login` l LEFT JOIN `ProfileExt` pe ON l.user_id = pe.user_id LEFT JOIN `Faculty` f ON l.user_id = f.user_id WHERE l.user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch();

$colleges = $pdo->query("SELECT * FROM `College`")->fetchAll();

// Define a reusable inline style for dropdowns to ensure consistency
$dropdownStyle = "width: 100%; padding: 12px 20px; border: 3px solid #BC5F04; border-radius: 30px; font-size: 1rem; color: #BC5F04; background-color: #FFFFFF; outline: none; box-sizing: border-box; cursor: pointer; appearance: none; -webkit-appearance: none;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style/styles.css">
    <title>Edit Profile</title>
    <style>
        /* Extra fix for date inputs which can sometimes be stubborn */
        input[type="date"] {
            color: #BC5F04;
        }
    </style>
</head>
<body class="layout-dashboard">
    <header>
        </header>
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

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Update Profile</button>
                    <button type="button" class="btn" style="flex: 1; padding: 14px 20px;" onclick="window.location.href='profile.php'">Cancel</button>
                </div>
            </form>
        </div>
    </main>
    <footer></footer>
</body>
</html>