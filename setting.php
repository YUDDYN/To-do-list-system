<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

  session_start();
  require_once '../config.php';

  if (!isset($_SESSION['id_user'])) {
      header('Location: ../auth/login.php');
      exit();
  }

  $username = $_SESSION['username'] ?? 'User';
  ?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/dashboard.css">
  <link rel="stylesheet" href="../design/settings-panel.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <title>Pengaturan - To Do List</title>
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <img src="../asset/logo.jpg" alt="Logo" width="42" height="42">
      </div>
      <div class="logo-text">My<br>Plants</div>
    </div>
    <a href="tambah.php" class="nav-item"><span class="icon">+</span> Tambah Tugas</a>
    <a href="crud/index.php" class="nav-item">
      <span class="material-symbols-outlined icon">calendar_today</span> My Plan
    </a>
    <a href="../crud/index.php" class="nav-item">
      <span class="material-symbols-outlined icon">calendar_today</span> Today
    </a>
    <a href="#" class="nav-item"><span class="material-symbols-outlined icon">calendar_month</span> Kalender</a>
    <a href="../crud/project.php" class="nav-item"><span class="icon">🗁</span> My Project</a>
    <a href="history.php" class="nav-item"><span class="icon">✔</span> Selesai</a>
    <a href="#" class="nav-item active" id="settingTrigger" onclick="toggleSetting(event)"><span class="icon">☰</span> Pengaturan</a>

    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="nav-item logout"><span class="icon">➜]</span> Logout</a>
    </div>
  </aside>

  <?php include_once 'includes/setting_panel.php'; ?>

  <div class="main">
    <div class="content">
      <div class="topbar">
        <h1>Pengaturan</h1>
      </div>
      <div class="section">
        <div class="kosong">Klik <b>Pengaturan</b> di sidebar untuk membuka panel.</div>
      </div>
    </div>
  </div>
</body>
</html>