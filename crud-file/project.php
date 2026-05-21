<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user  = (int) $_SESSION['id_user'];
$username = $_SESSION['username'] ?? 'User';

// Tangkap ID project yang harus dibuka setelah halaman reload
$open_project_id = (int)($_GET['open'] ?? 0);

// ─── AKSI: Ubah Status Tugas (Centang/Selesai) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_task_status') {
    $task_id    = (int) ($_POST['task_id'] ?? 0);
    $project_id = (int) ($_POST['project_id'] ?? 0);
    $new_status = ($_POST['new_status'] === 'done') ? 'done' : 'pending';

    if ($task_id > 0) {
        $chk = $conn->prepare("SELECT id_daily_activity FROM daily_activities WHERE id_daily_activity = ? AND user_id = ?");
        $chk->bind_param('ii', $task_id, $id_user);
        $chk->execute();

        if ($chk->get_result()->fetch_assoc()) {
            $upd = $conn->prepare("UPDATE daily_activities SET status = ? WHERE id_daily_activity = ?");
            $upd->bind_param('si', $new_status, $task_id);
            $upd->execute();
        }
    }
    header("Location: project.php?open=" . $project_id . "#proj-" . $project_id);
    exit();
}

// ─── AKSI: Tambah Section ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_section') {
    $project_id   = (int) ($_POST['project_id'] ?? 0);
    $section_name = trim((string) ($_POST['section_name'] ?? ''));

    if ($project_id > 0 && $section_name !== '') {
        $chk = $conn->prepare("SELECT id_project FROM projects WHERE id_project = ? AND user_id = ?");
        $chk->bind_param('ii', $project_id, $id_user);
        $chk->execute();

        if ($chk->get_result()->fetch_assoc()) {
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
    header("Location: project.php?open=" . $project_id . "#proj-" . $project_id);
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
    header("Location: project.php?open=" . $project_id . "#proj-" . $project_id);
    exit();
}

// ─── AKSI: Hapus Section ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_section') {
    $section_id = (int) ($_POST['section_id'] ?? 0);
    $project_id = (int) ($_POST['project_id'] ?? 0);

    if ($section_id > 0 && $project_id > 0) {
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
    header("Location: project.php?open=" . $project_id . "#proj-" . $project_id);
    exit();
}

// ─── AKSI: Hapus Project ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_project') {
    $delete_project_id = (int) ($_POST['project_id'] ?? 0);

    if ($delete_project_id > 0) {
        $chk = $conn->prepare("SELECT id_project FROM projects WHERE id_project = ? AND user_id = ?");
        $chk->bind_param('ii', $delete_project_id, $id_user);
        $chk->execute();

        if ($chk->get_result()->fetch_assoc()) {
            $del_tasks = $conn->prepare("DELETE FROM daily_activities WHERE project_id = ?");
            $del_tasks->bind_param('i', $delete_project_id);
            $del_tasks->execute();

            $del_sec = $conn->prepare("DELETE FROM project_sections WHERE project_id = ?");
            $del_sec->bind_param('i', $delete_project_id);
            $del_sec->execute();

            $del_proj = $conn->prepare("DELETE FROM projects WHERE id_project = ?");
            $del_proj->bind_param('i', $delete_project_id);
            $del_proj->execute();
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
    /* =========================================================================
       RE-DESIGN WORKSPACE
       ========================================================================= */
    .tugas-table tr.main-project-row {
      border-bottom: 1px solid #2a2a2a;
      transition: background-color 0.2s ease;
    }
    .tugas-table tr.main-project-row:hover td {
      background-color: #262626 !important;
      color: #fff !important;
    }

    .project-tasks-row {
      background-color: #181818 !important; 
      box-shadow: inset 0 8px 10px -10px rgba(0,0,0,0.5);
    }
    .project-tasks-row:hover {
      background-color: #181818 !important; 
    }

    .nested-table-container {
      padding: 20px 40px 30px 40px; 
    }

    .section-group {
      background: #1f1f1f; 
      border: 1px solid #2d2d2d;
      border-radius: 10px;
      margin-bottom: 16px;
      padding: 12px 18px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
    }

    .section-group-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 4px 0 8px 0;
      color: #e0e0e0;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      border-bottom: 1px solid #2d2d2d;
      margin-bottom: 8px;
      cursor: pointer;
      user-select: none;
    }
    .section-group-header .section-toggle-icon {
      font-size: 18px;
      color: #888;
      transition: transform 0.25s, color 0.2s;
    }
    .section-group-header:hover .section-toggle-icon { color: #fff; }
    .section-group-header .section-badge-count {
      background: #333;
      color: #bbb;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 11px;
      margin-left: auto;
      font-weight: normal;
    }

    .nested-table { width: 100%; border-collapse: collapse; background: transparent; }
    .nested-table th {
      background-color: transparent;
      color: #888;
      font-size: 11px;
      padding: 10px 8px;
      text-align: left;
      border-bottom: 1px solid #333;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .nested-table td {
      padding: 12px 8px;
      border-bottom: 1px solid #2a2a2a;
      color: #ccc !important;
      font-size: 13px;
    }
    .nested-table tr:last-child td { border-bottom: none; }
    .nested-table tr:hover td { background-color: #262626 !important; border-radius: 6px; }

    .btn-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: transparent;
      border: 1px solid transparent;
      cursor: pointer;
      font-size: 12px;
      padding: 4px 10px;
      border-radius: 6px;
      transition: all 0.2s ease;
      font-weight: 500;
    }
    .btn-status.pending { color: #aaa; border-color: #444; }
    .btn-status.pending:hover { background: #333; color: #fff; border-color: #6bcb77; }
    .btn-status.done { color: #6bcb77; border-color: #2d5a36; background: rgba(107, 203, 119, 0.1); }
    .btn-status.done:hover { background: rgba(107, 203, 119, 0.2); }

    .toggle-icon {
      vertical-align: middle;
      font-size: 20px;
      margin-right: 8px;
      color: #888;
      transition: transform 0.3s, color 0.3s;
    }
    .rotated { transform: rotate(90deg); color: #fff; }

    .section-group-body { overflow: hidden; transition: max-height 0.3s ease; }
    .section-group-body.collapsed { display: none; }
    
    .section-group-header .section-delete-btn {
      background: none; border: none; color: #555; cursor: pointer;
      font-size: 14px; padding: 0 4px; transition: color 0.2s;
    }
    .section-group-header .section-delete-btn:hover { color: #e63946; }

    .project-nav-wrap .nav-item.project-nav.active,
    .project-nav-wrap .nav-item.project-nav:focus { color: rgb(255, 255, 255) !important; }
    .sidebar a.nav-item[href="index.php"] { color: inherit; }
    .project-nav-wrap .nav-item.project-nav:hover { color: rgb(255, 255, 255) !important; }

    .modal {
      display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.6); z-index: 999;
    }
    .modal-content {
      background: #1e1e1e; color: white; padding: 24px; margin: 15% auto;
      width: 350px; border-radius: 12px; text-align: center; border: 1px solid #333;
    }
    .modal-content button { margin: 10px; padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><img src="../asset/logo.jpg" alt="Logo" width="42" height="42"style="border-radius:20px;"></div>
    <div class="logo-text">Grow<br>Plan</div>
  </div>

  <a href="index.php" class="nav-item"><span class="material-symbols-outlined icon">home</span> My Plan</a>
  <a href="history.php" class="nav-item"><span class="icon">✔</span> Selesai</a>

  <div class="project-nav-wrap">
    <div class="nav-item project-nav active" id="projectNav" role="button" tabindex="0" aria-expanded="false" onclick="toggleProjectDropdown(event)">
      <span class="icon">🗁</span>
      <span class="project-nav-label">My Project</span>
      <span class="project-nav-actions" aria-hidden="true">
        <button type="button" class="project-action-btn" title="Tambah project" aria-label="Tambah project" onclick="openAddProjectFromSidebar(event)">
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
          $stmt2 = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY id_project DESC");
          $stmt2->bind_param('i', $id_user);
          $stmt2->execute();
          $projects2 = $stmt2->get_result();

          while ($project = $projects2->fetch_assoc()):
            $p_id = (int) $project['id_project'];
            
            // Cek apakah project ini yang seharusnya terbuka
            $isOpen = ($p_id === $open_project_id);

            // Tugas project
            $stmtTasks = $conn->prepare("SELECT * FROM daily_activities WHERE project_id = ? ORDER BY id_daily_activity DESC");
            $stmtTasks->bind_param('i', $p_id);
            $stmtTasks->execute();
            $tasks = $stmtTasks->get_result();
            $taskCount = $tasks->num_rows;

            // Section project
            $stmtSec = $conn->prepare("SELECT * FROM project_sections WHERE project_id = ? ORDER BY id ASC");
            $stmtSec->bind_param('i', $p_id);
            $stmtSec->execute();
            $sections = $stmtSec->get_result();
            $sectionList = [];
            while ($s = $sections->fetch_assoc()) { $sectionList[] = $s; }

            // Kelompokkan tugas per section
            $tasksBySection = [];
            while ($t = $tasks->fetch_assoc()) {
                $key = $t['label'] ?: 'General';
                $tasksBySection[$key][] = $t;
            }

            // Nama section
            $allSectionNames = ['General'];
            foreach ($sectionList as $s) {
                if (!in_array($s['section_name'], $allSectionNames)) { $allSectionNames[] = $s['section_name']; }
            }
            foreach (array_keys($tasksBySection) as $k) {
                if (!in_array($k, $allSectionNames)) { $allSectionNames[] = $k; }
            }

            $workspaceText = '';
            if (!empty($project['deskripsi']) && str_starts_with($project['deskripsi'], 'Workspace: ')) {
                $workspaceText = substr($project['deskripsi'], 11);
            }
          ?>

          <tr class="main-project-row" id="proj-<?= $p_id ?>">
            <td class="tugas-name" style="cursor:pointer;" onclick="toggleTasks(<?= $p_id ?>)">
              <span class="material-symbols-outlined toggle-icon <?= $isOpen ? 'rotated' : '' ?>" id="icon-<?= $p_id ?>">chevron_right</span>
              <span style="color:<?= htmlspecialchars($project['warna']) ?>; margin-right:5px; font-size:16px;">●</span>
              <?= htmlspecialchars($project['nama_project']) ?>
            </td>
            <td class="tugas-desc"><?= htmlspecialchars($workspaceText ?: '-') ?></td>
            <td><strong><?= $taskCount ?></strong> tugas · <span style="color:#777;font-size:12px;"><?= count($sectionList) ?> section</span></td>
            <td class="aksi-col">
              <button class="btn-action btn-task" onclick="openAddTaskForProject(event, <?= $p_id ?>, <?= htmlspecialchars(json_encode($allSectionNames)) ?>)">+ Tugas</button>
              <button class="btn-action btn-section" onclick="openAddSectionForProject(event, <?= $p_id ?>)">+ Section</button>
              <button class="btn-action btn-delete" onclick="bukaModal(<?= $p_id ?>)">Hapus</button>
            </td>
          </tr>

          <tr class="project-tasks-row" id="tasks-<?= $p_id ?>" style="display: <?= $isOpen ? 'table-row' : 'none' ?>;">
            <td colspan="4" class="nested-table-container">

              <?php if ($taskCount === 0 && empty($sectionList)): ?>
                <div style="color:#777; font-style:italic; font-size:13px; text-align:center; padding: 20px 0;">
                  Belum ada tugas di project ini.<br>
                  <small style="color:#555;">Tambah tugas atau buat section untuk mulai mengorganisasi pekerjaanmu.</small>
                </div>

              <?php else: ?>
                <?php foreach ($allSectionNames as $secName):
                  $secId = null;
                  foreach ($sectionList as $s) {
                      if ($s['section_name'] === $secName) { $secId = $s['id']; break; }
                  }
                  $tugas   = $tasksBySection[$secName] ?? [];
                  $groupId = 'sec-' . $p_id . '-' . preg_replace('/[^a-z0-9]/i', '_', $secName);
                ?>
                <div class="section-group">
                  <div class="section-group-header" onclick="toggleSectionGroup('<?= $groupId ?>')">
                    <span class="material-symbols-outlined section-toggle-icon" id="icon-<?= $groupId ?>">expand_more</span>
                    <span style="color: <?= htmlspecialchars($project['warna']) ?>; font-size:10px; margin-right:-4px;">⬤</span> 
                    <span><?= htmlspecialchars($secName) ?></span>
                    <span class="section-badge-count"><?= count($tugas) ?></span>

                    <?php if ($secName !== 'General' && $secId): ?>
                      <form method="POST" style="margin:0;" onsubmit="return confirm('Hapus section ini?')">
                        <input type="hidden" name="action" value="delete_section">
                        <input type="hidden" name="section_id" value="<?= $secId ?>">
                        <input type="hidden" name="project_id" value="<?= $p_id ?>">
                        <button type="submit" class="section-delete-btn" title="Hapus section"><span class="material-symbols-outlined" style="font-size:16px;">close</span></button>
                      </form>
                    <?php endif; ?>
                  </div>

                  <div class="section-group-body" id="<?= $groupId ?>">
                    <?php if (empty($tugas)): ?>
                      <div style="color:#555; font-size:12px; padding:8px 0 8px 30px; font-style:italic;">
                        Belum ada tugas di section ini.
                      </div>
                    <?php else: ?>
                      <table class="nested-table">
                        <thead>
                          <tr>
                            <th style="padding-left: 30px;">Nama Tugas</th>
                            <th>Prioritas</th>
                            <th>Deadline</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($tugas as $t): ?>
                          <tr>
                            <td style="padding-left: 30px; <?= $t['status'] === 'done' ? 'text-decoration: line-through; color: #666 !important;' : '' ?>">
                              <?= htmlspecialchars($t['title']) ?>
                            </td>
                            <td>
                              <span class="badge badge-<?= htmlspecialchars($t['priority']) ?>">
                                <?= ucfirst(htmlspecialchars($t['priority'])) ?>
                              </span>
                            </td>
                            <td style="color:#888; font-size:12px;">
                              <?= $t['deadline'] ? htmlspecialchars($t['deadline']) : '—' ?>
                            </td>
                            <td>
                              <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="toggle_task_status">
                                <input type="hidden" name="project_id" value="<?= $p_id ?>">
                                <input type="hidden" name="task_id" value="<?= $t['id_daily_activity'] ?>">
                                
                                <?php if ($t['status'] === 'done'): ?>
                                  <input type="hidden" name="new_status" value="pending">
                                  <button type="submit" class="btn-status done" title="Batalkan status selesai">
                                    <span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> Selesai
                                  </button>
                                <?php else: ?>
                                  <input type="hidden" name="new_status" value="done">
                                  <button type="submit" class="btn-status pending" title="Tandai telah selesai">
                                    <span class="material-symbols-outlined" style="font-size:16px;">radio_button_unchecked</span> Pending
                                  </button>
                                <?php endif; ?>
                              </form>
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
    </div>
  </div>
</div>

<?php include_once '../includes/setting_panel.php'; ?>

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
        <option value="low">Mudah</option>
        <option value="medium" selected>Sedang</option>
        <option value="high">Susah</option>
      </select>
    </div>
    <div class="form-group">
      <label>Deadline</label>
      <input type="date" name="deadline">
    </div>
    <button type="submit" class="btn-simpan" style="width:100%">Tambah Tugas</button>
  </form>
</div>

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

<div id="confirmModal" class="modal">
  <div class="modal-content">
    <p style="margin-bottom:20px; font-size:15px;">Yakin ingin menghapus project ini beserta seluruh section dan tugasnya?</p>
    
    <form method="POST" style="display: inline-block;">
      <input type="hidden" name="action" value="delete_project">
      <input type="hidden" name="project_id" id="modal_delete_project_id" value="">
      <button type="submit" style="background: #e63946; color: white;">Hapus Permanen</button>
    </form>
    
    <button type="button" onclick="tutupModal()" style="background: #444; color: white;">Batal</button>
  </div>
</div>

<script src="project.js"></script>
</body>
</html>