<?php
session_start();
require_once '../config.php';

// Pastikan user sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Halaman ini khusus untuk EDIT. Jika tidak ada ID, kembalikan ke index.
if (!$id) {
    header('Location: index.php');
    exit();
}

// ── Ambil data tugas ────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM daily_activities WHERE id_daily_activity = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $id_user);
$stmt->execute();
$tugas = $stmt->get_result()->fetch_assoc();

// Jika tugas tidak ditemukan / bukan milik user
if (!$tugas) {
    header('Location: index.php');
    exit();
}

// ── Ambil data project untuk sidebar (agar sama dengan index) ───────────────
$stmt_projects = $conn->prepare("SELECT id_project, nama_project, warna FROM projects WHERE user_id = ? ORDER BY id_project DESC");
$stmt_projects->bind_param('i', $id_user);
$stmt_projects->execute();
$sidebar_projects = $stmt_projects->get_result();

// ── Proses Update Data ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $priority      = $_POST['priority'] ?? 'medium';
    $activity_date = !empty($_POST['activity_date']) ? $_POST['activity_date'] : null;
    $deadline      = !empty($_POST['deadline'])      ? $_POST['deadline']      : null;
    $label         = ($_POST['label'] ?? '') === 'lainnya'
                   ? trim($_POST['label_custom'] ?? '')
                   : ($_POST['label'] ?? 'personal');

    // ── Handle upload file baru ──
    $file_attachment    = null;
    $file_original_name = null;
    $has_new_file       = isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK;

    if ($has_new_file) {
        $allowed_types = [
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg','image/png','image/gif','image/webp',
            'application/zip','text/plain'
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['task_file']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed_types, true)) {
            $upload_dir = '../uploads/tasks/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext      = strtolower(pathinfo($_FILES['task_file']['name'], PATHINFO_EXTENSION));
            $baseName = pathinfo($_FILES['task_file']['name'], PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
            $filename = 'task_' . time() . '_' . $safeName . '.' . $ext;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['task_file']['tmp_name'], $filepath)) {
                $file_attachment    = $filename;
                $file_original_name = $_FILES['task_file']['name'];
            }
        }
    }

    if ($has_new_file && $file_attachment !== null) {
        // Update termasuk mengganti file baru
        $stmt = $conn->prepare(
            "UPDATE daily_activities
             SET title=?, description=?, priority=?, activity_date=?, deadline=?, label=?, file_attachment=?, file_original_name=?
             WHERE id_daily_activity=? AND user_id=?"
        );
        $stmt->bind_param('ssssssssii', $title, $description, $priority, $activity_date, $deadline, $label, $file_attachment, $file_original_name, $id, $id_user);
    } else {
        // Update tanpa mengubah file lama
        $stmt = $conn->prepare(
            "UPDATE daily_activities
             SET title=?, description=?, priority=?, activity_date=?, deadline=?, label=?
             WHERE id_daily_activity=? AND user_id=?"
        );
        $stmt->bind_param('ssssssii', $title, $description, $priority, $activity_date, $deadline, $label, $id, $id_user);
    }

    $stmt->execute();
    header('Location: index.php');
    exit();
}

// Helper untuk emoji ikon file
function fileIconEmoji(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match($ext) {
        'pdf'                     => '📕',
        'doc','docx'              => '📝',
        'jpg','jpeg','png','gif',
        'webp'                    => '🖼️',
        'zip','rar'               => '📦',
        'txt'                     => '📄',
        'xls','xlsx','csv'        => '📊',
        default                   => '📁',
    };
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Tugas — Grow Plant</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"/>
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/dashboard.css">
  <link rel="stylesheet" href="../design/+tugas.css">
  <link rel="stylesheet" href="../design/edit.css">
  <script>(function(){const t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
  
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <img src="../asset/logo.jpg" alt="Logo" width="42" height="42" style="border-radius:20px;">
    </div>
    <div class="logo-text">Grow<br>Plant</div>
  </div>

  <a href="index.php" class="nav-item active">
    <span class="material-symbols-outlined icon">home</span> Kembali ke Home
  </a>

  <div class="project-nav-wrap">
    <div class="nav-item project-nav">
      <span class="icon">🗁</span>
      <span class="project-nav-label">My Project</span>
    </div>

    <div class="project-dropdown">
      <?php if ($sidebar_projects->num_rows === 0): ?>
        <div class="project-empty-title" style="padding:0 20px;">Belum ada project</div>
      <?php else: ?>
        <?php while ($p = $sidebar_projects->fetch_assoc()): ?>
          <a class="project-item" href="project.php">
            <span class="project-dot" style="background:<?= htmlspecialchars($p['warna'] ?? '#e63946') ?>;"></span>
            <span class="project-name"><?= htmlspecialchars($p['nama_project']) ?></span>
          </a>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>
</aside>

<div class="main">
  <div class="content" style="padding-top: 20px;">
    
    <div class="edit-wrapper">
      <div class="edit-header">
        <a href="index.php" class="btn-back"><span class="material-symbols-outlined">arrow_back</span></a>
        <div class="edit-title">
          <h1>Edit Tugas</h1>
          <p>Perbarui rincian, status prioritas, atau file lampiran.</p>
        </div>
      </div>

      <div class="edit-card">
        <form method="POST" enctype="multipart/form-data" id="editForm">
          
          <div class="form-section-title">Info Utama</div>
          <div class="form-group">
            <label class="form-label">Judul Tugas</label>
            <input type="text" name="title" class="form-input" placeholder="Contoh: Kerjakan Bab 3" value="<?= htmlspecialchars($tugas['title']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-textarea" placeholder="Catatan atau instruksi tugas..."><?= htmlspecialchars($tugas['description']) ?></textarea>
          </div>

          <div class="form-section-title">🏷️ Detail</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Label / Kategori</label>
              <div class="select-wrap">
                <select name="label" class="form-select" onchange="cekLabel(this.value)">
                  <option value="personal" <?= $tugas['label'] === 'personal' ? 'selected' : '' ?>>Personal</option>
                  <option value="sekolah"   <?= $tugas['label'] === 'sekolah'   ? 'selected' : '' ?>>Sekolah</option>
                  <option value="kerja"    <?= $tugas['label'] === 'kerja'    ? 'selected' : '' ?>>Kerja</option>
                  <option value="lainnya"  <?= !in_array($tugas['label'], ['personal','sekolah','kerja']) ? 'selected' : '' ?>>Lainnya</option>
                </select>
                <span class="select-arrow">▼</span>
              </div>
              <div id="label_custom" style="display: <?= !in_array($tugas['label'], ['personal','sekolah','kerja']) ? 'block' : 'none' ?>; margin-top: 10px;">
                <input type="text" name="label_custom" class="form-input" placeholder="Tulis nama label..." value="<?= !in_array($tugas['label'], ['personal','kerja']) ? htmlspecialchars($tugas['label']) : '' ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Priority</label>
              <div class="select-wrap">
                <select name="priority" class="form-select">
                  <option value="low"    <?= $tugas['priority'] === 'low'    ? 'selected' : '' ?>>Low</option>
                  <option value="medium" <?= $tugas['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                  <option value="high"   <?= $tugas['priority'] === 'high'   ? 'selected' : '' ?>>High</option>
                </select>
                <span class="select-arrow">▼</span>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date" name="activity_date" class="form-input" value="<?= $tugas['activity_date'] ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Deadline</label>
              <input type="date" name="deadline" class="form-input" value="<?= $tugas['deadline'] ?>">
            </div>
          </div>

          <div class="form-section-title">📎 Lampiran</div>
          <?php if (!empty($tugas['file_attachment'])): ?>
            <a class="file-chip" href="../uploads/tasks/<?= htmlspecialchars($tugas['file_attachment']) ?>" target="_blank">
              <span><?= fileIconEmoji($tugas['file_original_name'] ?? $tugas['file_attachment']) ?></span>
              <span><?= htmlspecialchars($tugas['file_original_name'] ?? $tugas['file_attachment']) ?></span>
              <span class="material-symbols-outlined" style="font-size: 16px; color:#6bcb77; margin-left:auto;">open_in_new</span>
            </a>
          <?php endif; ?>

          <div class="upload-zone">
            <input type="file" name="task_file" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.zip,.txt" onchange="previewFileName(this)">
            <div class="upload-msg">
              <span class="material-symbols-outlined" style="font-size:28px; color:#555; display:block; margin-bottom:8px;">cloud_upload</span>
              Tarik file ke sini, atau <span>Pilih File</span> baru
            </div>
            <div id="filePreviewName"></div>
          </div>
          <p style="font-size:0.75rem; color:#666; margin-top:8px;">(Abaikan bagian ini jika tidak ingin mengubah file lampiran lama)</p>

          <div class="form-actions">
            <button type="submit" class="btn-simpan" id="btnSubmit">Simpan Perubahan</button>
            <a href="index.php" class="btn-batal">Batal</a>
          </div>

        </form>
      </div>

    </div>
  </div>
</div>

<script src="edit.js"></script>

</body>
</html>