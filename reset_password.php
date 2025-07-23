<?php
session_start();
require 'db.php';

$error = $success = "";

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
    $stmt->bind_param("ss", $hashed, $email);

    if ($stmt->execute()) {
        $success = "Password updated. You can now <a href='login.php'>login</a>.";
        session_destroy();
    } else {
        $error = "Failed to update password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Reset Password</title></head>
<body>
    <h2>Set New Password</h2>
    <?php
    if ($error) echo "<p style='color:red;'>$error</p>";
    if ($success) echo "<p style='color:green;'>$success</p>";
    ?>
    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required><br>
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
