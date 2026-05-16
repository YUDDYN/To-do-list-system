<?php
// Komponen panel pengaturan.
// Expect: $username (opsional). Kalau tidak ada, ambil dari session.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$sp_username = $username ?? ($_SESSION['username'] ?? 'User');
?>

<!-- OVERLAY -->
<div class="setting-overlay" id="settingOverlay" onclick="closeSetting()"></div>

<!-- SETTING PANEL -->
<div class="setting-panel" id="settingPanel" aria-hidden="true">
  <div class="sp-header">
    <h2>Pengaturan</h2>
    <button type="button" class="sp-close" onclick="closeSetting()" aria-label="Tutup">✕</button>
  </div>

  <div class="sp-label">Profile</div>
  <div class="sp-profile">
    <div class="sp-avatar">👤</div>
    <div class="sp-profile-info">
      <div class="sp-profile-name"><?= htmlspecialchars($sp_username) ?></div>
      <div class="sp-profile-role">Pengguna aktif</div>
    </div>
    <a href="#" class="sp-edit-btn" onclick="event.preventDefault()">Edit</a>
  </div>

  <div class="sp-label">Notifikasi</div>
  <div class="sp-row">
    <div class="sp-row-icon">
      <span class="material-symbols-outlined">notifications</span>
    </div>
    <div class="sp-row-text">
      <div class="sp-row-title">Notifikasi</div>
      <div class="sp-row-sub">Pengingat tugas harian</div>
    </div>
    <label class="sp-toggle" title="Notifikasi">
      <input type="checkbox" id="toggleNotif" checked onchange="handleNotif(this)">
      <span class="sp-toggle-slider"></span>
    </label>
  </div>

  <div class="sp-row">
    <div class="sp-row-icon">
      <span class="material-symbols-outlined">alarm</span>
    </div>
    <div class="sp-row-text">
      <div class="sp-row-title">Pengingat Deadline</div>
      <div class="sp-row-sub">Notifikasi sebelum deadline</div>
    </div>
    <label class="sp-toggle" title="Pengingat deadline">
      <input type="checkbox" id="toggleDeadline" checked onchange="handleDeadline(this)">
      <span class="sp-toggle-slider"></span>
    </label>
  </div>

  <div class="sp-divider"></div>

  <div class="sp-label">Tampilan</div>
  <div class="sp-theme-row">
    <div class="sp-row-icon">
      <span class="material-symbols-outlined">contrast</span>
    </div>
    <div class="sp-row-text">
      <div class="sp-row-title">Mode Tema</div>
    </div>
    <div class="sp-theme-options">
      <button type="button" class="theme-btn" id="btnDark" onclick="setTheme('dark')">🌙 Gelap</button>
      <button type="button" class="theme-btn" id="btnLight" onclick="setTheme('light')">☀️ Terang</button>
    </div>
  </div>

  <div class="sp-divider"></div>

  <div class="sp-label">About</div>
  <div class="sp-about">
    <div class="sp-about-box">
      <div class="sp-about-app">My Plants To Do</div>
      <div class="sp-about-ver">Versi 1.0.0</div>
      <div class="sp-about-desc">Aplikasi manajemen tugas harian untuk membantu produktivitas.</div>
    </div>
  </div>

  <div class="sp-logout">
    <button class="sp-logout-btn" onclick="openLogout()">
      <span class="material-symbols-outlined">logout</span>
      Logout
    </button>
  </div>

</div>

<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Konfirmasi Logout</h3>
    <p>Yakin ingin logout?</p>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeLogout()">Batal</button>
      <button class="btn-logout" onclick="confirmLogout()">Logout</button>
    </div>
  </div>
</div>

<script>
  function openSetting() {
    const panel = document.getElementById('settingPanel');
    const overlay = document.getElementById('settingOverlay');
    if (!panel || !overlay) return;
    panel.classList.add('open');
    overlay.classList.add('open');
    panel.setAttribute('aria-hidden', 'false');
  }

  function closeSetting() {
    const panel = document.getElementById('settingPanel');
    const overlay = document.getElementById('settingOverlay');
    if (!panel || !overlay) return;
    panel.classList.remove('open');
    overlay.classList.remove('open');
    panel.setAttribute('aria-hidden', 'true');
  }

  function toggleSetting(e) {
    if (e) e.preventDefault();
    const panel = document.getElementById('settingPanel');
    if (!panel) return;
    panel.classList.contains('open') ? closeSetting() : openSetting();
  }

  // ESC untuk menutup panel
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') closeSetting();
  });

  function setTheme(mode) {
    document.documentElement.setAttribute('data-theme', mode);
    localStorage.setItem('theme', mode);
    const darkBtn = document.getElementById('btnDark');
    const lightBtn = document.getElementById('btnLight');
    if (darkBtn) darkBtn.classList.toggle('active', mode === 'dark');
    if (lightBtn) lightBtn.classList.toggle('active', mode === 'light');
  }

  function handleNotif(el) {
    localStorage.setItem('notif', el && el.checked ? '1' : '0');
  }

  function handleDeadline(el) {
    localStorage.setItem('deadline_notif', el && el.checked ? '1' : '0');
  }

  (function initSettingPanel() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);

    const notif = document.getElementById('toggleNotif');
    const deadline = document.getElementById('toggleDeadline');
    if (notif) notif.checked = localStorage.getItem('notif') !== '0';
    if (deadline) deadline.checked = localStorage.getItem('deadline_notif') !== '0';
  })();

  function openLogout() {
  document.getElementById("logoutModal").style.display = "flex";
}

function closeLogout() {
  document.getElementById("logoutModal").style.display = "none";
}

function confirmLogout() {
  window.location.href = "../auth/logout.php";
}
</script>

