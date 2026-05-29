<?php
require_once __DIR__ . '/../includes/config.php';
startSession();
if (isLoggedIn()) { header('Location: ../index.php'); exit; }
$title = 'Masuk';
$csrf  = getCsrfToken();
include __DIR__ . '/../includes/header.php';
?>
<div style="min-height:100vh;display:flex;flex-direction:column;justify-content:center;padding:1.5rem 1rem;background:linear-gradient(160deg,var(--primary-xlight) 0%,#fff 60%)">

  <div style="max-width:400px;width:100%;margin:0 auto">

    <!-- Logo -->
    <div style="text-align:center;margin-bottom:2rem">
      <div style="width:72px;height:72px;background:var(--primary);border-radius:20px;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:2.5rem;box-shadow:0 8px 24px rgba(22,163,74,.3)">🗑️</div>
      <h1 style="font-size:1.6rem;margin-bottom:.25rem">Selamat Datang!</h1>
      <p class="text-muted text-small">Masuk ke akun LaporSampahLiar Anda</p>
    </div>

    <div class="card">
      <div class="card-body">
        <div id="alertBox"></div>

        <form id="formLogin" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <div class="form-group">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-icon">📧</span>
              <input type="email" id="email" name="email" class="form-control" placeholder="contoh@email.com" autocomplete="email">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" style="display:flex;justify-content:space-between">
              Password
              <a href="lupa-password.php" style="font-weight:400;font-size:.8rem">Lupa password?</a>
            </label>
            <div class="input-group input-group-right">
              <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" autocomplete="current-password">
              <span class="input-icon-right" onclick="togglePwd('password', this)">👁️</span>
            </div>
          </div>

          <button type="submit" id="loginBtn" class="btn btn-primary btn-lg btn-block mt-2">
            Masuk
          </button>
        </form>

        <p style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:var(--text-muted)">
          Belum punya akun? <a href="register.php" style="font-weight:700">Daftar Sekarang</a>
        </p>
      </div>
    </div>

    <!-- Demo Accounts -->
    <div style="margin-top:1.25rem;background:#fff;border-radius:var(--radius-lg);padding:1rem;border:1px solid var(--border)">
      <p style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:.75rem">Akun Demo</p>
      <div style="display:grid;gap:.5rem">
        <?php foreach([['Admin','admin@laporsampahliar.id'],['Petugas','petugas1@laporsampahliar.id'],['Warga','warga@demo.id']] as [$role,$em]): ?>
        <button onclick="fillDemo('<?=$em?>')" style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.5rem .875rem;font-size:.8rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700"><?=$role?></span>
          <span style="color:var(--text-muted)"><?=$em?></span>
        </button>
        <?php endforeach; ?>
        <p style="font-size:.72rem;color:var(--text-light);text-align:center">Password semua akun: <code>password</code></p>
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
function fillDemo(email) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = 'password';
}

document.getElementById('formLogin').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  if (!Validate.form('formLogin', { email: ['required','email'], password: ['required'] })) return;

  btn.disabled = true; btn.innerHTML = '<div class="spinner"></div> Memproses...';

  const fd = new FormData(e.target);
  const res = await fetch('../api/auth.php?action=login', { method:'POST', body: fd });
  const data = await res.json();

  if (data.success) {
    Toast.show('Berhasil masuk!', 'Mengarahkan ke dashboard...', 'success');
    setTimeout(() => window.location = data.redirect || '../index.php', 1000);
  } else {
    document.getElementById('alertBox').innerHTML = `<div class="alert alert-error"><span class="alert-icon">❌</span>${data.message}</div>`;
    btn.disabled = false; btn.innerHTML = 'Masuk';
  }
});
</script>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
