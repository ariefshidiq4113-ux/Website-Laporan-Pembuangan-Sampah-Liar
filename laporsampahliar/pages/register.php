<?php
require_once __DIR__ . '/../includes/config.php';
startSession();
if (isLoggedIn()) { header('Location: ../index.php'); exit; }
$title = 'Daftar Akun';
$csrf  = getCsrfToken();
include __DIR__ . '/../includes/header.php';
?>
<div style="min-height:100vh;padding:1.5rem 1rem 5rem;background:linear-gradient(160deg,var(--primary-xlight) 0%,#fff 60%)">
<div style="max-width:420px;width:100%;margin:0 auto">

  <div style="text-align:center;margin-bottom:1.75rem">
    <div style="width:60px;height:60px;background:var(--primary);border-radius:16px;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:2rem">🌱</div>
    <h1 style="font-size:1.5rem">Daftar Akun</h1>
    <p class="text-muted text-small">Bergabunglah untuk melapor dan memantau lingkungan</p>
  </div>

  <div class="card">
    <div class="card-body">
      <div id="alertBox"></div>
      <form id="formRegister" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-group">
          <label class="form-label">Nama Lengkap <span class="required">*</span></label>
          <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Nama sesuai KTP">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-control" placeholder="email@domain.com">
          </div>
          <div class="form-group">
            <label class="form-label">No. HP <span class="required">*</span></label>
            <input type="tel" id="no_hp" name="no_hp" class="form-control" placeholder="08xx">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">NIK (Opsional)</label>
          <input type="text" id="nik" name="nik" class="form-control" placeholder="16 digit NIK" maxlength="16">
        </div>

        <div class="form-group">
          <label class="form-label">Alamat</label>
          <textarea id="alamat" name="alamat" class="form-control" rows="2" placeholder="Alamat tempat tinggal"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Password <span class="required">*</span></label>
          <div class="input-group input-group-right">
            <input type="password" id="password" name="password" class="form-control" placeholder="Min. 8 karakter">
            <span class="input-icon-right" onclick="togglePwd('password',this)">👁️</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Konfirmasi Password <span class="required">*</span></label>
          <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Ulangi password">
        </div>

        <label style="display:flex;align-items:flex-start;gap:.75rem;cursor:pointer;margin-bottom:1.25rem">
          <input type="checkbox" id="setuju" name="setuju" style="width:18px;height:18px;accent-color:var(--primary);flex-shrink:0;margin-top:.1rem">
          <span style="font-size:.85rem;color:var(--text-muted)">Saya menyetujui <a href="#">syarat & ketentuan</a> dan <a href="#">kebijakan privasi</a></span>
        </label>

        <button type="submit" id="regBtn" class="btn btn-primary btn-lg btn-block">
          🌱 Daftar Sekarang
        </button>
      </form>

      <p style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:var(--text-muted)">
        Sudah punya akun? <a href="login.php" style="font-weight:700">Masuk</a>
      </p>
    </div>
  </div>
</div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
}

document.getElementById('formRegister').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('regBtn');
  const alertBox = document.getElementById('alertBox');

  if (!Validate.form('formRegister', {
    nama_lengkap: ['required','min:3'],
    email:        ['required','email'],
    no_hp:        ['required','phone'],
    password:     ['required','min:8'],
  })) return;

  if (document.getElementById('password').value !== document.getElementById('password_confirm').value) {
    alertBox.innerHTML = '<div class="alert alert-error"><span class="alert-icon">❌</span>Password tidak cocok</div>';
    return;
  }
  if (!document.getElementById('setuju').checked) {
    alertBox.innerHTML = '<div class="alert alert-warning"><span class="alert-icon">⚠️</span>Centang persetujuan terlebih dahulu</div>';
    return;
  }

  btn.disabled = true; btn.innerHTML = '<div class="spinner"></div> Mendaftar...';
  alertBox.innerHTML = '';

  const fd = new FormData(e.target);
  const res = await fetch('../api/auth.php?action=register', { method:'POST', body: fd });
  const data = await res.json();

  if (data.success) {
    alertBox.innerHTML = '<div class="alert alert-success"><span class="alert-icon">✅</span>' + data.message + '</div>';
    setTimeout(() => window.location = 'login.php', 2000);
  } else {
    alertBox.innerHTML = `<div class="alert alert-error"><span class="alert-icon">❌</span>${data.message}</div>`;
    btn.disabled = false; btn.innerHTML = '🌱 Daftar Sekarang';
  }
});
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
