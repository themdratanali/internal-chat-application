<?php
session_start();
include 'db.php';

$search = $_GET['query'] ?? '';
$currentUser = $_SESSION['user_id'];
$searchTerm = "%" . $search . "%";

$stmt = $conn->prepare("SELECT id, name, username, photo 
                        FROM users 
                        WHERE (name COLLATE utf8mb4_general_ci LIKE ? OR username COLLATE utf8mb4_general_ci LIKE ?) 
                        AND id != ?");
$stmt->bind_param("ssi", $searchTerm, $searchTerm, $currentUser);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode($users);
?>
