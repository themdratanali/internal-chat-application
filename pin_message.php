<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    exit('Unauthorized');
}

$message_id = intval($_POST['id']);

$sql = "UPDATE messages SET is_pinned = NOT is_pinned WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $message_id);
$stmt->execute();

echo "Pinned/Unpinned";
?>
