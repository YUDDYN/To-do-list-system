<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: lp.php');
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
        $ins = $conn->prepare(
            "INSERT INTO daily_activities (user_id, title, description, priority, activity_date, deadline, label)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param('issssss', $id_user, $title, $description, $priority, $activity_date, $deadline, $label);
        $ins->execute();
    }

    header('Location: history.php');
    exit();
}

// ─── FETCH DATA ──────────────────────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT * FROM daily_activities WHERE user_id = ? AND status = 'done' ORDER BY created_at DESC"
);
$stmt->bind_param('i', $id_user);
$stmt->execute();
$result = $stmt->get_result();

// Stats
$all      = $result->fetch_all(MYSQLI_ASSOC);
$total    = count($all);
$high     = count(array_filter($all, fn($r) => $r['priority'] === 'high'));
$thisWeek = count(array_filter($all, fn($r) => strtotime($r['activity_date']) >= strtotime('-7 days')));

// Chart data: 7 days
$chart_data_dates = [];
$chart_data_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data_dates[] = $date;
    $chart_data_counts[$date] = 0;
}

$start_date_chart = date('Y-m-d', strtotime("-6 days"));
$stmt_chart = $conn->prepare(
    "SELECT activity_date, COUNT(*) as count 
     FROM daily_activities 
     WHERE user_id = ? AND status = 'done' AND activity_date >= ? 
     GROUP BY activity_date"
);
$stmt_chart->bind_param('is', $id_user, $start_date_chart);
$stmt_chart->execute();
$res_chart = $stmt_chart->get_result();
while ($row = $res_chart->fetch_assoc()) {
    $d = $row['activity_date'];
    if (isset($chart_data_counts[$d])) {
        $chart_data_counts[$d] = (int)$row['count'];
    }
}

$chart_labels_js = [];
foreach ($chart_data_dates as $d) {
    $chart_labels_js[] = date('d M', strtotime($d));
}
$chart_values_js = array_values($chart_data_counts);

// Sidebar projects
$stmt_sidebar = $conn->prepare(
    "SELECT id_project, nama_project, warna FROM projects WHERE user_id = ? ORDER BY id_project DESC"
);
$stmt_sidebar->bind_param('i', $id_user);
$stmt_sidebar->execute();
$sidebar_projects = $stmt_sidebar->get_result();
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tugas Selesai - Grow plan</title>
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/dashboard.css">
  <link rel="stylesheet" href="../design/settings-panel.css">
  <link rel="stylesheet" href="../design/+tugas.css">
  <link rel="stylesheet" href="../design/history.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    (function() {
      const t = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
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

  <!-- My Plan — tidak aktif di halaman ini -->
  <a href="index.php" class="nav-item">
    <span class="material-symbols-outlined icon">home</span> My Plan
  </a>

  <!-- Selesai — AKTIF di halaman ini -->
  <a href="history.php" class="nav-item active">
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
<main class="main" style="flex-direction:column;padding:28px;">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="page-title">Tugas Selesai</div>
      <div class="page-subtitle">Riwayat semua tugas yang telah diselesaikan</div>
    </div>
    <a href="index.php" class="back-btn">← Kembali ke Dashboard</a>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">Total Selesai</div>
      <div class="stat-value green"><?= $total ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Minggu Ini</div>
      <div class="stat-value blue"><?= $thisWeek ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Prioritas Tinggi</div>
      <div class="stat-value orange"><?= $high ?></div>
    </div>
  </div>

  <!-- Chart Container -->
  <div class="chart-container">
    <div class="chart-header">
      <span class="chart-title">Statistik Tugas (7 Hari Terakhir)</span>
    </div>
    <div class="chart-wrapper">
      <canvas id="historyChart"></canvas>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="filter-bar">
    <span class="filter-label">Filter:</span>
    <button class="filter-chip active" type="button" data-filter="all">Semua</button>
    <button class="filter-chip" type="button" data-filter="today">Hari ini</button>
    <button class="filter-chip" type="button" data-filter="week">Minggu ini</button>
    <button class="filter-chip" type="button" data-filter="month">Bulan ini</button>

    <div style="margin-left:auto;display:flex;gap:10px;align-items:center;">
      <span class="filter-label" style="margin:0;">Prioritas:</span>
      <select id="priorityFilter" class="filter-chip" style="padding:8px 10px;">
        <option value="all" selected>Semua</option>
        <option value="low">Rendah</option>
        <option value="medium">Sedang</option>
        <option value="high">Tinggi</option>
      </select>
    </div>
  </div>

  <!-- Table Card -->
  <div class="table-card">
    <div class="table-card-header">
      <span class="table-card-title">Riwayat Tugas</span>
      <span class="table-count"><span id="historyCount"><?= $total ?></span> tugas</span>
    </div>

    <?php if ($total === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">🌱</div>
        <div class="empty-title">Belum ada tugas selesai</div>
        <div class="empty-sub">Tugas yang kamu tandai selesai akan muncul di sini.</div>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Tugas</th>
            <th>Label</th>
            <th>Prioritas</th>
            <th>Tanggal</th>
            <th>Deadline</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="historyTbody">
          <?php $no = 1; foreach ($all as $tugas): ?>
          <tr data-date="<?= htmlspecialchars($tugas['activity_date'] ?? '') ?>"
              data-priority="<?= htmlspecialchars(strtolower($tugas['priority'] ?? '')) ?>"
              style="animation-delay:<?= $no * 0.04 ?>s">
            <td class="td-no"><?= $no++ ?></td>
            <td>
              <div class="task-title"><?= htmlspecialchars($tugas['title']) ?></div>
              <?php if (!empty($tugas['description'])): ?>
                <div class="task-desc"><?= htmlspecialchars($tugas['description']) ?></div>
              <?php endif; ?>
            </td>
            <td><span class="badge"><?= htmlspecialchars($tugas['label'] ?: '—') ?></span></td>
            <td>
              <?php
                $prio  = strtolower($tugas['priority'] ?? '');
                $pLabel = match($prio) {
                  'high'   => 'Tinggi',
                  'medium' => 'Sedang',
                  'low'    => 'Rendah',
                  default  => ucfirst($prio ?: '—')
                };
              ?>
              <span class="priority">
                <span class="dot <?= $prio ?>"></span>
                <?= $pLabel ?>
              </span>
            </td>
            <td class="td-date">
              <?= $tugas['activity_date'] ? date('d M Y', strtotime($tugas['activity_date'])) : '—' ?>
            </td>
            <td class="td-date">
              <?= $tugas['deadline'] ? date('d M Y', strtotime($tugas['deadline'])) : '—' ?>
            </td>
            <td><div class="check-icon">✓</div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</main>

<script>
/* ── Panel functions ─────────────────────────────────── */
function openAddTaskPanel() {
  document.getElementById('addTaskPanel')?.classList.add('open');
  document.getElementById('addTaskOverlay')?.classList.add('open');
}
function closeAddTaskPanel() {
  document.getElementById('addTaskPanel')?.classList.remove('open');
  document.getElementById('addTaskOverlay')?.classList.remove('open');
}
function toggleAddTaskPanel(e) {
  if (e) e.preventDefault();
  const p = document.getElementById('addTaskPanel');
  p?.classList.contains('open') ? closeAddTaskPanel() : openAddTaskPanel();
}
function cekLabelPanel(val) {
  document.getElementById('add_task_label_custom').style.display = val === 'lainnya' ? 'block' : 'none';
}

function openAddProjectPanel() {
  document.getElementById('addProjectPanel')?.classList.add('open');
  document.getElementById('addProjectOverlay')?.classList.add('open');
}
function closeAddProjectPanel() {
  document.getElementById('addProjectPanel')?.classList.remove('open');
  document.getElementById('addProjectOverlay')?.classList.remove('open');
}
function openAddProjectFromSidebar(e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }
  openAddProjectPanel();
}

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

/* ── Filter history ──────────────────────────────────── */
function toYmd(d) {
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}

function applyHistoryFilters() {
  const tbody      = document.getElementById('historyTbody');
  if (!tbody) return;

  const activeChip = document.querySelector('.filter-chip.active[data-filter]');
  const timeFilter = activeChip?.getAttribute('data-filter') || 'all';
  const prioFilter = (document.getElementById('priorityFilter')?.value || 'all').toLowerCase();

  const today    = new Date();
  const todayYmd = toYmd(today);
  const weekAgo  = new Date(today);
  weekAgo.setDate(today.getDate() - 7);

  let visible = 0;
  Array.from(tbody.querySelectorAll('tr')).forEach((row) => {
    const rowDate = (row.getAttribute('data-date') || '').trim();
    const rowPrio = (row.getAttribute('data-priority') || '').trim();

    let okTime = true;
    if (timeFilter === 'today') {
      okTime = rowDate === todayYmd;
    } else if (timeFilter === 'week') {
      const t = rowDate ? new Date(rowDate + 'T00:00:00').getTime() : NaN;
      okTime = Number.isFinite(t) && t >= weekAgo.getTime() && t <= today.getTime();
    } else if (timeFilter === 'month') {
      const d = rowDate ? new Date(rowDate + 'T00:00:00') : null;
      okTime = !!d && d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth();
    }

    const okPrio = prioFilter === 'all' || rowPrio === prioFilter;
    const show   = okTime && okPrio;
    row.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  const countEl = document.getElementById('historyCount');
  if (countEl) countEl.textContent = String(visible);
}

document.querySelectorAll('.filter-chip[data-filter]').forEach((chip) => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('.filter-chip[data-filter]').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    applyHistoryFilters();
  });
});
document.getElementById('priorityFilter')?.addEventListener('change', applyHistoryFilters);
applyHistoryFilters();

/* ── Chart.js ────────────────────────────────────────── */
const ctxChart = document.getElementById('historyChart')?.getContext('2d');
if (ctxChart) {
  const gradient = ctxChart.createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, 'rgba(76, 175, 80, 0.4)');
  gradient.addColorStop(1, 'rgba(76, 175, 80, 0)');

  new Chart(ctxChart, {
    type: 'line',
    data: {
      labels: <?= json_encode($chart_labels_js) ?>,
      datasets: [{
        label: 'Tugas Selesai',
        data: <?= json_encode($chart_values_js) ?>,
        borderColor: '#4caf50',
        backgroundColor: gradient,
        borderWidth: 2,
        pointBackgroundColor: '#1a1a1a',
        pointBorderColor: '#4caf50',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#272626',
          titleColor: '#ffffff',
          bodyColor: '#a0a0a0',
          borderColor: 'rgba(255,255,255,0.08)',
          borderWidth: 1,
          padding: 10,
          displayColors: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1, color: '#888888' },
          grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }
        },
        x: {
          ticks: { color: '#888888' },
          grid: { display: false, drawBorder: false }
        }
      }
    }
  });
}

/* ── Keyboard ────────────────────────────────────────── */
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
</script>
</body>
</html>