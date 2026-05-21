<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['ok' => false, 'error' => 'not_authenticated']);
    exit();
}

$id_user = (int) $_SESSION['id_user'];

$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n'); // 1-12

if ($month < 1 || $month > 12) $month = (int) date('n');

$start = sprintf('%04d-%02d-01', $year, $month);
$end   = date('Y-m-t', strtotime($start));

$stmt = $conn->prepare(
        "SELECT id_daily_activity, title, deadline, status FROM daily_activities
         WHERE user_id = ? AND deadline IS NOT NULL AND deadline != '0000-00-00'
             AND status != 'done' AND deadline BETWEEN ? AND ?"
);
$stmt->bind_param('iss', $id_user, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$map = [];
while ($row = $res->fetch_assoc()) {
    $d = $row['deadline'];
    if (!isset($map[$d])) $map[$d] = [];
    $map[$d][] = [
        'id' => (int)$row['id_daily_activity'],
        'title' => $row['title'],
        'status' => $row['status']
    ];
}

echo json_encode(['ok' => true, 'deadlines' => $map]);
