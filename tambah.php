<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;
$tugas   = null;
$mode    = $id ? 'edit' : 'tambah';

// Kalau mode edit, ambil data tugas
if ($mode === 'edit') {
    $stmt = $conn->prepare("SELECT * FROM daily_activities WHERE id_daily_activity = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $id_user);
    $stmt->execute();
    $tugas = $stmt->get_result()->fetch_assoc();

    if (!$tugas) {
        header('Location: index.php');
        exit();
    }
}

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = $conn->real_escape_string($_POST['title']);
    $description   = $conn->real_escape_string($_POST['description']);
    $priority      = $conn->real_escape_string($_POST['priority']);
    $activity_date = !empty($_POST['activity_date']) ? $_POST['activity_date'] : null;
    $deadline      = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $label         = $_POST['label'] === 'lainnya'
                  ? $conn->real_escape_string($_POST['label_custom'])
                  : $conn->real_escape_string($_POST['label']);

    if ($mode === 'tambah') {
        $stmt = $conn->prepare("INSERT INTO daily_activities (user_id, title, description, priority, activity_date, deadline, label) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssss', $id_user, $title, $description, $priority, $activity_date, $deadline, $label);
    } else {
        $stmt = $conn->prepare("UPDATE daily_activities SET title=?, description=?, priority=?, activity_date=?, deadline=?, label=? WHERE id_daily_activity=? AND user_id=?");
        $stmt->bind_param('ssssssii', $title, $description, $priority, $activity_date, $deadline, $label, $id, $id_user);
    }

    $stmt->execute();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <title><?= $mode === 'edit' ? 'Edit' : 'Tambah' ?> Tugas</title>
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/dashboard.css">
  <link rel="stylesheet" href="../design/+tugas.css">
</head>
<body>

<div class="dashboard-wrapper">
  
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <img src="../asset/logo.jpg" alt="Logo" width="42" height="42" style="border-radius: 20px;">
      </div>
      <div class="logo-text">My<br>Plants</div>
    </div>
    
    <div class="nav-links">
        <a href="tambah.php" class="nav-item active"><span class="icon material-symbols-outlined">add</span> Tambah Tugas</a>
        <a href="index.php" class="nav-item"><span class="material-symbols-outlined icon">calendar_today</span> My Plan</a>
        <a href="../crud/index.php" class="nav-item"><span class="material-symbols-outlined icon">calendar_today</span> Today</a>
        <a href="#" class="nav-item"><span class="material-symbols-outlined icon">person</span> Profil</a>
        <a href="#" class="nav-item"><span class="material-symbols-outlined icon">calendar_month</span> Kalender</a>
        <!-- <a href="../crud/project.php" class="nav-item"><span class="icon material-symbols-outlined">folder</span> My Project</a> -->
        <a href="history.php" class="nav-item"><span class="icon material-symbols-outlined">check</span> Selesai</a>
        <a href="history.php" class="nav-item"><span class="icon material-symbols-outlined">settings</span> Pengaturan</a>
    </div>

    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="nav-item logout"><span class="icon material-symbols-outlined">logout</span> Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <div class="form-header">
        <h1><?= $mode === 'edit' ? 'Edit' : 'Tambah' ?> Tugas</h1>
    </div>

    <div class="form-card">
      <form method="POST">

        <div class="form-group">
          <label>Judul Tugas</label>
          <input type="text" name="title" placeholder="Contoh: Kerjakan tugas matematika"
                value="<?= $tugas ? htmlspecialchars($tugas['title']) : '' ?>" required>
        </div>

        <div class="form-group">
          <label>Deskripsi</label>
          <textarea name="description" placeholder="Tambahkan keterangan..."><?= $tugas ? htmlspecialchars($tugas['description']) : '' ?></textarea>
        </div>

        <div class="form-grid">

          <div class="form-group">
            <label>Label</label>
            <select name="label" id="label" onchange="cekLabel(this.value)">
              <option value="personal" <?= $tugas && $tugas['label'] === 'personal' ? 'selected' : '' ?>>Personal</option>
              <option value="kuliah"   <?= $tugas && $tugas['label'] === 'kuliah'   ? 'selected' : '' ?>>Kuliah</option>
              <option value="kerja"    <?= $tugas && $tugas['label'] === 'kerja'    ? 'selected' : '' ?>>Kerja</option>
              <option value="belanja"  <?= $tugas && $tugas['label'] === 'belanja'  ? 'selected' : '' ?>>Belanja</option>
              <option value="lainnya"  <?= $tugas && $tugas['label'] === 'lainnya'  ? 'selected' : '' ?>>Lainnya</option>
            </select>
            <div class="label-custom" id="label_custom">
              <input type="text" name="label_custom" placeholder="Tulis label kamu..."
                    value="<?= $tugas ? htmlspecialchars($tugas['label']) : '' ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Priority</label>
            <select name="priority">
              <option value="low"    <?= $tugas && $tugas['priority'] === 'low'    ? 'selected' : '' ?>>Low</option>
              <option value="medium" <?= $tugas && $tugas['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
              <option value="high"   <?= $tugas && $tugas['priority'] === 'high'   ? 'selected' : '' ?>>High</option>
            </select>
          </div>
  
          <div class="form-group">
            <label>Tanggal Mulai</label>
            <input type="date" name="activity_date"
                  value="<?= $tugas ? $tugas['activity_date'] : date('Y-m-d') ?>">
          </div>

          <div class="form-group">
            <label>Deadline</label>
            <input type="date" name="deadline"
                  value="<?= $tugas ? $tugas['deadline'] : '' ?>">
          </div>

        </div>

        <div class="form-actions">
          <button type="submit" class="btn-simpan">
            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Tugas' ?>
          </button>
          <a href="index.php" class="btn-batal">Batal</a>
        </div>

      </form>
    </div>
  </main>
</div>

<script>
function cekLabel(val) {
  document.getElementById('label_custom').style.display = val === 'lainnya' ? 'block' : 'none';
}

// Cek saat halaman load
const pilihanDefault = ['personal', 'kuliah', 'kerja', 'belanja', 'lainnya'];
const labelSekarang  = "<?= $tugas ? $tugas['label'] : '' ?>";
if (labelSekarang && !pilihanDefault.includes(labelSekarang)) {
  document.getElementById('label').value = 'lainnya';
  document.getElementById('label_custom').style.display = 'block';
}
</script>

</body>
</html>