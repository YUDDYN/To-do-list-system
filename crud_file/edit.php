<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isAjax  = isset($_GET['ajax']) && $_GET['ajax'] === '1';

// ── AJAX GET: kirim data tugas sebagai JSON ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id && $isAjax) {
    $stmt = $conn->prepare("SELECT * FROM daily_activities WHERE id_daily_activity = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $id_user);
    $stmt->execute();
    $tugas = $stmt->get_result()->fetch_assoc();

    header('Content-Type: application/json');
    if ($tugas) {
        echo json_encode($tugas);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Tidak ditemukan']);
    }
    exit();
}

// ── POST: simpan (edit atau tambah) ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode          = $_POST['mode'] ?? 'tambah'; // 'edit' atau 'tambah'
    $id_post       = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $title         = $conn->real_escape_string($_POST['title']);
    $description   = $conn->real_escape_string($_POST['description']);
    $priority      = $conn->real_escape_string($_POST['priority']);
    $activity_date = $conn->real_escape_string($_POST['activity_date']);
    $deadline      = $conn->real_escape_string($_POST['deadline']);
    $label         = isset($_POST['label']) && $_POST['label'] === 'lainnya'
                   ? $conn->real_escape_string($_POST['label_custom'] ?? '')
                   : $conn->real_escape_string($_POST['label'] ?? '');

    if ($mode === 'tambah') {
        $stmt = $conn->prepare("INSERT INTO daily_activities (user_id, title, description, priority, activity_date, deadline, label) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssss', $id_user, $title, $description, $priority, $activity_date, $deadline, $label);
    } else {
        $stmt = $conn->prepare("UPDATE daily_activities SET title=?, description=?, priority=?, activity_date=?, deadline=?, label=? WHERE id_daily_activity=? AND user_id=?");
        $stmt->bind_param('ssssssii', $title, $description, $priority, $activity_date, $deadline, $label, $id_post, $id_user);
    }

    $stmt->execute();

    // Kalau request dari AJAX (modal), cukup return 200 — JS akan reload halaman
    // Kalau request biasa (form langsung), redirect ke index
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['_ajax'])) {
        http_response_code(200);
        echo json_encode(['ok' => true]);
    } else {
        header('Location: index.php');
    }
    exit();
}

// ── Fallback: tampilan form standalone (opsional, kalau masih dipakai) ──────
$tugas = null;
$mode  = $id ? 'edit' : 'tambah';

if ($mode === 'edit') {
    $stmt = $conn->prepare("SELECT * FROM daily_activities WHERE id_daily_activity = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $id_user);
    $stmt->execute();
    $tugas = $stmt->get_result()->fetch_assoc();
    if (!$tugas) { header('Location: index.php'); exit(); }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= $mode === 'edit' ? 'Edit' : 'Tambah' ?> Tugas</title>
</head>
<body>
  <?php include '../includes/navbar.php'; ?>
  <h1><?= $mode === 'edit' ? 'Edit' : 'Tambah' ?> Tugas</h1>
  <form method="POST">
    <input type="hidden" name="mode" value="<?= $mode ?>">
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <label>Judul</label><br>
    <input type="text" name="title" value="<?= $tugas ? htmlspecialchars($tugas['title']) : '' ?>" required><br><br>

    <label>Deskripsi</label><br>
    <textarea name="description" rows="3"><?= $tugas ? htmlspecialchars($tugas['description']) : '' ?></textarea><br><br>

    <label>Label</label><br>
    <select name="label" id="label" onchange="cekLabel(this.value)">
      <option value="personal" <?= $tugas && $tugas['label']==='personal' ? 'selected':'' ?>>Personal</option>
      <option value="kuliah"   <?= $tugas && $tugas['label']==='kuliah'   ? 'selected':'' ?>>Kuliah</option>
      <option value="kerja"    <?= $tugas && $tugas['label']==='kerja'    ? 'selected':'' ?>>Kerja</option>
      <option value="belanja"  <?= $tugas && $tugas['label']==='belanja'  ? 'selected':'' ?>>Belanja</option>
      <option value="lainnya"  ?>Lainnya</option>
    </select><br>
    <div id="label_custom" style="display:none;margin-top:8px">
      <input type="text" name="label_custom" placeholder="Tulis label kamu..."
             value="<?= $tugas ? htmlspecialchars($tugas['label']) : '' ?>">
    </div><br>

    <label>Priority</label><br>
    <select name="priority">
      <option value="low"    <?= $tugas && $tugas['priority']==='low'    ? 'selected':'' ?>>Low</option>
      <option value="medium" <?= $tugas && $tugas['priority']==='medium' ? 'selected':'' ?>>Medium</option>
      <option value="high"   <?= $tugas && $tugas['priority']==='high'   ? 'selected':'' ?>>High</option>
    </select><br><br>

    <label>Tanggal Mulai</label><br>
    <input type="date" name="activity_date" value="<?= $tugas ? $tugas['activity_date'] : '' ?>"><br><br>

    <label>Deadline</label><br>
    <input type="date" name="deadline" value="<?= $tugas ? $tugas['deadline'] : '' ?>"><br><br>

    <button type="submit"><?= $mode === 'edit' ? 'Simpan' : 'Tambah' ?></button>
    <a href="index.php">Batal</a>
  </form>
  <script>
  function cekLabel(val) {
    document.getElementById('label_custom').style.display = val === 'lainnya' ? 'block' : 'none';
  }
  const pilihanDefault = ['personal','kuliah','kerja','belanja','lainnya'];
  const labelSekarang  = "<?= $tugas ? $tugas['label'] : '' ?>";
  if (labelSekarang && !pilihanDefault.includes(labelSekarang)) {
    document.getElementById('label').value = 'lainnya';
    document.getElementById('label_custom').style.display = 'block';
  }
  </script>
</body>
</html>