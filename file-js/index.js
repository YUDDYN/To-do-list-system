/* JavaScript moved from crud/index.php */
let now = new Date();
let year = now.getFullYear();
let month = now.getMonth(); // 0-11
const bulan = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
];
let deadlinesMap = {};

function formatDateYMD(y, m, d) {
  const mm = String(m).padStart(2, "0");
  const dd = String(d).padStart(2, "0");
  return `${y}-${mm}-${dd}`;
}

async function fetchDeadlines(y, m) {
  const yr = parseInt(y, 10);
  const mon = parseInt(m, 10) + 1;
  try {
    const resp = await fetch(`calendar_api.php?year=${yr}&month=${mon}`, {
      credentials: "same-origin",
    });
    const data = await resp.json();
    deadlinesMap = data.ok ? data.deadlines : {};
  } catch (e) {
    deadlinesMap = {};
  }
}

function openDateTasks(e, dateStr) {
  const tasks = deadlinesMap[dateStr] || [];
  if (tasks.length === 0) return;
  const lines = tasks.map((t) => (t.status === "done" ? "✓ " : "") + t.title);
  alert("Tugas pada " + dateStr + "\n\n" + lines.join("\n"));
}

async function renderCalendar() {
  document.getElementById("calTitle").textContent = bulan[month] + " " + year;
  const grid = document.getElementById("calGrid");
  grid.innerHTML = "";
  ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"].forEach((d) => {
    grid.innerHTML += `<div class="cal-day-name">${d}</div>`;
  });
  const firstDay = new Date(year, month, 1).getDay();
  const totalDays = new Date(year, month + 1, 0).getDate();
  const today = new Date();
  for (let i = 0; i < firstDay; i++) {
    grid.innerHTML += `<div class="cal-day empty"></div>`;
  }
  for (let d = 1; d <= totalDays; d++) {
    const isToday =
      d === today.getDate() &&
      month === today.getMonth() &&
      year === today.getFullYear();
    const dateStr = formatDateYMD(year, month + 1, d);
    const hasDeadline =
      deadlinesMap[dateStr] && deadlinesMap[dateStr].length > 0;
    const classes = ["cal-day"];
    if (isToday) classes.push("today");
    if (hasDeadline) classes.push("has-deadline");
    grid.innerHTML += `<div class="${classes.join(" ")}" data-date="${dateStr}" onclick="openDateTasks(event,'${dateStr}')"><span class="cal-day-num">${d}</span></div>`;
  }
}

function prevMonth() {
  if (--month < 0) {
    month = 11;
    year--;
  }
  loadAndRender();
}

function nextMonth() {
  if (++month > 11) {
    month = 0;
    year++;
  }
  loadAndRender();
}

async function loadAndRender() {
  await fetchDeadlines(year, month);
  renderCalendar();
}

function openAddTaskPanel() {
  document.getElementById("addTaskPanel")?.classList.add("open");
  document.getElementById("addTaskOverlay")?.classList.add("open");
}

function closeAddTaskPanel() {
  document.getElementById("addTaskPanel")?.classList.remove("open");
  document.getElementById("addTaskOverlay")?.classList.remove("open");
}

function toggleAddTaskPanel(e) {
  if (e) e.preventDefault();
  const p = document.getElementById("addTaskPanel");
  p?.classList.contains("open") ? closeAddTaskPanel() : openAddTaskPanel();
}

function cekLabelPanel(val) {
  document.getElementById("add_task_label_custom").style.display =
    val === "lainnya" ? "block" : "none";
}

function updateAddTaskFileName() {
  const input = document.getElementById("task_file");
  const display = document.getElementById("addTaskFileName");
  if (input && display) {
    display.textContent =
      input.files.length > 0 ? input.files[0].name : "Belum ada file dipilih";
  }
}

function openAddProjectPanel() {
  document.getElementById("addProjectPanel")?.classList.add("open");
  document.getElementById("addProjectOverlay")?.classList.add("open");
}

function closeAddProjectPanel() {
  document.getElementById("addProjectPanel")?.classList.remove("open");
  document.getElementById("addProjectOverlay")?.classList.remove("open");
}

function toggleAddProjectPanel(e) {
  if (e) e.preventDefault();
  const p = document.getElementById("addProjectPanel");
  p?.classList.contains("open")
    ? closeAddProjectPanel()
    : openAddProjectPanel();
}

function openAddProjectFromSidebar(e) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
  }
  openAddProjectPanel();
}

function toggleProjectDropdown(e) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
  }
  const dropdown = document.getElementById("projectDropdown");
  const nav = document.getElementById("projectNav");
  const chevron = document.getElementById("projectChevron");
  if (!dropdown) return;
  const isOpen = !dropdown.hasAttribute("hidden");
  if (isOpen) {
    dropdown.setAttribute("hidden", "");
    nav?.setAttribute("aria-expanded", "false");
    if (chevron) chevron.textContent = "chevron_right";
  } else {
    dropdown.removeAttribute("hidden");
    nav?.setAttribute("aria-expanded", "true");
    if (chevron) chevron.textContent = "expand_more";
  }
}

function openWaterModal() {
  const m = document.getElementById("waterModalOverlay");
  if (m) m.style.display = "flex";
}

function closeWaterModal() {
  const m = document.getElementById("waterModalOverlay");
  if (m) m.style.display = "none";
}

function showStreakToast(msg = "🌱 +10 poin! Tanaman kamu tumbuh!") {
  const t = document.getElementById("streakToast");
  const msgEl = document.getElementById("streakToastMsg");
  if (!t || !msgEl) return;
  msgEl.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 3000);
}

function handleDone(e, form) {
  e.preventDefault();
  showStreakToast("💧 +10 poin! Tanaman kamu disiram!");
  setTimeout(() => form.submit(), 400);
}

function openDeleteModal(event, id) {
  event.preventDefault();
  const modal = document.getElementById("deleteModal");
  const confirmBtn = document.getElementById("confirmDeleteBtn");
  if (modal) modal.style.display = "flex";
  if (confirmBtn) confirmBtn.href = `hapus.php?id=${id}`;
}

function closeDeleteModal() {
  document.getElementById("deleteModal")?.style.display = "none";
}

const pilihanDefaultEdit = ["personal", "Sekolah", "kerja", "lainnya"];

function cekLabelEdit(val) {
  const wrap = document.getElementById("editLabelCustomWrap");
  if (wrap) wrap.style.display = val === "lainnya" ? "block" : "none";
}

function bukaModalEdit(idTugas) {
  const overlay = document.getElementById("editModalOverlay");
  const editForm = document.getElementById("editForm");
  const editLabelCustomWrap = document.getElementById("editLabelCustomWrap");
  if (overlay) overlay.style.display = "flex";
  document.body.style.overflow = "hidden";
  editForm?.reset();
  if (editLabelCustomWrap) editLabelCustomWrap.style.display = "none";
  fetch(`edit.php?id=${idTugas}&ajax=1`)
    .then((r) => r.json())
    .then((data) => {
      document.getElementById("editId").value = data.id_daily_activity;
      document.getElementById("editTitle").value = data.title || "";
      document.getElementById("editDescription").value = data.description || "";
      document.getElementById("editPriority").value = data.priority || "medium";
      document.getElementById("editActivityDate").value =
        data.activity_date || "";
      document.getElementById("editDeadline").value = data.deadline || "";
      const labelSel = document.getElementById("editLabel");
      if (labelSel) {
        if (pilihanDefaultEdit.includes(data.label)) {
          labelSel.value = data.label;
          if (editLabelCustomWrap) editLabelCustomWrap.style.display = "none";
        } else {
          labelSel.value = "lainnya";
          const editLabelCustom = document.getElementById("editLabelCustom");
          if (editLabelCustom) editLabelCustom.value = data.label || "";
          if (editLabelCustomWrap) editLabelCustomWrap.style.display = "block";
        }
      }
    })
    .catch(() => alert("Gagal memuat data tugas."));
}

function tutupModalEdit() {
  document.getElementById("editModalOverlay")?.style.display = "none";
  document.body.style.overflow = "";
}

function normalizeText(v) {
  return (v || "").toString().toLowerCase().trim();
}

function filterTables(query) {
  const q = normalizeText(query);
  document.querySelectorAll("table.tugas-table").forEach((table) => {
    const rows = Array.from(table.querySelectorAll("tbody tr"));
    let visibleCount = 0;
    rows.forEach((tr) => {
      if (tr.getAttribute("data-no-result") === "1") return;
      const match = q === "" || normalizeText(tr.textContent).includes(q);
      tr.style.display = match ? "" : "none";
      if (match) visibleCount++;
    });
    const existing = table.querySelector('tr[data-no-result="1"]');
    if (q !== "" && visibleCount === 0) {
      if (!existing) {
        const tr = document.createElement("tr");
        tr.setAttribute("data-no-result", "1");
        const td = document.createElement("td");
        td.colSpan = table.querySelectorAll("thead th").length || 5;
        td.className = "kosong";
        td.style.padding = "14px";
        td.textContent = "Tugas tidak ditemukan.";
        tr.appendChild(td);
        table.querySelector("tbody")?.appendChild(tr);
      }
    } else {
      existing?.remove();
    }
  });
  document.querySelectorAll(".progress-item").forEach((a) => {
    a.style.display =
      q === "" || normalizeText(a.textContent).includes(q) ? "" : "none";
  });
}

let searchTimer = null;

document.addEventListener("DOMContentLoaded", () => {
  loadAndRender();

  setInterval(() => {
    const n = new Date();
    if (
      n.getDate() !== now.getDate() ||
      n.getMonth() !== now.getMonth() ||
      n.getFullYear() !== now.getFullYear()
    ) {
      now = n;
      year = now.getFullYear();
      month = now.getMonth();
      loadAndRender();
    }
  }, 60 * 1000);

  const editForm = document.getElementById("editForm");
  if (editForm) {
    editForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fetch("edit.php", { method: "POST", body: fd })
        .then((r) => {
          if (r.redirected || r.ok) {
            tutupModalEdit();
            location.reload();
          }
        })
        .catch(() => alert("Gagal menyimpan perubahan."));
    });
  }

  const searchInput = document.getElementById("sidebarSearchInput");
  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => filterTables(e.target.value), 60);
    });
  }

  document.addEventListener("keydown", (ev) => {
    if (ev.key === "Escape") {
      closeAddTaskPanel();
      closeAddProjectPanel();
      tutupModalEdit();
      closeWaterModal();
    }
    if (
      (ev.key === "Enter" || ev.key === " ") &&
      ev.target?.id === "projectNav"
    ) {
      ev.preventDefault();
      toggleProjectDropdown(ev);
    }
  });
});
