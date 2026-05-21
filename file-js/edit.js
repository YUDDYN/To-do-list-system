function cekLabel(val) {
    document.getElementById('label_custom').style.display = (val === 'lainnya') ? 'block' : 'none';
  }

  function previewFileName(input) {
    const preview = document.getElementById('filePreviewName');
    if (input.files && input.files[0]) {
      preview.textContent = "File Terpilih: " + input.files[0].name;
      preview.style.display = "block";
    } else {
      preview.style.display = "none";
    }
  }

  document.getElementById('editForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.textContent = 'Menyimpan...';
    btn.style.opacity = '0.7';
    btn.disabled = true;
  });