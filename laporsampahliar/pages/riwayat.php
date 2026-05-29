<?php
// pages/riwayat.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$title = 'Riwayat Laporan';
$activePage = 'riwayat';

$db = getDB();
$userId = $_SESSION['user_id'];
$status = sanitize($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where  = "WHERE l.user_id = ?";
$params = [$userId];
if ($status) { $where .= " AND l.status = ?"; $params[] = $status; }

$total = $db->prepare("SELECT COUNT(*) FROM laporan l $where");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $db->prepare("
  SELECT l.*, (SELECT path_file FROM foto_laporan WHERE laporan_id=l.id AND is_thumbnail=1 LIMIT 1) AS thumb
  FROM laporan l $where ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Counts by status
$countStmt = $db->prepare("SELECT status, COUNT(*) as c FROM laporan WHERE user_id=? GROUP BY status");
$countStmt->execute([$userId]);
$counts = [];
foreach ($countStmt->fetchAll() as $row) $counts[$row['status']] = $row['c'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <div class="page-header">
    <h1 class="page-title">📋 Riwayat Laporan</h1>
    <p class="page-subtitle">Pantau status laporan Anda</p>
  </div>

  <!-- Filter -->
  <div style="padding:0 1rem 1rem;overflow-x:auto">
    <div class="filter-chips">
      <a href="riwayat.php" class="chip <?= !$status?'active':'' ?>">Semua (<?= array_sum($counts) ?>)</a>
      <?php foreach(['menunggu'=>'⏳','diverifikasi'=>'✅','diproses'=>'🔧','selesai'=>'🎉','ditolak'=>'❌'] as $s=>$ic): ?>
      <a href="riwayat.php?status=<?=$s?>" class="chip <?= $status===$s?'active':'' ?>">
        <?= $ic ?> <?= ucfirst($s) ?> (<?= $counts[$s]??0 ?>)
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="laporan-list">
    <?php if (empty($laporan)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>Belum ada laporan</h3>
      <p>Anda belum membuat laporan dengan status ini</p>
      <a href="buat-laporan.php" class="btn btn-primary mt-2">📸 Buat Laporan</a>
    </div>
    <?php else: foreach($laporan as $l):
      $badge = getStatusBadge($l['status']);
      $icons = ['TPS_ILEGAL'=>'🗑️','SAMPAH_JALAN'=>'🛣️','SUNGAI'=>'💧','LAHAN_KOSONG'=>'🌿','FASILITAS_UMUM'=>'🏛️','LAINNYA'=>'📍'];
    ?>
    <div class="laporan-card" onclick="window.location='detail-laporan.php?id=<?=$l['id']?>'">
      <div class="card-top">
        <?php if ($l['thumb']): ?>
          <img class="card-thumb" src="<?= UPLOAD_URL . 'laporan/' . basename($l['thumb']) ?>" alt="">
        <?php else: ?>
          <div class="card-thumb-placeholder"><?= $icons[$l['kategori']]??'📍' ?></div>
        <?php endif; ?>
        <div class="card-meta">
          <div style="font-size:.7rem;color:var(--text-muted);font-weight:700;margin-bottom:.2rem"><?= $l['kode_laporan'] ?></div>
          <div class="card-title"><?= htmlspecialchars($l['judul']) ?></div>
          <div class="card-loc">📍 <?= htmlspecialchars($l['kecamatan'].', '.$l['kota']) ?></div>
          <div style="margin-top:.4rem">
            <span class="badge <?= $badge['class'] ?>"><?= $badge['icon'] ?> <?= $badge['label'] ?></span>
          </div>
        </div>
      </div>
      <div class="card-bottom">
        <span class="card-time">🕐 <?= timeAgo($l['created_at']) ?></span>
        <span style="font-size:.78rem;color:var(--primary);font-weight:600">Lihat Detail →</span>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:center;gap:.5rem;padding:1.5rem 1rem">
    <?php for($p=1;$p<=$totalPages;$p++): ?>
    <a href="?status=<?=$status?>&page=<?=$p?>"
       style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.875rem;font-weight:700;<?= $p===$page?'background:var(--primary);color:#fff':'background:#fff;color:var(--text);border:1px solid var(--border)' ?>">
       <?=$p?>
    </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
