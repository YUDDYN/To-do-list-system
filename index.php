<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user  = (int) $_SESSION['id_user'];
$username = $_SESSION['username'] ?? 'User';

// ─── AKSI: Tambah Tugas dari Panel ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_task_panel') {
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $priority      = $_POST['priority'] ?? 'medium';
    $activity_date = !empty($_POST['activity_date']) ? $_POST['activity_date'] : null;
    $deadline      = !empty($_POST['deadline'])      ? $_POST['deadline']      : null;
    $label_input   = $_POST['label'] ?? 'personal';
    $label_custom  = trim($_POST['label_custom'] ?? '');
    $label         = $label_input === 'lainnya' ? $label_custom : $label_input;

    if ($title !== '' && $label !== '') {
        $stmt_add = $conn->prepare(
            "INSERT INTO daily_activities (user_id, title, description, priority, activity_date, deadline, label)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_add->bind_param('issssss', $id_user, $title, $description, $priority, $activity_date, $deadline, $label);
        $stmt_add->execute();
    }

    header('Location: index.php');
    exit();
}

// ─── FETCH DATA ──────────────────────────────────────────────────────────────
$hari_ini = date('Y-m-d');

// Tugas hari ini (non-project, pending)
$stmt = $conn->prepare(
    "SELECT * FROM daily_activities
     WHERE user_id = ? AND activity_date = ? AND status = 'pending'
       AND (project_id IS NULL OR project_id = 0)
     ORDER BY priority DESC"
);
$stmt->bind_param('is', $id_user, $hari_ini);
$stmt->execute();
$tugas_hari_ini = $stmt->get_result();

// Semua tugas pending (non-project)
$stmt2 = $conn->prepare(
    "SELECT * FROM daily_activities
     WHERE user_id = ? AND status = 'pending'
       AND (project_id IS NULL OR project_id = 0)
     ORDER BY created_at DESC"
);
$stmt2->bind_param('i', $id_user);
$stmt2->execute();
$semua_tugas = $stmt2->get_result();

// Progress tugas (4 terbaru)
$stmt3 = $conn->prepare(
    "SELECT * FROM daily_activities
     WHERE user_id = ? AND (project_id IS NULL OR project_id = 0)
     ORDER BY status DESC, created_at DESC
     LIMIT 4"
);
$stmt3->bind_param('i', $id_user);
$stmt3->execute();
$progress_tugas = $stmt3->get_result();

// Sidebar: daftar project
$stmt_projects = $conn->prepare(
    "SELECT id_project, nama_project, warna FROM projects WHERE user_id = ? ORDER BY id_project DESC"
);
$stmt_projects->bind_param('i', $id_user);
$stmt_projects->execute();
$sidebar_projects = $stmt_projects->get_result();
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Plan - Grow Plant</title>
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/dashboard.css">
  <link rel="stylesheet" href="../design/settings-panel.css">
  <link rel="stylesheet" href="../design/+tugas.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <script>
    /* Terapkan tema sebelum render agar tidak flicker */
    (function() {
      const t = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <style>
    /* ── Progress item: non-navigating card ── */
    .progress-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 8px;
      border-radius: 8px;
      margin-bottom: 4px;
      cursor: default;
      transition: background 0.2s;
    }
    .progress-item:hover { background: var(--bg-hover, #1a1a1a); }

    .progress-circle {
      position: relative;
      flex-shrink: 0;
      width: 44px;
      height: 44px;
    }
    .progress-circle svg { display: block; }
    .pct {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      font-size: 9px;
      font-weight: 700;
      line-height: 1;
    }

    .progress-info { flex: 1; min-width: 0; }
    .progress-name {
      font-size: 12px;
      font-weight: 500;
      color: var(--text-primary, #f0f0f0);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .progress-sub {
      font-size: 11px;
      color: var(--text-muted, #666);
      margin-top: 2px;
    }
    .progress-sub.done { color: #6bcb77; }

    /* Tombol edit kecil di kanan (hanya muncul untuk tugas belum selesai) */
    .progress-edit-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      height: 28px;
      border-radius: 6px;
      background: var(--bg-elevated, #1c1c1c);
      color: var(--text-muted, #666);
      text-decoration: none;
      flex-shrink: 0;
      transition: background 0.2s, color 0.2s;
    }
    .progress-edit-btn:hover {
      background: rgba(107,203,119,0.15);
      color: #6bcb77;
    }

    /* Tanda centang hijau untuk tugas selesai */
    .progress-done-mark {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(107,203,119,0.15);
      color: #6bcb77;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 700;
      flex-shrink: 0;
    }
  </style>
</head>
<body>

<!-- ═══════════════════════════ SIDEBAR ════════════════════════════════════ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <img src="../asset/logo.jpg" alt="Logo" width="42" height="42" style="border-radius: 20px;">
    </div>
    <div class="logo-text">Grow<br>Plant</div>
  </div>

  <a href="#" class="nav-item" onclick="toggleAddTaskPanel(event)">
    <span class="icon">+</span> Tambah Tugas
  </a>

  <div class="nav-item nav-search" style="cursor:default;">
    <span class="material-symbols-outlined icon">search</span>
    <input id="sidebarSearchInput" type="text" placeholder="Cari tugas..."
           autocomplete="off" style="flex:1;background:transparent;border:none;outline:none;color:inherit;font:inherit;">
  </div>

  <!-- My Plan — AKTIF di halaman ini -->
  <a href="index.php" class="nav-item active">
    <span class="material-symbols-outlined icon">home</span> My Plan
  </a>

  <a href="history.php" class="nav-item">
    <span class="icon">✔</span> Selesai
  </a>

  <div class="project-nav-wrap">
    <div class="nav-item project-nav" id="projectNav"
         role="button" tabindex="0" aria-expanded="false"
         onclick="toggleProjectDropdown(event)">
      <span class="icon">🗁</span>
      <span class="project-nav-label">My Project</span>
      <span class="project-nav-actions" aria-hidden="true">
        <button type="button" class="project-action-btn" title="Tambah project"
                onclick="openAddProjectFromSidebar(event)">
          <span class="material-symbols-outlined">add</span>
        </button>
        <button type="button" class="project-action-btn" title="Buka/tutup daftar"
                onclick="toggleProjectDropdown(event)">
          <span class="material-symbols-outlined project-chevron" id="projectChevron">chevron_right</span>
        </button>
      </span>
    </div>

    <div class="project-dropdown" id="projectDropdown" hidden>
      <?php if ($sidebar_projects->num_rows === 0): ?>
        <div class="project-empty-title">Belum ada project</div>
        <div class="project-empty-sub">Buat project pertamamu dan lihat rencanamu tumbuh sedikit demi sedikit</div>
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

  <div class="sidebar-bottom">
    <a href="#" class="nav-item" onclick="toggleSetting(event)">
      <span class="icon">☰</span> Pengaturan
    </a>
  </div>
</aside>

<!-- ═══════════════ INCLUDE PANELS ═══════════════════════════════════════ -->
<?php include_once '../includes/setting_panel.php'; ?>

<!-- Panel: Tambah Tugas -->
<div class="setting-overlay" id="addTaskOverlay" onclick="closeAddTaskPanel()"></div>
<div class="setting-panel" id="addTaskPanel" aria-hidden="true">
  <div class="sp-header">
    <h2>Tambah Tugas</h2>
    <button type="button" class="sp-close" onclick="closeAddTaskPanel()">✕</button>
  </div>
  <div style="padding:16px;">
    <form method="POST">
      <input type="hidden" name="action" value="add_task_panel">
      <div class="form-group">
        <label>Judul Tugas</label>
        <input type="text" name="title" placeholder="Contoh: Kerjakan tugas matematika" required>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="description" placeholder="Tambahkan keterangan..."></textarea>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Label</label>
          <select name="label" id="add_task_label" onchange="cekLabelPanel(this.value)">
            <option value="personal">Personal</option>
            <option value="kuliah">Kuliah</option>
            <option value="kerja">Kerja</option>
            <option value="belanja">Belanja</option>
            <option value="lainnya">Lainnya</option>
          </select>
          <div class="label-custom" id="add_task_label_custom">
            <input type="text" name="label_custom" placeholder="Tulis label kamu...">
          </div>
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select name="priority">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Mulai</label>
          <input type="date" name="activity_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Deadline</label>
          <input type="date" name="deadline">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-simpan">Tambah Tugas</button>
        <button type="button" class="btn-batal" onclick="closeAddTaskPanel()">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Panel: Tambah Project -->
<div class="setting-overlay" id="addProjectOverlay" onclick="closeAddProjectPanel()"></div>
<div class="setting-panel" id="addProjectPanel" aria-hidden="true">
  <div class="sp-header">
    <h2>Tambah Project</h2>
    <button type="button" class="sp-close" onclick="closeAddProjectPanel()">✕</button>
  </div>
  <div style="padding:16px;">
    <form method="POST" action="tambah_project.php">
      <div class="form-group">
        <label>Nama</label>
        <input type="text" name="nama_project" placeholder="Nama project..." required>
      </div>
      <div class="form-group">
        <label>Warna</label>
        <input type="color" name="warna" value="#e63946"
               style="width:56px;height:42px;padding:0;border:none;background:transparent;">
      </div>
      <div class="form-group">
        <label>Workspace</label>
        <input type="text" name="workspace" placeholder="Contoh: Tim Product / Team Dev">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-simpan">Tambah</button>
        <button type="button" class="btn-batal" onclick="closeAddProjectPanel()">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════════════════════ MAIN CONTENT ══════════════════════════════ -->
<div class="main">
  <div class="content">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="title">
        <h1>Grow Plant</h1>
        <p class="sub-title">Kelola dan perbarui tugasmu di sini</p>
      </div>
    </div>

    <!-- TUGAS HARI INI -->
    <div class="section">
      <div class="section-header">
        <div class="section-title">Hari ini</div>
        <a href="#" class="link-red">Lihat semua</a>
      </div>

      <?php if ($tugas_hari_ini->num_rows === 0): ?>
        <div class="kosong">Tidak ada tugas untuk hari ini — yuk mulai buat rencana 🌱</div>
      <?php else: ?>
        <table class="tugas-table">
          <thead>
            <tr>
              <th>Nama Kegiatan</th>
              <th>Keterangan</th>
              <th>Priority</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($tugas = $tugas_hari_ini->fetch_assoc()): ?>
            <tr>
              <td class="tugas-name"><?= htmlspecialchars($tugas['title']) ?></td>
              <td class="tugas-desc"><?= htmlspecialchars($tugas['description']) ?></td>
              <td><span class="badge badge-<?= $tugas['priority'] ?>"><?= ucfirst($tugas['priority']) ?></span></td>
              <td>
                <a href="tambah.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn">Edit</a>
                <a href="../crud/update_status.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn">Done</a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
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
          <thead>
            <tr>
              <th>Nama Tugas</th>
              <th>Label</th>
              <th>Deadline</th>
              <th>Priority</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($tugas = $semua_tugas->fetch_assoc()): ?>
            
            <tr>
              <td class="tugas-name"><?= htmlspecialchars($tugas['title']) ?></td>
              <td class="tugas-desc"><?= htmlspecialchars($tugas['label'] ?? '-') ?></td>
              <td class="tugas-desc"><?= $tugas['deadline'] ? date('d M Y', strtotime($tugas['deadline'])) : '-' ?></td>
              <td><span class="badge badge-<?= $tugas['priority'] ?>"><?= ucfirst($tugas['priority']) ?></span></td>
              <td>
              <button type="button" class="aksi-btn" onclick="bukaModalEdit(<?= $tugas['id_daily_activity'] ?>)">Edit</button>
                <a href="update_status.php?id=<?= $tugas['id_daily_activity'] ?>" class="aksi-btn">Done</a>
                <a href="#" class="aksi-btn hapus"
                  onclick="openDeleteModal(event, <?= $tugas['id_daily_activity'] ?>)">Hapus</a>
              </td>
            </tr>
            <div id="deleteModal" class="modal">
              <div class="modal-content">
                <h3>Konfirmasi</h3>
                <p>Yakin ingin menghapus data ini?</p>

                <div class="modal-actions">
                  <button onclick="closeDeleteModal()" class="btn-cancel">Batal</button>
                  <a id="confirmDeleteBtn" class="btn-delete">Hapus</a>
                </div>
              </div>
            </div>

            <div id="editModalOverlay" style="display:none;" onclick="if(event.target===this)tutupModalEdit()">
  <div id="editModalBox">

    <div class="modal-header">
      <span class="modal-title">Edit Tugas</span>
      <button class="modal-close" onclick="tutupModalEdit()">&#x2715;</button>
    </div>

    <form id="editForm" method="POST">
      <!-- hidden: mode edit -->
      <input type="hidden" name="mode" value="edit">
      <input type="hidden" name="id"   id="editId">

      <div class="form-group">
        <label class="form-label">Judul Tugas</label>
        <input type="text" name="title" id="editTitle"
               class="form-input" placeholder="Contoh: Kerjakan tugas matematika" required>
      </div>

      <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" id="editDescription"
                  class="form-textarea" rows="4"
                  placeholder="Tambahkan keterangan..."></textarea>
      </div>

      <div class="form-row">
        <div class="form-group half">
          <label class="form-label">Label</label>
          <div class="select-wrap">
            <select name="label" id="editLabel" class="form-select" onchange="cekLabelEdit(this.value)">
              <option value="personal">Personal</option>
              <option value="kuliah">Kuliah</option>
              <option value="kerja">Kerja</option>
              <option value="belanja">Belanja</option>
              <option value="lainnya">Lainnya</option>
            </select>
            <span class="select-arrow">&#9660;</span>
          </div>
          <div id="editLabelCustomWrap" style="display:none; margin-top:8px;">
            <input type="text" name="label_custom" id="editLabelCustom"
                   class="form-input" placeholder="Tulis label kamu...">
          </div>
        </div>

        <div class="form-group half">
          <label class="form-label">Priority</label>
          <div class="select-wrap">
            <select name="priority" id="editPriority" class="form-select">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
            <span class="select-arrow">&#9660;</span>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group half">
          <label class="form-label">Tanggal Mulai</label>
          <input type="date" name="activity_date" id="editActivityDate" class="form-input">
        </div>
        <div class="form-group half">
          <label class="form-label">Deadline</label>
          <input type="date" name="deadline" id="editDeadline" class="form-input">
        </div>
      </div>

      <div class="modal-actions">
        <button type="submit" class="btn-primary">Simpan Perubahan</button>
        <button type="button" class="btn-secondary" onclick="tutupModalEdit()">Batal</button>
      </div>
    </form>

  </div>
</div>

<!-- ===== STYLES ===== -->
<style>
/* Overlay */
#editModalOverlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.65);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: fadeIn .18s ease;
}
@keyframes fadeIn { from { opacity:0 } to { opacity:1 } }

/* Box */
#editModalBox {
  background: #1a1a1a;
  border: 1px solid #2e2e2e;
  border-radius: 14px;
  width: 620px;
  max-width: 95vw;
  max-height: 90vh;
  overflow-y: auto;
  padding: 28px 32px;
  box-shadow: 0 24px 64px rgba(0,0,0,.6);
  animation: slideUp .22s cubic-bezier(.4,0,.2,1);
}
@keyframes slideUp { from { transform:translateY(20px); opacity:0 } to { transform:translateY(0); opacity:1 } }

/* Scrollbar */
#editModalBox::-webkit-scrollbar { width:5px }
#editModalBox::-webkit-scrollbar-track { background:transparent }
#editModalBox::-webkit-scrollbar-thumb { background:#333; border-radius:4px }

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
.modal-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: #f0f0f0;
  letter-spacing: .01em;
}
.modal-close {
  background: none;
  border: none;
  color: #666;
  font-size: 1rem;
  cursor: pointer;
  line-height: 1;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background .15s, color .15s;
}
.modal-close:hover { background: #2a2a2a; color: #ccc; }

/* Form groups */
.form-group {
  margin-bottom: 18px;
}
.form-group.half {
  flex: 1;
  min-width: 0;
}
.form-row {
  display: flex;
  gap: 16px;
}
.form-label {
  display: block;
  font-size: .82rem;
  font-weight: 600;
  color: #ccc;
  margin-bottom: 7px;
  letter-spacing: .03em;
  text-transform: uppercase;
}

/* Inputs */
.form-input,
.form-select,
.form-textarea {
  width: 100%;
  background: #111;
  border: 1px solid #2e2e2e;
  border-radius: 8px;
  color: #e8e8e8;
  font-size: .92rem;
  padding: 10px 14px;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
  box-sizing: border-box;
  font-family: inherit;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  border-color: #4a7c59;
  box-shadow: 0 0 0 3px rgba(74,124,89,.15);
}
.form-input::placeholder,
.form-textarea::placeholder { color: #444; }
.form-textarea { resize: vertical; min-height: 90px; }

/* Select wrapper */
.select-wrap {
  position: relative;
}
.form-select {
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  padding-right: 36px;
}
.select-arrow {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #555;
  font-size: .7rem;
  pointer-events: none;
}

/* Date input */
input[type="date"].form-input::-webkit-calendar-picker-indicator {
  filter: invert(.4);
  cursor: pointer;
}

/* Actions */
.modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}
.btn-primary {
  background: #2d5a3d;
  color: #e8f5ec;
  border: 1px solid #3d7a53;
  border-radius: 8px;
  padding: 10px 22px;
  font-size: .9rem;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s, transform .1s;
  font-family: inherit;
}
.btn-primary:hover { background: #3a6e4a; transform: translateY(-1px); }
.btn-primary:active { transform: translateY(0); }

.btn-secondary {
  background: transparent;
  color: #888;
  border: 1px solid #2e2e2e;
  border-radius: 8px;
  padding: 10px 22px;
  font-size: .9rem;
  font-weight: 500;
  cursor: pointer;
  transition: background .15s, color .15s;
  font-family: inherit;
}
.btn-secondary:hover { background: #222; color: #ccc; }

/* Loading spinner inside modal */
#editLoadingSpinner {
  text-align: center;
  padding: 40px 0;
  color: #555;
  font-size: .9rem;
}
</style>
          <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div><!-- .content -->

  <!-- RIGHT PANEL -->
  <div class="right-panel">

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
          $isDone        = $pt['status'] === 'done';
          $p             = $isDone ? 100 : 20;
          $r             = 18;
          $circumference = 2 * 3.14159 * $r;
          $offset        = $circumference - ($p / 100) * $circumference;
          $color         = $isDone ? '#6bcb77' : '#555';
        ?>
        <div class="progress-item">
          <div class="progress-circle">
            <svg width="44" height="44" viewBox="0 0 44 44">
              <circle cx="22" cy="22" r="<?= $r ?>" fill="none" stroke="#2a2a2a" stroke-width="3"/>
              <circle cx="22" cy="22" r="<?= $r ?>" fill="none" stroke="<?= $color ?>" stroke-width="3"
                      stroke-dasharray="<?= $circumference ?>"
                      stroke-dashoffset="<?= $offset ?>"
                      stroke-linecap="round"
                      transform="rotate(-90 22 22)"/>
            </svg>
            <div class="pct" style="color:<?= $color ?>;"><?= $p ?>%</div>
          </div>
          <div class="progress-info">
            <div class="progress-name"><?= htmlspecialchars($pt['title']) ?></div>
            <div class="progress-sub <?= $isDone ? 'done' : '' ?>">
              <?= $isDone ? '✓ Selesai' : '⏳ Belum selesai' ?>
            </div>
          </div>
          <?php if (!$isDone): ?>
          <a href="tambah.php?id=<?= $pt['id_daily_activity'] ?>"
             class="progress-edit-btn" title="Edit tugas">
            <span class="material-symbols-outlined" style="font-size:16px;">edit</span>
          </a>
          <?php else: ?>
          <div class="progress-done-mark">✓</div>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

  </div><!-- .right-panel -->
</div><!-- .main -->

<script>
/* ── Kalender ────────────────────────────────────────── */
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

/* ── Panel: Add Task ─────────────────────────────────── */
function openAddTaskPanel() {
  document.getElementById('addTaskPanel')?.classList.add('open');
  document.getElementById('addTaskOverlay')?.classList.add('open');
  document.getElementById('addTaskPanel')?.setAttribute('aria-hidden', 'false');
}
function closeAddTaskPanel() {
  document.getElementById('addTaskPanel')?.classList.remove('open');
  document.getElementById('addTaskOverlay')?.classList.remove('open');
  document.getElementById('addTaskPanel')?.setAttribute('aria-hidden', 'true');
}
function toggleAddTaskPanel(e) {
  if (e) e.preventDefault();
  const p = document.getElementById('addTaskPanel');
  p?.classList.contains('open') ? closeAddTaskPanel() : openAddTaskPanel();
}
function cekLabelPanel(val) {
  document.getElementById('add_task_label_custom').style.display = val === 'lainnya' ? 'block' : 'none';
}

/* ── Panel: Add Project ──────────────────────────────── */
function openAddProjectPanel() {
  document.getElementById('addProjectPanel')?.classList.add('open');
  document.getElementById('addProjectOverlay')?.classList.add('open');
}
function closeAddProjectPanel() {
  document.getElementById('addProjectPanel')?.classList.remove('open');
  document.getElementById('addProjectOverlay')?.classList.remove('open');
}
function toggleAddProjectPanel(e) {
  if (e) e.preventDefault();
  const p = document.getElementById('addProjectPanel');
  p?.classList.contains('open') ? closeAddProjectPanel() : openAddProjectPanel();
}
function openAddProjectFromSidebar(e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }
  openAddProjectPanel();
}

/* ── Sidebar project dropdown ────────────────────────── */
function toggleProjectDropdown(e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }
  const dropdown = document.getElementById('projectDropdown');
  const nav      = document.getElementById('projectNav');
  const chevron  = document.getElementById('projectChevron');
  if (!dropdown) return;
  const isOpen = !dropdown.hasAttribute('hidden');
  if (isOpen) {
    dropdown.setAttribute('hidden', '');
    nav.setAttribute('aria-expanded', 'false');
    if (chevron) chevron.textContent = 'chevron_right';
  } else {
    dropdown.removeAttribute('hidden');
    nav.setAttribute('aria-expanded', 'true');
    if (chevron) chevron.textContent = 'expand_more';
  }
}

/* ── Keyboard shortcuts ──────────────────────────────── */
document.addEventListener('keydown', (ev) => {
  if (ev.key === 'Escape') {
    closeAddTaskPanel();
    closeAddProjectPanel();
  }
  if ((ev.key === 'Enter' || ev.key === ' ') && ev.target?.id === 'projectNav') {
    ev.preventDefault();
    toggleProjectDropdown(ev);
  }
});

/* ── Realtime search ─────────────────────────────────── */
function normalizeText(v) { return (v || '').toString().toLowerCase().trim(); }

function filterTables(query) {
  const q = normalizeText(query);
  document.querySelectorAll('table.tugas-table').forEach((table) => {
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    let visibleCount = 0;
    rows.forEach((tr) => {
      if (tr.getAttribute('data-no-result') === '1') return;
      const match = q === '' || normalizeText(tr.textContent).includes(q);
      tr.style.display = match ? '' : 'none';
      if (match) visibleCount++;
    });
    // Baris "tidak ditemukan"
    let existing = table.querySelector('tr[data-no-result="1"]');
    if (q !== '' && visibleCount === 0) {
      if (!existing) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-no-result', '1');
        const td = document.createElement('td');
        td.colSpan = table.querySelectorAll('thead th').length || 5;
        td.className = 'kosong';
        td.style.padding = '14px';
        td.textContent = 'Tugas tidak ditemukan.';
        tr.appendChild(td);
        table.querySelector('tbody')?.appendChild(tr);
      }
    } else {
      existing?.remove();
    }
  });

  // Progress items
  document.querySelectorAll('.progress-item').forEach((a) => {
    a.style.display = (q === '' || normalizeText(a.textContent).includes(q)) ? '' : 'none';
  });
}

let searchTimer = null;
document.getElementById('sidebarSearchInput')?.addEventListener('input', (e) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => filterTables(e.target.value), 60);
});

function openDeleteModal(event, id) {
  event.preventDefault();

  document.getElementById("deleteModal").style.display = "flex";

  // set link hapus
  document.getElementById("confirmDeleteBtn").href = "hapus.php?id=" + id;
}

function closeDeleteModal() {
  document.getElementById("deleteModal").style.display = "none";
}

const pilihanDefaultEdit = ['personal','kuliah','kerja','belanja','lainnya'];

function cekLabelEdit(val) {
  const wrap = document.getElementById('editLabelCustomWrap');
  wrap.style.display = val === 'lainnya' ? 'block' : 'none';
}

function bukaModalEdit(idTugas) {
  const overlay = document.getElementById('editModalOverlay');
  overlay.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  // Reset form
  document.getElementById('editForm').reset();
  document.getElementById('editLabelCustomWrap').style.display = 'none';

  // Fetch data via AJAX
  fetch(`edit.php?id=${idTugas}&ajax=1`)
    .then(r => r.json())
    .then(data => {
      document.getElementById('editId').value           = data.id_daily_activity;
      document.getElementById('editTitle').value        = data.title         || '';
      document.getElementById('editDescription').value  = data.description   || '';
      document.getElementById('editPriority').value     = data.priority      || 'medium';
      document.getElementById('editActivityDate').value = data.activity_date || '';
      document.getElementById('editDeadline').value     = data.deadline      || '';

      // Label
      const labelSel = document.getElementById('editLabel');
      if (pilihanDefaultEdit.includes(data.label)) {
        labelSel.value = data.label;
        document.getElementById('editLabelCustomWrap').style.display = 'none';
      } else {
        labelSel.value = 'lainnya';
        document.getElementById('editLabelCustom').value = data.label || '';
        document.getElementById('editLabelCustomWrap').style.display = 'block';
      }
    })
    .catch(() => alert('Gagal memuat data tugas.'));
}

function tutupModalEdit() {
  document.getElementById('editModalOverlay').style.display = 'none';
  document.body.style.overflow = '';
}

// Submit via AJAX
document.getElementById('editForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fd = new FormData(this);

  fetch('edit.php', { method: 'POST', body: fd })
    .then(r => {
      if (r.redirected || r.ok) {
        tutupModalEdit();
        location.reload(); // refresh daftar tugas
      }
    })
    .catch(() => alert('Gagal menyimpan perubahan.'));
});

// Tutup dengan ESC
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') tutupModalEdit();
});

</script>
</body>
</html>