<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: project.php');
    exit();
}

$nama_project = trim((string)($_POST['nama_project'] ?? ''));
$workspace = trim((string)($_POST['workspace'] ?? ''));
$warna = trim((string)($_POST['warna'] ?? ''));

if ($nama_project === '') {
    $errors[] = 'Nama project wajib diisi.';
}

if ($warna === '') {
    $warna = '#e63946';
}

if (!preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $warna)) {
    $errors[] = 'Warna tidak valid.';
}

if (!$errors) {
    $deskripsi = $workspace !== '' ? 'Workspace: ' . $workspace : '';
    $stmt = $conn->prepare("INSERT INTO projects (user_id, nama_project, deskripsi, warna) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $id_user, $nama_project, $deskripsi, $warna);
    $stmt->execute();
}

header('Location: project.php');
exit();
