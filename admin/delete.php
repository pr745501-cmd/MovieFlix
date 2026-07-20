<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

include '../config.php';

$id = (int)$_GET['id'];

// Use prepared statement for security
$stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: index.php?deleted=1');
} else {
    header('Location: index.php?error=delete_failed');
}
exit();
