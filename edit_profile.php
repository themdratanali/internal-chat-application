<?php
session_start();
include 'db.php';

$userId = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];

    if ($_FILES['photo']['name']) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo = $target_dir . basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $photo);

        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $username, $email, $photo, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $username, $email, $userId);
    }
    $stmt->execute();
    echo "<script>alert('Profile updated successfully'); window.location.href='chat.php';</script>";
}

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/css/edit_profile.css">
</head>
<body>

<form method="POST" enctype="multipart/form-data">
    <h2>Edit Profile</h2>
    
    <?php if (!empty($user['photo'])): ?>
        <img src="<?= htmlspecialchars($user['photo']) ?>" alt="Current Photo">
    <?php endif; ?>

    <label>Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>

    <label>Username:</label>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label>Upload New Photo:</label>
    <input type="file" name="photo">

    <button type="submit">Update Profile</button>
</form>

</body>
</html>
