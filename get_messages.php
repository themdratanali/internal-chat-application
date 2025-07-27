<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id'])) {
    exit('Unauthorized access');
}

$sender_id = $_SESSION['user_id'];
$receiver_id = intval($_GET['receiver_id']);

$sql = "SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $class = ($row['sender_id'] == $sender_id) ? 'sent' : 'received';
    $message = htmlspecialchars($row['message']);

    $time = date('h:i A', strtotime($row['created_at']));

    echo '<div class="message ' . $class . '">';
    echo    '<div class="message-text">' . $message . '</div>';
    echo    '<div class="message-time">' . $time . '</div>';
    echo '</div>';
}
