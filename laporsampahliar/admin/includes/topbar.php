<?php
/**
 * admin/includes/topbar.php
 */
$db = getDB();
$nc = (int)$db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id=? AND is_read=0")->execute([$_SESSION['user_id']]);
$ncStmt = $db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id=? AND is_read=0");
$ncStmt->execute([$_SESSION['user_id']]);
$nc = (int)$ncStmt->fetchColumn();
?>
<header class="admin-topbar">
  <span class="menu-toggle" onclick="Sidebar.toggle()">☰</span>
  <div>
    <h2 style="font-size:1rem;font-weight:700"><?= $title ?? 'Admin Panel' ?></h2>
    <p style="font-size:.7rem;color:var(--text-muted)"><?= date('l, d F Y') ?></p>
  </div>
  <div style="margin-left:auto;display:flex;align-items:center;gap:1rem">
    <a href="../pages/notifikasi.php" style="position:relative;font-size:1.2rem;color:var(--text)">🔔
      <?php if ($nc > 0): ?>
      <span style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.6rem;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700"><?= min($nc,99) ?></span>
      <?php endif; ?>
    </a>
    <div style="display:flex;align-items:center;gap:.5rem">
      <div style="width:34px;height:34px;background:var(--primary-xlight);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;border:2px solid var(--primary)">👤</div>
      <div>
        <div style="font-size:.8rem;font-weight:700"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></div>
        <div style="font-size:.65rem;color:var(--text-muted)"><?= ucfirst($_SESSION['role'] ?? '') ?></div>
      </div>
    </div>
  </div>
</header>
<div id="toast-container"></div>