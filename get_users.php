<?php
session_start();
include 'db.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

$query = "
    SELECT u.id, u.name, u.username, u.photo, MAX(m.timestamp) AS last_message_time
    FROM messages m
    INNER JOIN users u ON u.id = m.receiver_id
    WHERE m.sender_id = ?
    GROUP BY u.id
    ORDER BY last_message_time DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

while ($user = $result->fetch_assoc()) {
    $photo = $user['photo'] ?: 'default-photo.jpg';
    echo '
        <div class="user-entry" onclick="selectUser(' . $user['id'] . ', \'' . addslashes($user['name']) . '\', \'' . $photo . '\')" style="display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
            <img src="' . $photo . '" style="width:30px; height:30px; border-radius:50%; margin-right:10px;">
            <span>' . htmlspecialchars($user['name']) . '</span>
        </div>
    ';
}
?>
