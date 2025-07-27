<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: chat.php");
    }
    exit;
}

$errorMsg = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usernameOrEmail = trim($_POST['username']);
    $password        = $_POST['password'];
    $sql  = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {

            $roleCheck = $conn->prepare("SELECT role_name FROM roles WHERE role_name = ?");
            $roleCheck->bind_param("s", $row['role']);
            $roleCheck->execute();
            $roleResult = $roleCheck->get_result();

            if ($roleResult && $roleResult->num_rows === 1) {
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role']     = $row['role'];

                if ($row['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: chat.php");
                }
                exit;
            } else {
                $errorMsg = "Invalid role. Contact the administrator.";
            }
        } else {
            $errorMsg = "Incorrect password!";
        }
    } else {
        $errorMsg = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/login.css" />
    <title>Login - Deppol Messenger</title>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h2>Deppol Messenger</h2>
            <?php if (!empty($errorMsg)): ?>
                <p style="color: red; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($errorMsg); ?>
                </p>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username or Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">Login</button>
            </form>
            <div class="footer-link">
                <p><a href="forgot_password.php">Forgotten password?</a></p>
                <p><a href="index.php">← Back to Home Page</a></p>
            </div>
        </div>
    </div>
</body>

</html>
