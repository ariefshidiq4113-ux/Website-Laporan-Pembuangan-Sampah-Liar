<?php
/**
 * admin/includes/sidebar.php
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$navItems = [
  ['dashboard.php', '📊', 'Dashboard'],
  ['laporan.php',   '📋', 'Laporan'],
  ['peta.php',      '🗺️', 'Peta Laporan'],
  ['petugas.php',   '👷', 'Petugas'],
  ['pengguna.php',  '👥', 'Pengguna'],
  ['laporan-grafik.php','📈','Grafik & Statistik'],
  ['export.php',    '📤', 'Export Data'],
  ['pengaturan.php','⚙️', 'Pengaturan'],
];
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🗑️</div>
    <span><?= APP_NAME ?></span>
  </div>
  <nav>
    <?php foreach($navItems as [$href,$icon,$label]): ?>
    <a href="<?= $href ?>" class="<?= $currentPage===$href?'active':'' ?>">
      <span class="nav-ic"><?= $icon ?></span> <?= $label ?>
    </a>
    <?php endforeach; ?>
    <a href="../pages/logout.php" >
      <span class="nav-ic">🚪</span> Keluar
    </a>
  </nav>
  <div style="margin-top:auto;padding:1rem 1.5rem;border-top:1px solid rgba(255,255,255,.1)">
    <div style="color:rgba(255,255,255,.5);font-size:.72rem;text-transform:uppercase;font-weight:700;margin-bottom:.4rem">Login sebagai</div>
    <div style="color:#fff;font-weight:700;font-size:.875rem">👤 <?= htmlspecialchars($_SESSION['nama'] ?? '') ?></div>
    <div style="color:rgba(255,255,255,.5);font-size:.72rem"><?= ucfirst($_SESSION['role'] ?? '') ?></div>
  </div>
</aside>