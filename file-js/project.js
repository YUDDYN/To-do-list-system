function toggleTasks(projectId) {
  const row  = document.getElementById('tasks-' + projectId);
  const icon = document.getElementById('icon-'  + projectId);
  if (!row) return;

  const isHidden = row.style.display === 'none';
  row.style.display = isHidden ? 'table-row' : 'none';
  icon.classList.toggle('rotated', isHidden);
}

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

function openAddTaskForProject(event, projectId, sectionNames) {
  event.stopPropagation();
  document.getElementById('pt_project_id').value = projectId;

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

function bukaModal(projectId) {
  document.getElementById("modal_delete_project_id").value = projectId;
  document.getElementById("confirmModal").style.display = "block";
}

function tutupModal() {
  document.getElementById("confirmModal").style.display = "none";
}

document.addEventListener('keydown', function(ev) {
  if (ev.key === 'Escape') {
    closeAddProjectPanel();
    closeAddProjectTaskPanel();
    closeAddSectionPanel();
    tutupModal();
  }
  if ((ev.key === 'Enter' || ev.key === ' ') && ev.target?.id === 'projectNav') {
    ev.preventDefault();
    toggleProjectDropdown(ev);
  }
});