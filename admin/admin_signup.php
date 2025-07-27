<?php
require '../db.php';

$successMsg = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $passwordInput = $_POST['password'];

    if (strlen($passwordInput) < 5) {
        $errorMsg = "Password must be at least 5 characters long.";
    } else {
        $password = password_hash($passwordInput, PASSWORD_DEFAULT);
        $photo = 'default-photo.jpg';
        $role = 'admin';

        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errorMsg = "Username or email already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role, photo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $username, $email, $password, $role, $photo);
            if ($stmt->execute()) {
                $successMsg = "Admin account created successfully!";
                header("refresh:2;url=../login.php");
            } else {
                $errorMsg = "Failed to create account. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Signup</title>
    <link rel="stylesheet" href="../assets/css/login.css" />
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h2>Create Admin Account</h2>
            <?php if ($successMsg): ?>
                <p style="color: green;"><?= htmlspecialchars($successMsg) ?></p>
            <?php elseif ($errorMsg): ?>
                <p style="color: red;"><?= htmlspecialchars($errorMsg) ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="text" name="name" placeholder="Full Name" required />
                <input type="text" name="username" placeholder="Username" required />
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">Register as Admin</button>
            </form>

            <div class="footer-link">
                <p><a href="../login.php">← Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
