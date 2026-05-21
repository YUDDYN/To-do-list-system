<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: lp.php');
    exit();
}

$id_user  = (int) $_SESSION['id_user'];
$username = $_SESSION['username'] ?? 'User';

// ─── HELPER: upsert streak row ────────────────────────────────────────────────
function ensureStreakRow(mysqli $conn, int $user_id): void {
    $conn->query(
        "INSERT IGNORE INTO user_streak (user_id, progress, streak_days, last_activity)
         VALUES ($user_id, 0, 0, NULL)"
    );
}

// ─── HELPER: tambah progress setelah tugas selesai ───────────────────────────
function addStreakProgress(mysqli $conn, int $user_id, int $poin = 10): void {
    ensureStreakRow($conn, $user_id);

    $today = date('Y-m-d');
    $stmt  = $conn->prepare("SELECT streak_days, last_activity FROM user_streak WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $streak_days    = (int)($row['streak_days']    ?? 0);
    $last_activity  = $row['last_activity'] ?? null;

    // Hitung streak hari
    if ($last_activity === null) {
        $streak_days = 1;
    } elseif ($last_activity === $today) {
        // Sudah tercatat hari ini – streak tidak berubah
    } elseif ($last_activity === date('Y-m-d', strtotime('-1 day'))) {
        $streak_days++; // hari berturut-turut
    } else {
        $streak_days = 1; // putus – mulai ulang
    }

    $upd = $conn->prepare(
        "UPDATE user_streak
         SET progress = progress + ?, streak_days = ?, last_activity = ?
         WHERE user_id = ?"
    );
    $upd->bind_param('iisi', $poin, $streak_days, $today, $user_id);
    $upd->execute();
}

// ─── AKSI: Upload File pada tugas ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_file') {
    $task_id = (int)($_POST['task_id'] ?? 0);
    if ($task_id > 0 && isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf','application/msword',
                          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                          'image/jpeg','image/png','image/gif','image/webp',
                          'application/zip','text/plain'];
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mime      = finfo_file($finfo, $_FILES['task_file']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed_types)) {
            $upload_dir = '../uploads/tasks/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext      = pathinfo($_FILES['task_file']['name'], PATHINFO_EXTENSION);
            $filename = 'task_' . $task_id . '_' . time() . '.' . $ext;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['task_file']['tmp_name'], $filepath)) {
                $stmt_file = $conn->prepare(
                    "UPDATE daily_activities SET file_attachment = ?, file_original_name = ? WHERE id_daily_activity = ? AND user_id = ?"
                );
                $orig_name = htmlspecialchars($_FILES['task_file']['name']);
                $stmt_file->bind_param('ssii', $filename, $orig_name, $task_id, $id_user);
                $stmt_file->execute();
            }
        }
    }
    header('Location: index.php');
    exit();
}

// ─── AKSI: Tambah Tugas ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_task_panel') {
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $priority      = $_POST['priority'] ?? 'medium';
    $activity_date = !empty($_POST['activity_date']) ? $_POST['activity_date'] : null;
    $deadline      = !empty($_POST['deadline'])      ? $_POST['deadline']      : null;
    $label_input   = $_POST['label'] ?? 'personal';
    $label_custom  = trim($_POST['label_custom'] ?? '');
    $label         = $label_input === 'lainnya' ? $label_custom : $label_input;
    $file_attachment    = null;
    $file_original_name = null;

    if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf','application/msword',
                          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                          'image/jpeg','image/png','image/gif','image/webp',
                          'application/zip','text/plain'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['task_file']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed_types, true)) {
            $upload_dir = '../uploads/tasks/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext      = strtolower(pathinfo($_FILES['task_file']['name'], PATHINFO_EXTENSION));
            $baseName = pathinfo($_FILES['task_file']['name'], PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
            $filename = 'task_' . time() . '_' . $safeName . '.' . $ext;
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['task_file']['tmp_name'], $filepath)) {
                $file_attachment    = $filename;
                $file_original_name = htmlspecialchars($_FILES['task_file']['name']);
            }
        }
    }

    if ($title !== '' && $label !== '') {
        $stmt_add = $conn->prepare(
            "INSERT INTO daily_activities (user_id, title, description, priority, activity_date, deadline, label, file_attachment, file_original_name)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_add->bind_param('issssssss', $id_user, $title, $description, $priority, $activity_date, $deadline, $label, $file_attachment, $file_original_name);
        $stmt_add->execute();
    }

    header('Location: index.php');
    exit();
}

// ─── AKSI: Tandai tugas selesai (done) dari halaman ini ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'done_task') {
    $task_id = (int)($_POST['task_id'] ?? 0);
    if ($task_id > 0) {
        // Pastikan tugas milik user ini dan belum selesai
        $chk = $conn->prepare(
            "SELECT id_daily_activity FROM daily_activities
             WHERE id_daily_activity = ? AND user_id = ? AND status = 'pending'"
        );
        $chk->bind_param('ii', $task_id, $id_user);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $upd = $conn->prepare(
                "UPDATE daily_activities SET status = 'done' WHERE id_daily_activity = ? AND user_id = ?"
            );
            $upd->bind_param('ii', $task_id, $id_user);
            $upd->execute();
            // +10 poin per tugas selesai
            addStreakProgress($conn, $id_user, 10);
        }
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

// ─── STREAK DATA ─────────────────────────────────────────────────────────────
ensureStreakRow($conn, $id_user);
$sk = $conn->prepare("SELECT progress, streak_days FROM user_streak WHERE user_id = ?");
$sk->bind_param('i', $id_user);
$sk->execute();
$streak_row  = $sk->get_result()->fetch_assoc();
$s_progress  = (int)($streak_row['progress']    ?? 0);
$s_days      = (int)($streak_row['streak_days'] ?? 0);

// Tentukan level & tahap tumbuhan
$levels = [
    ['min'=>0,   'max'=>20,  'level'=>1, 'nama'=>'Benih',      'emoji'=>'🌱', 'next'=>20],
    ['min'=>21,  'max'=>50,  'level'=>2, 'nama'=>'Tunas',      'emoji'=>'🌿', 'next'=>50],
    ['min'=>51,  'max'=>100, 'level'=>3, 'nama'=>'Pohon Muda', 'emoji'=>'🌳', 'next'=>100],
    ['min'=>101, 'max'=>200, 'level'=>4, 'nama'=>'Pohon Besar','emoji'=>'🌲', 'next'=>200],
    ['min'=>201, 'max'=>999, 'level'=>5, 'nama'=>'Pohon Raksasa','emoji'=>'🏔️','next'=>999],
];
$current_level = $levels[0];
foreach ($levels as $lv) {
    // Pilih level tertinggi yang sudah tercapai, agar pertumbuhan pohon langsung loncat ke level saat ini
    if ($s_progress >= $lv['min']) {
        $current_level = $lv;
    }
}

$lv_min   = $current_level['min'];
$lv_max   = $current_level['max'];
$lv_range = max(1, $lv_max - $lv_min);
$lv_cur   = min($s_progress - $lv_min, $lv_range);
$pct_bar  = min(100, round(($lv_cur / $lv_range) * 100));
$next_level_label = $current_level['level'] < end($levels)['level']
    ? 'Progress ke Level ' . ($current_level['level'] + 1)
    : 'Level maksimum tercapai';
$plant_wrapper_class = $current_level['level'] === end($levels)['level'] ? 'streak-plant-wrap level-max' : 'streak-plant-wrap';

// Tampilan tumbuhan SVG berdasarkan level
function plantSVG(int $level): string {
    $svgs = [
        1 => '<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <!-- Pot -->
                <ellipse cx="60" cy="100" rx="28" ry="8" fill="#8B5E3C" opacity=".4"/>
                <path d="M38 88 Q38 108 60 108 Q82 108 82 88 Z" fill="#A0522D"/>
                <rect x="34" y="82" width="52" height="10" rx="5" fill="#8B4513"/>
                <!-- Bibit kecil -->
                <line x1="60" y1="82" x2="60" y2="60" stroke="#5D8A3C" stroke-width="2.5"/>
                <ellipse cx="60" cy="56" rx="9" ry="12" fill="#6BCB5A" transform="rotate(-15 60 56)"/>
                <ellipse cx="60" cy="58" rx="9" ry="12" fill="#7ED95F" transform="rotate(15 60 58)"/>
                <!-- Sparkle -->
                <circle cx="45" cy="52" r="2" fill="#B5EAD7" opacity=".7"/>
                <circle cx="76" cy="48" r="1.5" fill="#B5EAD7" opacity=".5"/>
              </svg>',
        2 => '<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <ellipse cx="60" cy="100" rx="28" ry="8" fill="#8B5E3C" opacity=".4"/>
                <path d="M38 88 Q38 108 60 108 Q82 108 82 88 Z" fill="#A0522D"/>
                <rect x="34" y="82" width="52" height="10" rx="5" fill="#8B4513"/>
                <!-- Batang -->
                <line x1="60" y1="82" x2="60" y2="48" stroke="#5D8A3C" stroke-width="3"/>
                <!-- Daun kiri -->
                <path d="M60 68 Q38 58 40 40 Q55 50 60 68Z" fill="#4CAF50"/>
                <!-- Daun kanan -->
                <path d="M60 62 Q82 52 80 34 Q65 44 60 62Z" fill="#66BB6A"/>
                <!-- Pucuk -->
                <ellipse cx="60" cy="44" rx="10" ry="14" fill="#81C784"/>
                <circle cx="45" cy="42" r="2" fill="#C8E6C9" opacity=".6"/>
                <circle cx="78" cy="36" r="1.5" fill="#C8E6C9" opacity=".5"/>
              </svg>',
        3 => '<svg viewBox="0 0 120 130" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <ellipse cx="60" cy="112" rx="30" ry="9" fill="#8B5E3C" opacity=".35"/>
                <path d="M40 96 Q40 116 60 116 Q80 116 80 96 Z" fill="#A0522D"/>
                <rect x="36" y="89" width="48" height="11" rx="5" fill="#8B4513"/>
                <!-- Batang -->
                <line x1="60" y1="89" x2="60" y2="44" stroke="#5D8A3C" stroke-width="4"/>
                <!-- Daun besar -->
                <path d="M60 72 Q28 56 32 26 Q52 40 60 72Z" fill="#388E3C"/>
                <path d="M60 64 Q92 48 88 18 Q68 32 60 64Z" fill="#43A047"/>
                <path d="M60 55 Q42 38 50 18 Q62 32 60 55Z" fill="#4CAF50"/>
                <!-- Kanopi -->
                <circle cx="60" cy="38" r="22" fill="#4CAF50"/>
                <circle cx="42" cy="44" r="14" fill="#43A047"/>
                <circle cx="78" cy="44" r="14" fill="#388E3C"/>
                <circle cx="60" cy="22" r="16" fill="#66BB6A"/>
                <circle cx="44" cy="34" r="2" fill="#A5D6A7" opacity=".5"/>
                <circle cx="77" cy="26" r="2" fill="#A5D6A7" opacity=".4"/>
              </svg>',
        4 => '<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <ellipse cx="70" cy="128" rx="34" ry="9" fill="#6D4C41" opacity=".4"/>
                <rect x="58" y="88" width="24" height="40" rx="5" fill="#6D4C41"/>
                <!-- Cabang -->
                <line x1="70" y1="98" x2="38" y2="72" stroke="#5D4037" stroke-width="4"/>
                <line x1="70" y1="94" x2="102" y2="68" stroke="#5D4037" stroke-width="4"/>
                <!-- Kanopi besar -->
                <circle cx="70" cy="52" r="32" fill="#2E7D32"/>
                <circle cx="44" cy="62" r="22" fill="#388E3C"/>
                <circle cx="96" cy="62" r="22" fill="#1B5E20"/>
                <circle cx="56" cy="38" r="20" fill="#43A047"/>
                <circle cx="84" cy="36" r="20" fill="#2E7D32"/>
                <circle cx="70" cy="30" r="18" fill="#4CAF50"/>
                <circle cx="38" cy="55" r="2.5" fill="#A5D6A7" opacity=".5"/>
                <circle cx="100" cy="42" r="2" fill="#A5D6A7" opacity=".4"/>
              </svg>',
        5 => '<svg viewBox="0 0 160 150" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <ellipse cx="80" cy="138" rx="40" ry="10" fill="#4E342E" opacity=".4"/>
                <rect x="68" y="90" width="24" height="48" rx="6" fill="#4E342E"/>
                <!-- Cabang -->
                <line x1="80" y1="100" x2="32" y2="68" stroke="#3E2723" stroke-width="5"/>
                <line x1="80" y1="96" x2="128" y2="64" stroke="#3E2723" stroke-width="5"/>
                <line x1="80" y1="108" x2="40" y2="90" stroke="#3E2723" stroke-width="3.5"/>
                <line x1="80" y1="108" x2="120" y2="90" stroke="#3E2723" stroke-width="3.5"/>
                <!-- Kanopi mega -->
                <circle cx="80" cy="50" r="40" fill="#1B5E20"/>
                <circle cx="48" cy="62" r="28" fill="#2E7D32"/>
                <circle cx="112" cy="60" r="28" fill="#1B5E20"/>
                <circle cx="60" cy="32" r="26" fill="#388E3C"/>
                <circle cx="100" cy="30" r="26" fill="#2E7D32"/>
                <circle cx="80" cy="20" r="22" fill="#43A047"/>
                <circle cx="36" cy="52" r="3" fill="#81C784" opacity=".5"/>
                <circle cx="122" cy="38" r="2.5" fill="#81C784" opacity=".4"/>
                <circle cx="80" cy="10" r="2" fill="#A5D6A7" opacity=".4"/>
              </svg>',
    ];
    return $svgs[$level] ?? $svgs[1];
}
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
  <link rel="stylesheet" href="../design/ts.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <script src="theme.js"></script>
</head>
<body>

<!-- ═══════════════════════════ SIDEBAR ════════════════════════════════════ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <img src="../asset/logo.jpg" alt="Logo" width="42" height="42" style="border-radius:20px;">
    </div>
    <div class="logo-text">Grow<br>Plan</div>
  </div>

  <a href="#" class="nav-item" onclick="toggleAddTaskPanel(event)">
    <span class="icon">+</span> Tambah Tugas
  </a>

  <div class="nav-item nav-search" style="cursor:default;">
    <span class="material-symbols-outlined icon">search</span>
    <input id="sidebarSearchInput" type="text" placeholder="Cari tugas..."
           autocomplete="off" style="flex:1;background:transparent;border:none;outline:none;color:inherit;font:inherit;">
  </div>

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
    <form method="POST" enctype="multipart/form-data">
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
            <option value="sekolah">Sekolah</option>
            <option value="kerja">Kerja</option>
            <option value="lainnya">Lainnya</option>
          </select>
          <div class="label-custom" id="add_task_label_custom">
            <input type="text" name="label_custom" placeholder="Tulis label kamu...">
          </div>
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select name="priority">
            <option value="low">Mudah</option>
            <option value="medium" selected>Sedang</option>
            <option value="high">Susah</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Mulai</label>
          <input type="date" name="activity_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group file-upload-group">
          <label>Upload File</label>
          <div class="file-upload-row">
            <label for="task_file" class="btn-upload">Pilih File</label>
            <span id="addTaskFileName" class="file-name">Belum ada file dipilih</span>
          </div>
          <input type="file" id="task_file" name="task_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.zip,.txt" onchange="updateAddTaskFileName()" hidden>
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
        <h1>Grow Plan</h1>
        <p class="sub-title">Kelola dan perbarui tugasmu di sini</p>
      </div>
    </div>

    <!-- ══════════════ STREAK TREE CARD ══════════════ -->
    <div class="streak-card">

      <!-- Level badge -->
      <div class="streak-level-badge">
        <?= $current_level['emoji'] ?> Level <?= $current_level['level'] ?> — <?= $current_level['nama'] ?>
      </div>

      <!-- Ilustrasi tanaman -->
      <div class="<?= $plant_wrapper_class ?>">
        <?= plantSVG($current_level['level']) ?>
      </div>

      <!-- Info kanan -->
      <div class="streak-info">
        <h2 class="streak-title">Streak Tree 🌱</h2>
        <p class="streak-subtitle">Selesaikan tugas untuk menyiram tanamanmu!</p>

        <!-- Stat chips -->
        <div class="streak-stats">
          <div class="streak-stat">
            <span class="streak-stat-icon">💧</span>
            <div>
              <div class="streak-stat-val"><?= $s_progress ?></div>
              <div class="streak-stat-label">Total Poin</div>
            </div>
          </div>
          <div class="streak-stat">
            <span class="streak-stat-icon">🔥</span>
            <div>
              <div class="streak-stat-val"><?= $s_days ?></div>
              <div class="streak-stat-label">Streak Hari</div>
            </div>
          </div>
          <div class="streak-stat">
            <span class="streak-stat-icon">🌿</span>
            <div>
              <div class="streak-stat-val">Lv.<?= $current_level['level'] ?></div>
              <div class="streak-stat-label"><?= $current_level['nama'] ?></div>
            </div>
          </div>
        </div>

        <!-- Progress bar -->
        <div class="streak-progress-row">
          <span class="streak-progress-label">
            <?= $next_level_label ?>
          </span>
          <span class="streak-progress-frac">
            <?= $s_progress ?> / <?= $lv_max ?>
          </span>
        </div>
        <div class="streak-bar-wrap">
          <div class="streak-bar-fill" style="width:<?= $pct_bar ?>%;"></div>
        </div>

        <!-- Tombol siram (tandai tugas selesai via modal kecil) -->
        <button class="streak-water-btn" onclick="openWaterModal()">
          💧 Siram Sekarang
        </button>
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
              <th>File</th>
              <th>Priority</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($tugas = $tugas_hari_ini->fetch_assoc()): ?>
            <tr>
              <td class="tugas-name"><?= htmlspecialchars($tugas['title']) ?></td>
              <td class="tugas-desc"><?= htmlspecialchars($tugas['description']) ?></td>
              <td class="tugas-desc">
                <?php if (!empty($tugas['file_attachment'])): ?>
                  <a class="file-link" href="../uploads/tasks/<?= htmlspecialchars($tugas['file_attachment']) ?>" target="_blank">📎 <?= htmlspecialchars($tugas['file_original_name'] ?? 'Lihat file') ?></a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td><span class="badge badge-<?= $tugas['priority'] ?>"><?= ucfirst($tugas['priority']) ?></span></td>
              <td>
                <a href="edit.php?id=59" class="aksi-btn">Edit</a>
                <!-- Done: POST ke action done_task agar streak ikut terupdate -->
                <form method="POST" style="display:inline;" onsubmit="handleDone(event,this)">
                  <input type="hidden" name="action" value="done_task">
                  <input type="hidden" name="task_id" value="<?= $tugas['id_daily_activity'] ?>">
                  <button type="submit" class="aksi-btn">Done</button>
                </form>
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
              <th>File</th>
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
              <td class="tugas-desc">
                <?php if (!empty($tugas['file_attachment'])): ?>
                  <a class="file-link" href="../uploads/tasks/<?= htmlspecialchars($tugas['file_attachment']) ?>" target="_blank">📎 <?= htmlspecialchars($tugas['file_original_name'] ?? 'Lihat file') ?></a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="tugas-desc"><?= $tugas['deadline'] ? date('d M Y', strtotime($tugas['deadline'])) : '-' ?></td>
              <td><span class="badge badge-<?= $tugas['priority'] ?>"><?= ucfirst($tugas['priority']) ?></span></td>
              <td>
                <button type="button" class="aksi-btn" onclick="bukaModalEdit(<?= $tugas['id_daily_activity'] ?>)">Edit</button>
                <!-- Done dengan streak -->
                <form method="POST" style="display:inline;" onsubmit="handleDone(event,this)">
                  <input type="hidden" name="action" value="done_task">
                  <input type="hidden" name="task_id" value="<?= $tugas['id_daily_activity'] ?>">
                  <button type="submit" class="aksi-btn">Done</button>
                </form>
                <a href="#" class="aksi-btn hapus"
                   onclick="openDeleteModal(event, <?= $tugas['id_daily_activity'] ?>)">Hapus</a>
              </td>
            </tr>
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
          <a href="edit.php?id=<?= $pt['id_daily_activity'] ?>"
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

<!-- ── Delete modal ── -->
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

<!-- ── Edit modal ── -->
<div id="editModalOverlay" style="display:none;" onclick="if(event.target===this)tutupModalEdit()">
  <div id="editModalBox">
    <div class="modal-header">
      <span class="modal-title">Edit Tugas</span>
      <button class="modal-close" onclick="tutupModalEdit()">&#x2715;</button>
    </div>
    <form id="editForm" method="POST">
      <input type="hidden" name="mode" value="edit">
      <input type="hidden" name="id"   id="editId">
      <div class="form-group">
        <label class="form-label">Judul Tugas</label>
        <input type="text" name="title" id="editTitle" class="form-input" placeholder="Judul tugas" required>
      </div>
      <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" id="editDescription" class="form-textarea" rows="4" placeholder="Tambahkan keterangan..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group half">
          <label class="form-label">Label</label>
          <div class="select-wrap">
            <select name="label" id="editLabel" class="form-select" onchange="cekLabelEdit(this.value)">
              <option value="personal">Personal</option>
              <option value="sekolah">Sekolah</option>
              <option value="kerja">Kerja</option>
              <option value="lainnya">Lainnya</option>
            </select>
            <span class="select-arrow">&#9660;</span>
          </div>
          <div id="editLabelCustomWrap" style="display:none;margin-top:8px;">
            <input type="text" name="label_custom" id="editLabelCustom" class="form-input" placeholder="Tulis label kamu...">
          </div>
        </div>
        <div class="form-group half">
          <label class="form-label">Priority</label>
          <div class="select-wrap">
            <select name="priority" id="editPriority" class="form-select">
              <option value="low">Mudah</option>
              <option value="medium">Sedang</option>
              <option value="high">Susah</option>
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

<!-- ── Water modal: pilih tugas yg ingin diselesaikan ── -->
<div id="waterModalOverlay" onclick="if(event.target===this)closeWaterModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#141f16;border:1.5px solid rgba(76,175,80,.3);border-radius:18px;padding:28px 32px;width:420px;max-width:95vw;max-height:80vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.6);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
      <span style="font-size:1.1rem;font-weight:700;color:#d4f0d8;">💧 Siram Tanaman</span>
      <button onclick="closeWaterModal()" style="background:none;border:none;color:#666;font-size:1.1rem;cursor:pointer;">✕</button>
    </div>
    <p style="font-size:.85rem;color:#6b9e72;margin:0 0 18px;">
      Pilih tugas yang sudah selesai (+10 poin per tugas):
    </p>
    <div id="waterTaskList" style="display:flex;flex-direction:column;gap:8px;">
      <?php
      // Ambil tugas pending user untuk ditampilkan di water modal
      $wq = $conn->prepare("SELECT id_daily_activity, title, label FROM daily_activities WHERE user_id = ? AND status = 'pending' AND (project_id IS NULL OR project_id = 0) ORDER BY created_at DESC LIMIT 20");
      $wq->bind_param('i', $id_user);
      $wq->execute();
      $water_tasks = $wq->get_result();
      if ($water_tasks->num_rows === 0):
      ?>
        <p style="color:#555;font-size:.85rem;text-align:center;padding:20px 0;">Tidak ada tugas pending saat ini.</p>
      <?php else: while ($wt = $water_tasks->fetch_assoc()): ?>
        <form method="POST" onsubmit="handleDone(event,this)">
          <input type="hidden" name="action" value="done_task">
          <input type="hidden" name="task_id" value="<?= $wt['id_daily_activity'] ?>">
          <button type="submit" style="width:100%;text-align:left;background:rgba(0,0,0,.3);border:1px solid rgba(76,175,80,.15);border-radius:10px;padding:12px 16px;color:#c8e6c9;cursor:pointer;display:flex;align-items:center;gap:12px;font-family:inherit;transition:background .2s;"
                  onmouseover="this.style.background='rgba(76,175,80,.12)'" onmouseout="this.style.background='rgba(0,0,0,.3)'">
            <span style="font-size:1.1rem;">✅</span>
            <div>
              <div style="font-size:.9rem;font-weight:600;"><?= htmlspecialchars($wt['title']) ?></div>
              <div style="font-size:.75rem;color:#6b9e72;"><?= htmlspecialchars($wt['label'] ?? 'personal') ?></div>
            </div>
            <span style="margin-left:auto;font-size:.78rem;color:#4CAF50;font-weight:600;">+10 💧</span>
          </button>
        </form>
      <?php endwhile; endif; ?>
    </div>
    <button onclick="closeWaterModal()" style="margin-top:18px;width:100%;background:rgba(0,0,0,.3);border:1px solid #333;border-radius:10px;padding:10px;color:#888;cursor:pointer;font-family:inherit;">Tutup</button>
  </div>
</div>

<!-- ── Toast ── -->
<div id="streakToast">
  <span id="streakToastIcon">🌱</span>
  <span id="streakToastMsg">+10 poin! Tanaman kamu tumbuh!</span>
</div>

<!-- ── Edit modal styles ── -->
<style>
#editModalOverlay {
  position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);
  z-index:9999;display:flex;align-items:center;justify-content:center;animation:fadeIn .18s ease;
}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
#editModalBox {
  background:#1a1a1a;border:1px solid #2e2e2e;border-radius:14px;
  width:620px;max-width:95vw;max-height:90vh;overflow-y:auto;
  padding:28px 32px;box-shadow:0 24px 64px rgba(0,0,0,.6);
  animation:slideUp .22s cubic-bezier(.4,0,.2,1);
}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
#editModalBox::-webkit-scrollbar{width:5px}
#editModalBox::-webkit-scrollbar-track{background:transparent}
#editModalBox::-webkit-scrollbar-thumb{background:#333;border-radius:4px}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
.modal-title{font-size:1.15rem;font-weight:600;color:#f0f0f0;letter-spacing:.01em;}
.modal-close{background:none;border:none;color:#666;font-size:1rem;cursor:pointer;line-height:1;padding:4px 8px;border-radius:6px;transition:background .15s,color .15s;}
.modal-close:hover{background:#2a2a2a;color:#ccc;}
.form-group{margin-bottom:18px;}
.form-group.half{flex:1;min-width:0;}
.form-row{display:flex;gap:16px;}
.form-label{display:block;font-size:.82rem;font-weight:600;color:#ccc;margin-bottom:7px;letter-spacing:.03em;text-transform:uppercase;}
.form-input,.form-select,.form-textarea{width:100%;background:#111;border:1px solid #2e2e2e;border-radius:8px;color:#e8e8e8;font-size:.92rem;padding:10px 14px;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box;font-family:inherit;}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:#4a7c59;box-shadow:0 0 0 3px rgba(74,124,89,.15);}
.form-input::placeholder,.form-textarea::placeholder{color:#444;}
.form-textarea{resize:vertical;min-height:90px;}
.select-wrap{position:relative;}
.form-select{appearance:none;-webkit-appearance:none;cursor:pointer;padding-right:36px;}
.select-arrow{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#555;font-size:.7rem;pointer-events:none;}
input[type="date"].form-input::-webkit-calendar-picker-indicator{filter:invert(.4);cursor:pointer;}
.file-upload-group .file-upload-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.btn-upload{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;background:#2d5a3d;color:#e8f5ec;border:1px solid #3d7a53;border-radius:8px;cursor:pointer;transition:background .15s;}
.btn-upload:hover{background:#3a6e4a;}
.file-name{font-size:.85rem;color:#c9d8c6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;}
.modal-actions{display:flex;gap:12px;margin-top:24px;}
.btn-primary{background:#2d5a3d;color:#e8f5ec;border:1px solid #3d7a53;border-radius:8px;padding:10px 22px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .15s,transform .1s;font-family:inherit;}
.btn-primary:hover{background:#3a6e4a;transform:translateY(-1px);}
.btn-secondary{background:transparent;color:#888;border:1px solid #2e2e2e;border-radius:8px;padding:10px 22px;font-size:.9rem;font-weight:500;cursor:pointer;transition:background .15s,color .15s;font-family:inherit;}
.btn-secondary:hover{background:#222;color:#ccc;}

/* highlight deadlines */
.cal-day.has-deadline { background: #e23b3b; color: #fff; border-radius: 8px; position: relative; }
.cal-day.today { outline: 2px solid #6bcb77; border-radius:8px; }
.deadline-badge { position: absolute; right:6px; top:6px; background: rgba(0,0,0,0.2); color:#fff; font-size:0.7rem; padding:2px 6px; border-radius:10px; }
</style>
<script>
/* ── Kalender (dinamis) ── */
let now   = new Date();
let year  = now.getFullYear();
let month = now.getMonth(); // 0-11
const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

let deadlinesMap = {};

function formatDateYMD(y, m, d) {
  const mm = String(m).padStart(2,'0');
  const dd = String(d).padStart(2,'0');
  return `${y}-${mm}-${dd}`;
}

async function fetchDeadlines(y, m) {
  // API expects month as 1-12
  const yr = parseInt(y,10);
  const mon = parseInt(m,10) + 1;
  try {
    const resp = await fetch('calendar_api.php?year='+yr+'&month='+mon, {credentials: 'same-origin'});
    const data = await resp.json();
    deadlinesMap = data.ok ? data.deadlines : {};
  } catch (e) {
    deadlinesMap = {};
  }
}

function openDateTasks(e, dateStr) {
  const tasks = deadlinesMap[dateStr] || [];
  if (tasks.length === 0) return;
  let lines = tasks.map(t => (t.status === 'done' ? '✓ ' : '') + t.title);
  alert('Tugas pada ' + dateStr + '\n\n' + lines.join('\n'));
}

async function renderCalendar() {
  document.getElementById('calTitle').textContent = bulan[month] + ' ' + year;
  const grid = document.getElementById('calGrid');
  grid.innerHTML = '';
  ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(d => grid.innerHTML += `<div class="cal-day-name">${d}</div>`);
  const firstDay  = new Date(year, month, 1).getDay();
  const totalDays = new Date(year, month + 1, 0).getDate();
  const today     = new Date();
  for (let i = 0; i < firstDay; i++) grid.innerHTML += `<div class="cal-day empty"></div>`;
  for (let d = 1; d <= totalDays; d++) {
    const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
    const dateStr = formatDateYMD(year, month + 1, d);
    const hasDeadline = (deadlinesMap[dateStr] && deadlinesMap[dateStr].length > 0);
    const classes = ['cal-day'];
    if (isToday) classes.push('today');
    if (hasDeadline) classes.push('has-deadline');
    const badge = '';
    grid.innerHTML += `<div class="${classes.join(' ')}" data-date="${dateStr}" onclick="openDateTasks(event,'${dateStr}')"><span class="cal-day-num">${d}</span></div>`;
  }
}

function prevMonth() { if (--month < 0) { month = 11; year--; } loadAndRender(); }
function nextMonth() { if (++month > 11) { month = 0; year++; } loadAndRender(); }

async function loadAndRender() { await fetchDeadlines(year, month); renderCalendar(); }

// initial load
loadAndRender();

// Realtime check: update calendar if day changed (runs every minute)
setInterval(() => {
  const n = new Date();
  if (n.getDate() !== now.getDate() || n.getMonth() !== now.getMonth() || n.getFullYear() !== now.getFullYear()) {
    now = n; year = now.getFullYear(); month = now.getMonth(); loadAndRender();
  }
}, 60 * 1000);

/* ── Panel: Add Task ── */
function openAddTaskPanel()  { document.getElementById('addTaskPanel')?.classList.add('open'); document.getElementById('addTaskOverlay')?.classList.add('open'); }
function closeAddTaskPanel() { document.getElementById('addTaskPanel')?.classList.remove('open'); document.getElementById('addTaskOverlay')?.classList.remove('open'); }
function toggleAddTaskPanel(e) { if(e) e.preventDefault(); const p=document.getElementById('addTaskPanel'); p?.classList.contains('open')?closeAddTaskPanel():openAddTaskPanel(); }
function cekLabelPanel(val)  { document.getElementById('add_task_label_custom').style.display = val==='lainnya'?'block':'none'; }
function updateAddTaskFileName() {
  const input   = document.getElementById('task_file');
  const display = document.getElementById('addTaskFileName');
  if (input && display) {
    display.textContent = input.files.length > 0 ? input.files[0].name : 'Belum ada file dipilih';
  }
}

/* ── Panel: Add Project ── */
function openAddProjectPanel()  { document.getElementById('addProjectPanel')?.classList.add('open'); document.getElementById('addProjectOverlay')?.classList.add('open'); }
function closeAddProjectPanel() { document.getElementById('addProjectPanel')?.classList.remove('open'); document.getElementById('addProjectOverlay')?.classList.remove('open'); }
function toggleAddProjectPanel(e){ if(e) e.preventDefault(); const p=document.getElementById('addProjectPanel'); p?.classList.contains('open')?closeAddProjectPanel():openAddProjectPanel(); }
function openAddProjectFromSidebar(e){ if(e){e.preventDefault();e.stopPropagation();} openAddProjectPanel(); }

/* ── Sidebar project dropdown ── */
function toggleProjectDropdown(e) {
  if(e){e.preventDefault();e.stopPropagation();}
  const dropdown=document.getElementById('projectDropdown'),nav=document.getElementById('projectNav'),chevron=document.getElementById('projectChevron');
  if(!dropdown) return;
  const isOpen=!dropdown.hasAttribute('hidden');
  if(isOpen){dropdown.setAttribute('hidden','');nav.setAttribute('aria-expanded','false');if(chevron)chevron.textContent='chevron_right';}
  else{dropdown.removeAttribute('hidden');nav.setAttribute('aria-expanded','true');if(chevron)chevron.textContent='expand_more';}
}

/* ── Water (Siram) modal ── */
function openWaterModal()  { const m=document.getElementById('waterModalOverlay'); m.style.display='flex'; }
function closeWaterModal() { const m=document.getElementById('waterModalOverlay'); m.style.display='none'; }

/* ── Toast helper ── */
function showStreakToast(msg='🌱 +10 poin! Tanaman kamu tumbuh!') {
  const t=document.getElementById('streakToast');
  document.getElementById('streakToastMsg').textContent=msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 3000);
}

/* ── Handle Done (tangkap submit, tampilkan toast, lalu submit normal) ── */
function handleDone(e, form) {
  e.preventDefault();
  showStreakToast('💧 +10 poin! Tanaman kamu disiram!');
  // Submit setelah animasi toast muncul sedikit
  setTimeout(() => form.submit(), 400);
}

/* ── Delete modal ── */
function openDeleteModal(event, id) {
  event.preventDefault();
  document.getElementById('deleteModal').style.display='flex';
  document.getElementById('confirmDeleteBtn').href='hapus.php?id='+id;
}
function closeDeleteModal() { document.getElementById('deleteModal').style.display='none'; }

/* ── Edit modal ── */
const pilihanDefaultEdit = ['personal','sekolah','kerja','lainnya'];
function cekLabelEdit(val) { document.getElementById('editLabelCustomWrap').style.display=val==='lainnya'?'block':'none'; }
function bukaModalEdit(idTugas) {
  const overlay=document.getElementById('editModalOverlay');
  overlay.style.display='flex';
  document.body.style.overflow='hidden';
  document.getElementById('editForm').reset();
  document.getElementById('editLabelCustomWrap').style.display='none';
  fetch(`edit.php?id=${idTugas}&ajax=1`).then(r=>r.json()).then(data=>{
    document.getElementById('editId').value           = data.id_daily_activity;
    document.getElementById('editTitle').value        = data.title         || '';
    document.getElementById('editDescription').value  = data.description   || '';
    document.getElementById('editPriority').value     = data.priority      || 'medium';
    document.getElementById('editActivityDate').value = data.activity_date || '';
    document.getElementById('editDeadline').value     = data.deadline      || '';
    const labelSel=document.getElementById('editLabel');
    if(pilihanDefaultEdit.includes(data.label)){
      labelSel.value=data.label;
      document.getElementById('editLabelCustomWrap').style.display='none';
    } else {
      labelSel.value='lainnya';
      document.getElementById('editLabelCustom').value=data.label||'';
      document.getElementById('editLabelCustomWrap').style.display='block';
    }
  }).catch(()=>alert('Gagal memuat data tugas.'));
}
function tutupModalEdit() { document.getElementById('editModalOverlay').style.display='none'; document.body.style.overflow=''; }

document.getElementById('editForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd=new FormData(this);
  fetch('edit.php',{method:'POST',body:fd}).then(r=>{
    if(r.redirected||r.ok){ tutupModalEdit(); location.reload(); }
  }).catch(()=>alert('Gagal menyimpan perubahan.'));
});

/* ── Realtime search ── */
function normalizeText(v){return(v||'').toString().toLowerCase().trim();}
function filterTables(query){
  const q=normalizeText(query);
  document.querySelectorAll('table.tugas-table').forEach((table)=>{
    const rows=Array.from(table.querySelectorAll('tbody tr'));
    let visibleCount=0;
    rows.forEach((tr)=>{
      if(tr.getAttribute('data-no-result')==='1') return;
      const match=q===''||normalizeText(tr.textContent).includes(q);
      tr.style.display=match?'':'none';
      if(match) visibleCount++;
    });
    let existing=table.querySelector('tr[data-no-result="1"]');
    if(q!==''&&visibleCount===0){
      if(!existing){
        const tr=document.createElement('tr');tr.setAttribute('data-no-result','1');
        const td=document.createElement('td');td.colSpan=table.querySelectorAll('thead th').length||5;
        td.className='kosong';td.style.padding='14px';td.textContent='Tugas tidak ditemukan.';
        tr.appendChild(td);table.querySelector('tbody')?.appendChild(tr);
      }
    } else { existing?.remove(); }
  });
  document.querySelectorAll('.progress-item').forEach((a)=>{
    a.style.display=(q===''||normalizeText(a.textContent).includes(q))?'':'none';
  });
}
let searchTimer=null;
document.getElementById('sidebarSearchInput')?.addEventListener('input',(e)=>{
  clearTimeout(searchTimer); searchTimer=setTimeout(()=>filterTables(e.target.value),60);
});

/* ── Keyboard shortcuts ── */
document.addEventListener('keydown',(ev)=>{
  if(ev.key==='Escape'){closeAddTaskPanel();closeAddProjectPanel();tutupModalEdit();closeWaterModal();}
  if((ev.key==='Enter'||ev.key===' ')&&ev.target?.id==='projectNav'){ev.preventDefault();toggleProjectDropdown(ev);}
});
</script>
</body>
</html>