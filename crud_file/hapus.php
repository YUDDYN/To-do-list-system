<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$id      = (int) $_GET['id'];

$stmt = $conn->prepare("DELETE FROM daily_activities WHERE id_daily_activity = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $id_user);
$stmt->execute();

header('Location: index.php');
exit();