<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ceo') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "internal_chat");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_GET['id']);

$user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
if (!$user) {
    echo "User not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'];
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    $photoPath = $user['photo'];
    if (!empty($_FILES['photo']['name'])) {
        $photoName = basename($_FILES['photo']['name']);
        $targetDir = "uploads/";
        $targetFile = $targetDir . $photoName;
        move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile);
        $photoPath = $targetFile;
    }

    $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, password=?, role=?, photo=? WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $username, $email, $password, $role, $photoPath, $id);

    if ($stmt->execute()) {
        echo "<script>alert('User updated successfully'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "Update failed: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Update User</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>

<body>
    <div class="dashboard-wrapper">
        <h2>Update User (ID: <?php echo $user['id']; ?>)</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Name:</label><br>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br>

            <label>Username:</label><br>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br>

            <label>Email:</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>

            <label>New Password:</label><br>
            <input type="password" name="password" placeholder="Enter new password" required><br>

            <label>Role:</label><br>
            <select name="role" required>
                <option value="user" <?php if ($user['role'] === 'user') echo 'selected'; ?>>User</option>
                <option value="ceo" <?php if ($user['role'] === 'ceo') echo 'selected'; ?>>CEO</option>
                <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
            </select><br><br>

            <label>Photo:</label><br>
            <input type="file" name="photo"><br>
            <?php if ($user['photo']): ?>
                <img src="<?php echo $user['photo']; ?>" alt="User Photo" width="100" style="margin-top:10px;"><br>
            <?php endif; ?>

            <br><button type="submit">Update User</button>
        </form>
        <br><a href="admin_dashboard.php">← Back to Dashboard</a>
    </div>
</body>

</html>