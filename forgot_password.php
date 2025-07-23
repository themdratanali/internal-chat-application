<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        $code = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_expires'] = time() + 900;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'yourgmail@gmail.com';
            $mail->Password   = 'your_app_password';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('yourgmail@gmail.com', 'Chat App');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Code';
            $mail->Body    = "Your password reset code is: $code";

            $mail->send();
            header("Location: verify_code.php");
            exit;
        } catch (Exception $e) {
            $error = "Failed to send email.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" />
    <link rel="stylesheet" href="assets/css/forgot_password.css" />
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h2>Forgot Password</h2>
            <?php if (!empty($error)) echo "<p style='color:red; text-align:center;font-size: 14px;margin-bottom: 20px;'>$error</p>"; ?>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="Enter your email" required />
                <button type="submit">Send Reset Code</button>
            </form>

            <div class="footer-link">
                <p><a href="login.php">← Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
