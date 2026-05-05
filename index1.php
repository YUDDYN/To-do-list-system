<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user  = $_SESSION['id_user'];
$username = $_SESSION['username'];

// Tugas hari ini
$hari_ini = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM daily_activities WHERE user_id = ? AND activity_date = ? AND status = 'pending' ORDER BY priority DESC");
$stmt->bind_param('is', $id_user, $hari_ini);
$stmt->execute();
$tugas_hari_ini = $stmt->get_result();

// Semua tugas pending
$stmt2 = $conn->prepare("SELECT * FROM daily_activities WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC");
$stmt2->bind_param('i', $id_user);
$stmt2->execute();
$semua_tugas = $stmt2->get_result();

// Progress tugas
$stmt3 = $conn->prepare("SELECT * FROM daily_activities WHERE user_id = ? ORDER BY status DESC, created_at DESC LIMIT 4");
$stmt3->bind_param('i', $id_user);
$stmt3->execute();
$progress_tugas = $stmt3->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - To Do List</title>
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/dashboard.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">📝</div>
    <div class="logo-text">My<br>Plants</div>
  </div>

  <a href="crud/index.php" class="nav-item"><span class="icon">🗓</span> My Plan</a>
  <a href="index.php"   class="nav-item active"><span class="icon">🏠</span> Beranda</a>
  <a href="#"           class="nav-item"><span class="icon">👤</span> Profil</a>
  <a href="#"           class="nav-item"><span class="icon">📅</span> Kalender</a>
  <a href="project.php" class="nav-item"><span class="icon">📁</span> My Project</a>
  <a href="history.php" class="nav-item"><span class="icon">✅</span> Selesai</a>
  <a href="#"           class="nav-item"><span class="icon">⚙️</span> Pengaturan</a>

  <div class="sidebar-bottom">
    <a href="../auth/logout.php" class="nav-item logout"><span class="icon">🚪</span> Logout</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">

  <!-- CONTENT -->
  <div class="content">

    <!-- TOPBAR -->
    <div class="topbar">
      <h1>Dashboard</h1>
      <div class="topbar-right">
        <div class="search-box">🔍 Cari...</div>
        <div class="notif-btn">🔔</div>
      </div>
    </div>

    <a href="tambah.php" class="btn-tambah">+ Tambah Tugas</a>

    <!-- TUGAS HARI INI -->
    <div class="section">
      <div class="section-header">
        <div class="section-title">Hari ini</div>
        <a href="#" class="link-red">Lihat semua</a>
      </div>

      <?php if ($tugas_hari_ini->num_rows === 0): ?>
        <div class="kosong">Tidak ada tugas untuk hari ini 🎉</div>
      <?php else: ?>
        <table class="tugas-table">
          <tr>
            <th>Nama Kegiatan</th>
            <th>Keterangan</th>
            <th>Priority</th>
            <th>Aksi</th>
          </tr>
          <?php while ($tugas = $tugas_hari_ini->fetch_assoc()): ?>
          <tr>
            <td class="tugas-name"><?= htmlspecialchars($tugas['title']) ?></td>
            <td class="tugas-desc"><?= htmlspecialchars($tugas['description']) ?></td>
            <td><span class="badge badge-<?= $tugas['priority'] ?>"><?= ucfirst($tugas['priority']) ?></span></td>
            <td>
              <a href="tambah.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn">Edit</a>
              <a href="update_status.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn">Done</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </table>
      <?php endif; ?>
    </div>

    <!-- SEMUA TUGAS -->
    <div class="section">
      <div class="section-header">
        <div class="section-title">Tugas</div>
        <a href="history.php" class="link-red">Lihat semua</a>
      </div>

      <?php if ($semua_tugas->num_rows === 0): ?>
        <div class="kosong">Belum ada tugas. <a href="tambah.php" class="link-red">Tambah sekarang</a></div>
      <?php else: ?>
        <table class="tugas-table">
          <tr>
            <th>Nama Tugas</th>
            <th>Label</th>
            <th>Deadline</th>
            <th>Priority</th>
            <th>Aksi</th>
          </tr>
          <?php while ($tugas = $semua_tugas->fetch_assoc()): ?>
          <tr>
            <td class="tugas-name"><?= htmlspecialchars($tugas['title']) ?></td>
            <td class="tugas-desc"><?= $tugas['label'] ?? '-' ?></td>
            <td class="tugas-desc"><?= $tugas['deadline'] ?? '-' ?></td>
            <td><span class="badge badge-<?= $tugas['priority'] ?>"><?= ucfirst($tugas['priority']) ?></span></td>
            <td>
              <a href="tambah.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn">Edit</a>
              <a href="update_status.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn">Done</a>
              <a href="hapus.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn hapus"
                 onclick="return confirm('Yakin hapus?')">Hapus</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </table>
      <?php endif; ?>
    </div>

  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">

    <!-- Profile -->
    <div class="profile-card">
      <div class="profile-avatar">👤</div>
      <div class="profile-name"><?= htmlspecialchars($username) ?></div>
      <div class="profile-loc">📍 Indonesia</div>
    </div>

    <!-- Kalender -->
    <div class="calendar">
      <div class="cal-header">
        <div class="cal-title" id="calTitle"></div>
        <div class="cal-nav">
          <button onclick="prevMonth()">‹</button>
          <button onclick="nextMonth()">›</button>
        </div>
      </div>
      <div class="cal-grid" id="calGrid"></div>
    </div>

    <!-- Progress -->
    <div class="progress-section">
      <div class="progress-header">
        <div class="progress-title">Progres tugas</div>
        <a href="history.php" class="link-red">Lihat semua</a>
      </div>

      <?php if ($progress_tugas->num_rows === 0): ?>
        <div class="kosong">Belum ada tugas.</div>
      <?php else: ?>
        <?php while ($pt = $progress_tugas->fetch_assoc()):
          $p = $pt['status'] === 'done' ? 100 : 20;
          $r = 18;
          $circumference = 2 * 3.14159 * $r;
          $offset = $circumference - ($p / 100) * $circumference;
          $color  = $pt['status'] === 'done' ? '#e63946' : '#444';
        ?>
        <a href="tambah.php?id=<?= $pt['id_daily_activity'] ?>" class="progress-item">
          <div class="progress-circle">
            <svg width="44" height="44" viewBox="0 0 44 44">
              <circle cx="22" cy="22" r="<?= $r ?>" fill="none" stroke="#333" stroke-width="3"/>
              <circle cx="22" cy="22" r="<?= $r ?>" fill="none" stroke="<?= $color ?>" stroke-width="3"
                stroke-dasharray="<?= $circumference ?>"
                stroke-dashoffset="<?= $offset ?>"
                stroke-linecap="round"/>
            </svg>
            <div class="pct"><?= $p ?>%</div>
          </div>
          <div class="progress-info">
            <div class="progress-name"><?= htmlspecialchars($pt['title']) ?></div>
            <div class="progress-sub"><?= $pt['status'] === 'done' ? 'Selesai' : 'Belum selesai' ?></div>
          </div>
          <div class="progress-arrow">›</div>
        </a>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
  let now   = new Date();
  let year  = now.getFullYear();
  let month = now.getMonth();
  const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

  function renderCalendar() {
    document.getElementById('calTitle').textContent = bulan[month] + ' ' + year;
    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';
    ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(d => {
      grid.innerHTML += `<div class="cal-day-name">${d}</div>`;
    });
    const firstDay  = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();
    const today     = new Date();
    for (let i = 0; i < firstDay; i++) {
      grid.innerHTML += `<div class="cal-day empty"></div>`;
    }
    for (let d = 1; d <= totalDays; d++) {
      const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
      grid.innerHTML += `<div class="cal-day ${isToday ? 'today' : ''}">${d}</div>`;
    }
  }

  function prevMonth() { if (--month < 0)  { month = 11; year--; } renderCalendar(); }
  function nextMonth() { if (++month > 11) { month = 0;  year++; } renderCalendar(); }

  renderCalendar();
</script>

</body>
</html>
