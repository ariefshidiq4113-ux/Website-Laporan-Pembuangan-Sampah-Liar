<?php
/**
 * includes/header.php
 * Global HTML head & navbar
 * Params: $title (string), $activePage (string)
 */
require_once __DIR__ . '/config.php';
startSession();
$appName = APP_NAME;
$pageTitle = isset($title) ? "$title — $appName" : $appName;
$isLogged  = isLoggedIn();
$userRole  = $_SESSION['role'] ?? 'guest';
$userName  = $_SESSION['nama'] ?? '';
$notifCount = 0;
if ($isLogged) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id=? AND is_read=0");
        $stmt->execute([$_SESSION['user_id']]);
        $notifCount = (int)$stmt->fetchColumn();
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="theme-color" content="#16a34a">
  <meta name="description" content="Platform pelaporan sampah liar untuk masyarakat">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
  <!-- Styles -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <!-- Leaflet Maps -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <a href="<?= APP_URL ?>/index.php" class="navbar-brand">
    <div class="brand-icon">🗑️</div>
    <span><?= $appName ?></span>
  </a>
  <div class="navbar-right">
    <?php if ($isLogged): ?>
      <a href="<?= APP_URL ?>/pages/notifikasi.php" class="btn btn-ghost btn-sm btn-icon" style="position:relative">
        🔔
        <?php if ($notifCount > 0): ?>
          <span style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.6rem;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700"><?= $notifCount ?></span>
        <?php endif; ?>
      </a>
      <?php if (isAdmin() || isPetugas()): ?>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-primary btn-sm">Dashboard</a>
      <?php endif; ?>
    <?php else: ?>
      <a href="<?= APP_URL ?>/pages/login.php" class="btn btn-outline btn-sm">Masuk</a>
      <a href="<?= APP_URL ?>/pages/register.php" class="btn btn-primary btn-sm">Daftar</a>
    <?php endif; ?>
  </div>
</nav>

<div id="toast-container"></div>
