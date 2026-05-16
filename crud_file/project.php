<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user  = (int) $_SESSION['id_user'];
$username = $_SESSION['username'] ?? 'User';

// ─── AKSI: Tambah Section ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_section') {
    $project_id   = (int) ($_POST['project_id'] ?? 0);
    $section_name = trim((string) ($_POST['section_name'] ?? ''));

    if ($project_id > 0 && $section_name !== '') {
        // Pastikan project milik user ini
        $chk = $conn->prepare("SELECT id_project FROM projects WHERE id_project = ? AND user_id = ?");
        $chk->bind_param('ii', $project_id, $id_user);
        $chk->execute();

        if ($chk->get_result()->fetch_assoc()) {
            // Cek duplikat section dalam project yang sama
            $dup = $conn->prepare("SELECT id FROM project_sections WHERE project_id = ? AND section_name = ?");
            $dup->bind_param('is', $project_id, $section_name);
            $dup->execute();

            if (!$dup->get_result()->fetch_assoc()) {
                $ins = $conn->prepare("INSERT INTO project_sections (project_id, section_name) VALUES (?, ?)");
                $ins->bind_param('is', $project_id, $section_name);
                $ins->execute();
            }
        }
    }

    header('Location: project.php');
    exit();
}

// ─── AKSI: Tambah Tugas ke Project ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_project_task') {
    $project_id    = (int)    ($_POST['project_id']   ?? 0);
    $title         = trim((string) ($_POST['title']        ?? ''));
    $description   = trim((string) ($_POST['description']  ?? ''));
    $priority      = trim((string) ($_POST['priority']     ?? 'medium'));
    $activity_date = !empty($_POST['activity_date']) ? $_POST['activity_date'] : date('Y-m-d');
    $deadline      = !empty($_POST['deadline'])      ? $_POST['deadline']      : null;
    $section_label = trim((string) ($_POST['section'] ?? 'General'));
    $label         = $section_label !== '' ? $section_label : 'General';

    if ($project_id > 0 && $title !== '') {
        $chk = $conn->prepare("SELECT id_project FROM projects WHERE id_project = ? AND user_id = ?");
        $chk->bind_param('ii', $project_id, $id_user);
        $chk->execute();

        if ($chk->get_result()->fetch_assoc()) {
            $ins = $conn->prepare(
                "INSERT INTO daily_activities
                    (user_id, title, description, status, priority, activity_date, label, deadline, project_id)
                 VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?)"
            );
            $ins->bind_param('issssssi', $id_user, $title, $description, $priority, $activity_date, $label, $deadline, $project_id);
            $ins->execute();
        }
    }

    header('Location: project.php');
    exit();
}

// ─── AKSI: Hapus Section ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_section') {
    $section_id = (int) ($_POST['section_id'] ?? 0);
    $project_id = (int) ($_POST['project_id'] ?? 0);

    if ($section_id > 0 && $project_id > 0) {
        // Pastikan section milik project yang dimiliki user
        $chk = $conn->prepare(
            "SELECT ps.id FROM project_sections ps
             JOIN projects p ON p.id_project = ps.project_id
             WHERE ps.id = ? AND ps.project_id = ? AND p.user_id = ?"
        );
        $chk->bind_param('iii', $section_id, $project_id, $id_user);
        $chk->execute();

        if ($chk->get_result()->fetch_assoc()) {
            $del = $conn->prepare("DELETE FROM project_sections WHERE id = ?");
            $del->bind_param('i', $section_id);
            $del->execute();
        }
    }

    header('Location: project.php');
    exit();
}

// ─── FETCH: Daftar Project ──────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY id_project DESC");
$stmt->bind_param('i', $id_user);
$stmt->execute();
$projects = $stmt->get_result();

// ─── FETCH: Sidebar Projects ────────────────────────────────────────────────
$stmt_sidebar = $conn->prepare("SELECT id_project, nama_project, warna FROM projects WHERE user_id = ? ORDER BY id_project DESC");
$stmt_sidebar->bind_param('i', $id_user);
$stmt_sidebar->execute();
$sidebar_projects = $stmt_sidebar->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Project</title>
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/dashboard.css">
  <link rel="stylesheet" href="../design/settings-panel.css">
  <link rel="stylesheet" href="../design/+tugas.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <style>
    /* ── Hover: Baris utama project ─────────────────────────── */
    .tugas-table tr.main-project-row:hover td {
      background-color: #1a1a1a !important;
      color: #fff !important;
    }

    /* ── Baris dropdown tugas — selalu hitam ────────────────── */
    .project-tasks-row,
    .project-tasks-row:hover {
      background-color: #000 !important;
    }

    /* ── Container nested table ─────────────────────────────── */
    .nested-table-container {
      padding: 10px 40px 25px 40px;
    }

    /* ── Tabel tugas di dalam dropdown ──────────────────────── */
    .nested-table {
      width: 100%;
      border-collapse: collapse;
      background: #111;
      border: 1px solid #333;
      border-radius: 8px;
      overflow: hidden;
    }
    .nested-table th {
      background-color: #222;
      color: #888;
      font-size: 11px;
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #333;
    }
    .nested-table td {
      padding: 12px 10px;
      border-bottom: 1px solid #222;
      color: #fff !important;
      font-size: 13px;
    }
    .nested-table tr:hover td {
      background-color: #1a1a1a !important;
    }

    /* ── Icon toggle ─────────────────────────────────────────── */
    .toggle-icon {
      vertical-align: middle;
      font-size: 20px;
      margin-right: 8px;
      color: #888;
      transition: transform 0.3s, color 0.3s;
    }
    .rotated { transform: rotate(90deg); color: #fff; }

    /* ── Badge section ───────────────────────────────────────── */
    .badge-section {
      background: #333;
      color: #aaa;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 10px;
    }

    /* ── Section group header (dalam dropdown) ───────────────── */
    .section-group-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0 6px 0;
      color: #6bcb77;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      border-bottom: 1px solid #222;
      margin-bottom: 4px;
      cursor: pointer;
      user-select: none;
    }
    .section-group-header:first-child { padding-top: 0; }
    .section-group-header .section-toggle-icon {
      font-size: 16px;
      color: #555;
      transition: transform 0.25s;
    }
    .section-group-header .section-badge-count {
      background: #222;
      color: #777;
      padding: 1px 7px;
      border-radius: 20px;
      font-size: 10px;
      margin-left: auto;
    }
    .section-group-header .section-delete-btn {
      background: none;
      border: none;
      color: #444;
      cursor: pointer;
      font-size: 13px;
      padding: 0 2px;
      transition: color 0.2s;
    }
    .section-group-header .section-delete-btn:hover { color: #e63946; }

    .section-group-body {
      overflow: hidden;
      transition: max-height 0.3s ease;
    }
    .section-group-body.collapsed { display: none; }

    /* ── Sidebar: My Project aktif (bukan My Plan) ───────────── */
    .project-nav-wrap .nav-item.project-nav.active,
    .project-nav-wrap .nav-item.project-nav:focus {
      color:rgb(255, 255, 255) !important;
    }
   
    .sidebar a.nav-item[href="index.php"] { color: inherit; }
    /* My Project nav item hover */
    .project-nav-wrap .nav-item.project-nav:hover {
      color:rgb(255, 255, 255) !important;
    }

    /* ── Panel section ───────────────────────────────────────── */
    .section-list-wrap {
      padding: 0 20px 10px 20px;
    }
    .section-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #222;
      border: 1px solid #333;
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 12px;
      color: #ccc;
      margin: 4px 4px 0 0;
    }
    .section-chip button {
      background: none;
      border: none;
      color: #555;
      cursor: pointer;
      padding: 0;
      font-size: 13px;
      line-height: 1;
    }
    .section-chip button:hover { color: #e63946; }
  </style>
</head>
<body>

<!-- ═══════════════════════════ SIDEBAR ════════════════════════════════════ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <img src="../asset/logo.jpg" alt="Logo" width="42" height="42"style="border-radius:20px;">
    </div>
    <div class="logo-text">Grow<br>Plant</div>
  </div>

  <!-- My Plan — TIDAK aktif saat di halaman project.php -->
  <a href="index.php" class="nav-item">
    <span class="material-symbols-outlined icon">home</span> My Plan
  </a>

  <a href="history.php" class="nav-item"><span class="icon">✔</span> Selesai</a>

  <div class="project-nav-wrap">
    <!-- My Project — AKTIF saat di halaman ini -->
    <div class="nav-item project-nav active"
         id="projectNav"
         role="button"
         tabindex="0"
         aria-expanded="false"
         onclick="toggleProjectDropdown(event)">
      <span class="icon">🗁</span>
      <span class="project-nav-label">My Project</span>
      <span class="project-nav-actions" aria-hidden="true">
        <button type="button" class="project-action-btn"
                title="Tambah project"
                aria-label="Tambah project"
                onclick="openAddProjectFromSidebar(event)">
          <span class="material-symbols-outlined">add</span>
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
    <a href="#" class="nav-item" id="settingTrigger" onclick="toggleSetting(event)">
      <span class="icon">☰</span> Pengaturan
    </a>
  </div>
</aside>

<!-- ═══════════════════════════ MAIN CONTENT ══════════════════════════════ -->
<div class="main">
  <div class="content">
    <div class="topbar"><h1>My Project</h1></div>

    <div class="section">
      <div class="section-header">
        <div class="section-title">Daftar Project</div>
        <a href="#" class="link-red" onclick="toggleAddProjectPanel(event)">+ Add Project</a>
      </div>

      <?php if ($projects->num_rows === 0): ?>
        <div class="kosong">Belum ada project.</div>
      <?php else: ?>
        <table class="tugas-table">
          <thead>
            <tr>
              <th>Project</th>
              <th>Workspace</th>
              <th>Jumlah Tugas</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>

          <?php
          // Re-fetch projects karena pointer sudah bergerak
          $stmt2 = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY id_project DESC");
          $stmt2->bind_param('i', $id_user);
          $stmt2->execute();
          $projects2 = $stmt2->get_result();

          while ($project = $projects2->fetch_assoc()):
            $p_id = (int) $project['id_project'];

            // Ambil semua tugas project ini
            $stmtTasks = $conn->prepare("SELECT * FROM daily_activities WHERE project_id = ? ORDER BY id_daily_activity DESC");
            $stmtTasks->bind_param('i', $p_id);
            $stmtTasks->execute();
            $tasks     = $stmtTasks->get_result();
            $taskCount = $tasks->num_rows;

            // Ambil semua section project ini
            $stmtSec = $conn->prepare("SELECT * FROM project_sections WHERE project_id = ? ORDER BY id ASC");
            $stmtSec->bind_param('i', $p_id);
            $stmtSec->execute();
            $sections    = $stmtSec->get_result();
            $sectionList = [];
            while ($s = $sections->fetch_assoc()) {
                $sectionList[] = $s;
            }

            // Kelompokkan tugas per section
            $tasksBySection = [];
            while ($t = $tasks->fetch_assoc()) {
                $key = $t['label'] ?: 'General';
                $tasksBySection[$key][] = $t;
            }

            // Kumpulkan semua nama section yang ada (dari DB + dari tugas)
            $allSectionNames = ['General'];
            foreach ($sectionList as $s) {
                if (!in_array($s['section_name'], $allSectionNames)) {
                    $allSectionNames[] = $s['section_name'];
                }
            }
            foreach (array_keys($tasksBySection) as $k) {
                if (!in_array($k, $allSectionNames)) {
                    $allSectionNames[] = $k;
                }
            }

            // Workspace text
            $workspaceText = '';
            if (!empty($project['deskripsi']) && str_starts_with($project['deskripsi'], 'Workspace: ')) {
                $workspaceText = substr($project['deskripsi'], 11);
            }
          ?>

          <!-- Baris utama project -->
          <tr class="main-project-row">
            <td class="tugas-name" style="cursor:pointer;" onclick="toggleTasks(<?= $p_id ?>)">
              <span class="material-symbols-outlined toggle-icon" id="icon-<?= $p_id ?>">chevron_right</span>
              <span style="color:<?= htmlspecialchars($project['warna']) ?>; margin-right:5px;">●</span>
              <?= htmlspecialchars($project['nama_project']) ?>
            </td>
            <td class="tugas-desc"><?= htmlspecialchars($workspaceText ?: '-') ?></td>
            <td><strong><?= $taskCount ?></strong> tugas · <span style="color:#555;font-size:12px;"><?= count($sectionList) ?> section</span></td>
            <td class="aksi-col">
              <button class="btn-action btn-task"
                      onclick="openAddTaskForProject(event, <?= $p_id ?>, <?= htmlspecialchars(json_encode($allSectionNames)) ?>)">
                + Tugas
              </button>
              <button class="btn-action btn-section"
                      onclick="openAddSectionForProject(event, <?= $p_id ?>)">
                + Section
              </button>
              <!-- TOMBOL HAPUS (di dalam loop project kamu) -->
<button class="btn-action btn-delete"
  onclick="bukaModal(<?= $p_id ?>)">
  Hapus
</button>


<!-- MODAL (taruh di bawah, JANGAN di dalam loop) -->
<div id="confirmModal" class="modal">
  <div class="modal-content">
    <p>Yakin ingin menghapus project ini ?</p>
    <button onclick="hapusProject()">OK</button>
    <button onclick="tutupModal()">Cancel</button>
  </div>
</div>


<!-- CSS -->
<style>
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.6);
  z-index: 999;
}

.modal-content {
  background: #1e1e1e;
  color: white;
  padding: 20px;
  margin: 15% auto;
  width: 300px;
  border-radius: 12px;
  text-align: center;
}

.modal-content button {
  margin: 10px;
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.modal-content button:first-child {
  background: red;
  color: white;
}

.modal-content button:last-child {
  background: gray;
  color: white;
}
</style>
          

          <!-- Baris dropdown tugas & section -->
          <tr class="project-tasks-row" id="tasks-<?= $p_id ?>" style="display:none;">
            <td colspan="4" class="nested-table-container">

              <?php if ($taskCount === 0 && empty($sectionList)): ?>
                <div style="color:#666;font-style:italic;font-size:13px;">
                  Belum ada tugas di project ini.<br>
                  <small style="color:#444;">Tambah tugas atau buat section untuk mengorganisasi kerja Anda.</small>
                </div>

              <?php else: ?>
                <!-- Tampilkan section yang ada di DB (termasuk kosong) -->
                <?php foreach ($allSectionNames as $secName):
                  $secId = null;
                  foreach ($sectionList as $s) {
                      if ($s['section_name'] === $secName) { $secId = $s['id']; break; }
                  }
                  $tugas   = $tasksBySection[$secName] ?? [];
                  $groupId = 'sec-' . $p_id . '-' . preg_replace('/[^a-z0-9]/i', '_', $secName);
                ?>
                <div class="section-group">
                  <!-- Header section -->
                  <div class="section-group-header" onclick="toggleSectionGroup('<?= $groupId ?>')">
                    <span class="material-symbols-outlined section-toggle-icon" id="icon-<?= $groupId ?>">expand_more</span>
                    <span><?= htmlspecialchars($secName) ?></span>
                    <span class="section-badge-count"><?= count($tugas) ?></span>

                    <?php if ($secName !== 'General' && $secId): ?>
                      <form method="POST" style="margin:0;" onsubmit="return confirm('Hapus section ini?')">
                        <input type="hidden" name="action"     value="delete_section">
                        <input type="hidden" name="section_id" value="<?= $secId ?>">
                        <input type="hidden" name="project_id" value="<?= $p_id ?>">
                        <button type="submit" class="section-delete-btn" title="Hapus section">✕</button>
                      </form>
                    <?php endif; ?>
                  </div>

                  <!-- Body section -->
                  <div class="section-group-body" id="<?= $groupId ?>">
                    <?php if (empty($tugas)): ?>
                      <div style="color:#555;font-size:12px;padding:10px 0;font-style:italic;">
                        Belum ada tugas di section ini.
                      </div>
                    <?php else: ?>
                      <table class="nested-table">
                        <thead>
                          <tr>
                            <th>Nama Tugas</th>
                            <th>Prioritas</th>
                            <th>Deadline</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($tugas as $t): ?>
                          <tr>
                            <td><?= htmlspecialchars($t['title']) ?></td>
                            <td>
                              <span class="badge badge-<?= htmlspecialchars($t['priority']) ?>">
                                <?= ucfirst(htmlspecialchars($t['priority'])) ?>
                              </span>
                            </td>
                            <td style="color:#777;font-size:12px;">
                              <?= $t['deadline'] ? htmlspecialchars($t['deadline']) : '—' ?>
                            </td>
                            <td>
                              <?= $t['status'] === 'done' ? '🟢 Selesai' : '⏳ Pending' ?>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>

              <?php endif; ?>
            </td>
          </tr>

          <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div><!-- .section -->
  </div><!-- .content -->
</div><!-- .main -->

<!-- ═══════════════ INCLUDE SETTING PANEL ══════════════════════════════════ -->
<?php include_once '../includes/setting_panel.php'; ?>

<!-- ═══════════════ PANEL: Tambah Project ══════════════════════════════════ -->
<div class="setting-overlay" id="addProjectOverlay" onclick="closeAddProjectPanel()"></div>
<div class="setting-panel" id="addProjectPanel">
  <div class="sp-header">
    <h2>Tambah Project</h2>
    <button class="sp-close" onclick="closeAddProjectPanel()">✕</button>
  </div>
  <form method="POST" action="tambah_project.php" style="padding:20px;">
    <div class="form-group"><label>Nama Project</label><input type="text" name="nama_project" required></div>
    <div class="form-group"><label>Warna</label><input type="color" name="warna" value="#e63946"></div>
    <div class="form-group"><label>Workspace</label><input type="text" name="workspace"></div>
    <button type="submit" class="btn-simpan" style="width:100%">Simpan</button>
  </form>
</div>

<!-- ═══════════════ PANEL: Tambah Tugas ════════════════════════════════════ -->
<div class="setting-overlay" id="addProjectTaskOverlay" onclick="closeAddProjectTaskPanel()"></div>
<div class="setting-panel" id="addProjectTaskPanel">
  <div class="sp-header">
    <h2>Tambah Tugas</h2>
    <button class="sp-close" onclick="closeAddProjectTaskPanel()">✕</button>
  </div>
  <form method="POST" style="padding:20px;">
    <input type="hidden" name="action"      value="add_project_task">
    <input type="hidden" name="project_id"  id="pt_project_id">
    <div class="form-group">
      <label>Judul Tugas</label>
      <input type="text" name="title" required>
    </div>
    <div class="form-group">
      <label>Section</label>
      <select name="section" id="pt_section_select">
        <option value="General">General</option>
      </select>
    </div>
    <div class="form-group">
      <label>Prioritas</label>
      <select name="priority">
        <option value="low">Low</option>
        <option value="medium" selected>Medium</option>
        <option value="high">High</option>
      </select>
    </div>
    <div class="form-group">
      <label>Deadline</label>
      <input type="date" name="deadline">
    </div>
    <button type="submit" class="btn-simpan" style="width:100%">Tambah Tugas</button>
  </form>
</div>

<!-- ═══════════════ PANEL: Tambah Section ══════════════════════════════════ -->
<div class="setting-overlay" id="addSectionOverlay" onclick="closeAddSectionPanel()"></div>
<div class="setting-panel" id="addSectionPanel">
  <div class="sp-header">
    <h2>Tambah Section</h2>
    <button class="sp-close" onclick="closeAddSectionPanel()">✕</button>
  </div>
  <form method="POST" style="padding:20px;">
    <input type="hidden" name="action"     value="add_section">
    <input type="hidden" name="project_id" id="sec_project_id">

    <div class="form-group">
      <label>Nama Section</label>
      <input type="text" name="section_name" id="sec_name_input"
             placeholder="Contoh: Design, Development, Testing…" required>
    </div>

    <p style="font-size:12px;color:#555;margin-top:-8px;">
      Section membantu mengelompokkan tugas dalam satu project menjadi bagian-bagian yang lebih terorganisir.
    </p>

    <button type="submit" class="btn-simpan" style="width:100%">Buat Section</button>
  </form>
</div>

<!-- ═══════════════ JAVASCRIPT ══════════════════════════════════════════════ -->
<script>
/* ── Toggle dropdown tugas per project ─────────────────────────────────── */
function toggleTasks(projectId) {
  const row  = document.getElementById('tasks-' + projectId);
  const icon = document.getElementById('icon-'  + projectId);
  if (!row) return;

  const isHidden = row.style.display === 'none';
  row.style.display = isHidden ? 'table-row' : 'none';
  icon.classList.toggle('rotated', isHidden);
}

/* ── Toggle collapsed section group ───────────────────────────────────── */
function toggleSectionGroup(groupId) {
  const body = document.getElementById(groupId);
  const icon = document.getElementById('icon-' + groupId);
  if (!body) return;

  body.classList.toggle('collapsed');
  if (body.classList.contains('collapsed')) {
    icon.textContent = 'chevron_right';
  } else {
    icon.textContent = 'expand_more';
  }
}

/* ── Panel: Add Project ─────────────────────────────────────────────── */
function openAddProjectPanel() {
  document.getElementById('addProjectPanel').classList.add('open');
  document.getElementById('addProjectOverlay').classList.add('open');
}
function closeAddProjectPanel() {
  document.getElementById('addProjectPanel').classList.remove('open');
  document.getElementById('addProjectOverlay').classList.remove('open');
}
function toggleAddProjectPanel(e) {
  if (e) e.preventDefault();
  const panel = document.getElementById('addProjectPanel');
  panel.classList.contains('open') ? closeAddProjectPanel() : openAddProjectPanel();
}
function openAddProjectFromSidebar(e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }
  openAddProjectPanel();
}

/* ── Panel: Add Task ────────────────────────────────────────────────── */
function openAddTaskForProject(event, projectId, sectionNames) {
  event.stopPropagation();

  document.getElementById('pt_project_id').value = projectId;

  // Isi dropdown section sesuai section yang ada di project
  const sel = document.getElementById('pt_section_select');
  sel.innerHTML = '';
  const names = Array.isArray(sectionNames) ? sectionNames : ['General'];
  names.forEach(function(name) {
    const opt  = document.createElement('option');
    opt.value  = name;
    opt.text   = name;
    sel.appendChild(opt);
  });

  document.getElementById('addProjectTaskPanel').classList.add('open');
  document.getElementById('addProjectTaskOverlay').classList.add('open');
}
function closeAddProjectTaskPanel() {
  document.getElementById('addProjectTaskPanel').classList.remove('open');
  document.getElementById('addProjectTaskOverlay').classList.remove('open');
}

/* ── Panel: Add Section ─────────────────────────────────────────────── */
function openAddSectionForProject(event, projectId) {
  event.stopPropagation();

  document.getElementById('sec_project_id').value = projectId;
  document.getElementById('sec_name_input').value  = '';

  document.getElementById('addSectionPanel').classList.add('open');
  document.getElementById('addSectionOverlay').classList.add('open');
}
function closeAddSectionPanel() {
  document.getElementById('addSectionPanel').classList.remove('open');
  document.getElementById('addSectionOverlay').classList.remove('open');
}

/* ── Sidebar project dropdown ───────────────────────────────────────── */
function toggleProjectDropdown(e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }

  const dropdown = document.getElementById('projectDropdown');
  const nav      = document.getElementById('projectNav');
  if (!dropdown || !nav) return;

  const isOpen = !dropdown.hasAttribute('hidden');
  if (isOpen) {
    dropdown.setAttribute('hidden', '');
    nav.setAttribute('aria-expanded', 'false');
  } else {
    dropdown.removeAttribute('hidden');
    nav.setAttribute('aria-expanded', 'true');
  }
}

/* ── Delete project ─────────────────────────────────────────────────── */
function bukaModal() {
  document.getElementById("confirmModal").style.display = "block";
}

function tutupModal() {
  document.getElementById("confirmModal").style.display = "none";
}

function hapusProject() {
  // logic hapus di sini
  tutupModal();
}

/* ── Keyboard: Escape menutup semua panel ───────────────────────────── */
document.addEventListener('keydown', function(ev) {
  if (ev.key === 'Escape') {
    closeAddProjectPanel();
    closeAddProjectTaskPanel();
    closeAddSectionPanel();
  }
  if ((ev.key === 'Enter' || ev.key === ' ') && ev.target?.id === 'projectNav') {
    ev.preventDefault();
    toggleProjectDropdown(ev);
  }
});
</script>
</body>
</html>