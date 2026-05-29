<?php
/**
 * includes/footer.php
 * Global footer + bottom navigation
 * Param: $activePage (string) - 'home','laporan','buat','riwayat','profil'
 */
$active = $activePage ?? 'home';
$isLog  = isLoggedIn();
?>

<!-- Bottom Navigation (Mobile-first) -->
<nav class="bottom-nav">
  <a href="<?= APP_URL ?>/index.php" class="<?= $active==='home'?'active':'' ?>">
    <span class="nav-icon">🏠</span>Beranda
  </a>
  <a href="<?= APP_URL ?>/pages/peta.php" class="<?= $active==='peta'?'active':'' ?>">
    <span class="nav-icon">🗺️</span>Peta
  </a>
  <?php if ($isLog): ?>
  <a href="<?= APP_URL ?>/pages/buat-laporan.php" class="<?= $active==='buat'?'active':'' ?>"
     style="<?= $active==='buat'?'':'color:var(--primary)' ?>">
    <span class="nav-icon" style="font-size:1.6rem;<?= $active==='buat'?'':'filter:drop-shadow(0 2px 6px rgba(22,163,74,.5))' ?>">➕</span>Lapor
  </a>
  <a href="<?= APP_URL ?>/pages/riwayat.php" class="<?= $active==='riwayat'?'active':'' ?>">
    <span class="nav-icon">📋</span>Riwayat
  </a>
  <a href="<?= APP_URL ?>/pages/profil.php" class="<?= $active==='profil'?'active':'' ?>">
    <span class="nav-icon">👤</span>Profil
  </a>
  <?php else: ?>
  <a href="<?= APP_URL ?>/pages/login.php" class="">
    <span class="nav-icon">➕</span>Lapor
  </a>
  <a href="<?= APP_URL ?>/pages/semua-laporan.php" class="<?= $active==='riwayat'?'active':'' ?>">
    <span class="nav-icon">📋</span>Laporan
  </a>
  <a href="<?= APP_URL ?>/pages/login.php" class="<?= $active==='profil'?'active':'' ?>">
    <span class="nav-icon">👤</span>Masuk
  </a>
  <?php endif; ?>
</nav>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?php if (isset($extraJS)): echo $extraJS; endif; ?>
</body>
</html>
