<?php
session_start();
require 'db.php';

$sender = $_SESSION['user_id'];
$receiver = $_POST['receiver_id'];
$message = $_POST['message'];

$sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $sender, $receiver, $message);
$stmt->execute();
?>
