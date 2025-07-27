<?php
session_start();
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    if (isset($_SESSION['reset_code']) && $_SESSION['reset_expires'] > time()) {
        if ($code == $_SESSION['reset_code']) {
            header("Location: reset_password.php");
            exit;
        } else {
            $error = "Invalid code.";
        }
    } else {
        $error = "Code expired.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Verify Code</title>
</head>

<body>
    <h2>Enter Verification Code</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="code" placeholder="Enter code" required><br>
        <button type="submit">Verify</button>
    </form>
</body>

</html>