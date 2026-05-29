<?php
require_once __DIR__ . '/../includes/config.php';
startSession();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: semua-laporan.php'); exit; }

$db = getDB();
$stmt = $db->prepare("
  SELECT l.*, u.nama_lengkap, u.foto_profil, u.no_hp
  FROM laporan l JOIN users u ON l.user_id=u.id WHERE l.id=?
");
$stmt->execute([$id]);
$l = $stmt->fetch();
if (!$l) { header('Location: semua-laporan.php'); exit; }

// Foto
$fotoStmt = $db->prepare("SELECT * FROM foto_laporan WHERE laporan_id=? ORDER BY is_thumbnail DESC");
$fotoStmt->execute([$id]);
$fotos = $fotoStmt->fetchAll();

// Tracking
$trackStmt = $db->prepare("
  SELECT t.*, u.nama_lengkap as petugas_nama
  FROM tracking_laporan t LEFT JOIN users u ON t.diubah_oleh=u.id
  WHERE t.laporan_id=? ORDER BY t.created_at ASC
");
$trackStmt->execute([$id]);
$tracking = $trackStmt->fetchAll();

// Komentar
$komStmt = $db->prepare("
  SELECT k.*, u.nama_lengkap, u.foto_profil, u.role
  FROM komentar k JOIN users u ON k.user_id=u.id
  WHERE k.laporan_id=? ORDER BY k.created_at ASC
");
$komStmt->execute([$id]);
$komentar = $komStmt->fetchAll();

$badge  = getStatusBadge($l['status']);
$title  = htmlspecialchars($l['judul']);
$activePage = 'riwayat';
include __DIR__ . '/../includes/header.php';

$statusOrder = ['menunggu'=>1,'diverifikasi'=>2,'diproses'=>3,'selesai'=>4,'ditolak'=>4];
$currentStep = $statusOrder[$l['status']] ?? 1;
?>

<div class="page-wrapper">

  <!-- Header -->
  <div style="background:var(--primary);padding:1rem;display:flex;align-items:center;gap:.75rem">
    <a href="javascript:history.back()" style="color:#fff;font-size:1.2rem">←</a>
    <div style="flex:1;min-width:0">
      <div style="color:rgba(255,255,255,.75);font-size:.75rem"><?= htmlspecialchars($l['kode_laporan']) ?></div>
      <h2 style="color:#fff;font-size:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $title ?></h2>
    </div>
    <span class="badge <?= $badge['class'] ?>"><?= $badge['icon'] ?> <?= $badge['label'] ?></span>
  </div>

  <!-- Progress Tracker -->
  <div style="background:#fff;padding:1.25rem 1rem;border-bottom:1px solid var(--border)">
    <div style="display:flex;justify-content:space-between;position:relative;margin-bottom:.75rem">
      <div style="position:absolute;top:14px;left:10%;right:10%;height:2px;background:var(--border);z-index:0"></div>
      <div style="position:absolute;top:14px;left:10%;height:2px;background:var(--primary);z-index:0;transition:width .5s;width:<?= [1=>0,2=>33,3=>67,4=>100][$currentStep] ?? 0 ?>%"></div>
      <?php foreach([['⏳','Menunggu',1],['✅','Verifikasi',2],['🔧','Diproses',3],['🎉','Selesai',4]] as [$ic,$lbl,$step]): ?>
      <div style="text-align:center;z-index:1;flex:1">
        <div style="width:28px;height:28px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:.85rem;border:2px solid <?= $currentStep>=$step?'var(--primary)':'var(--border)' ?>;background:<?= $currentStep>=$step?'var(--primary)':'#fff' ?>">
          <?= $currentStep>=$step?'✓':$step ?>
        </div>
        <div style="font-size:.65rem;color:<?= $currentStep>=$step?'var(--primary)':'var(--text-muted)' ?>;margin-top:.3rem;font-weight:<?= $currentStep>=$step?'700':'400' ?>"><?=$lbl?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Photo Carousel -->
  <?php if (!empty($fotos)): ?>
  <div style="overflow-x:auto;display:flex;gap:.5rem;padding:1rem;scroll-snap-type:x mandatory">
    <?php foreach($fotos as $foto): ?>
    <img src="<?= UPLOAD_URL . 'laporan/' . basename($foto['path_file']) ?>"
         style="height:200px;width:auto;border-radius:var(--radius-lg);flex-shrink:0;object-fit:cover;scroll-snap-align:start;cursor:pointer"
         onclick="window.open(this.src,'_blank')" alt="Foto laporan">
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="height:120px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:3rem">🗑️</div>
  <?php endif; ?>

  <div style="padding:0 1rem">

    <!-- Info Cards -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin:1rem 0">
      <div style="background:var(--primary-xlight);border-radius:var(--radius-lg);padding:1rem;border:1px solid var(--primary-light)">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--primary);margin-bottom:.3rem">Kategori</div>
        <div style="font-weight:700;font-size:.9rem"><?= str_replace('_',' ',$l['kategori']) ?></div>
      </div>
      <div style="background:<?= $l['tingkat_urgensi']==='darurat'?'#ede9fe':($l['tingkat_urgensi']==='tinggi'?'#fee2e2':'#fef3c7') ?>;border-radius:var(--radius-lg);padding:1rem">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:<?= getUrgensiColor($l['tingkat_urgensi']) ?>;margin-bottom:.3rem">Urgensi</div>
        <div style="font-weight:700;font-size:.9rem"><?= ucfirst($l['tingkat_urgensi']) ?></div>
      </div>
    </div>

    <!-- Deskripsi -->
    <div class="card mb-2">
      <div class="card-body">
        <h4 style="margin-bottom:.75rem">📝 Deskripsi</h4>
        <p style="font-size:.9rem;line-height:1.7;color:var(--text)"><?= nl2br(htmlspecialchars($l['deskripsi'])) ?></p>
      </div>
    </div>

    <!-- Lokasi + Map -->
    <div class="card mb-2">
      <div class="card-header"><span style="font-weight:700">📍 Lokasi</span></div>
      <div class="card-body">
        <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1rem">
          📌 <?= htmlspecialchars($l['alamat_lengkap']) ?>
        </p>
        <div id="mapDetail" style="height:220px;border-radius:var(--radius-lg);border:1px solid var(--border)"></div>
        <a href="https://www.google.com/maps?q=<?= $l['latitude'] ?>,<?= $l['longitude'] ?>" target="_blank"
           class="btn btn-outline btn-sm mt-2 btn-block">🗺️ Buka di Google Maps</a>
      </div>
    </div>

    <!-- Timeline Tracking -->
    <div class="card mb-2">
      <div class="card-header"><span style="font-weight:700">📊 Riwayat Status</span></div>
      <div class="card-body">
        <div class="timeline">
          <?php foreach($tracking as $t):
            $b = getStatusBadge($t['status_baru']);
          ?>
          <div class="timeline-item">
            <div class="timeline-dot done"></div>
            <div class="timeline-content">
              <div class="timeline-title"><?= $b['icon'] ?> <?= $b['label'] ?></div>
              <?php if ($t['keterangan']): ?>
              <div class="timeline-desc"><?= htmlspecialchars($t['keterangan']) ?></div>
              <?php endif; ?>
              <div class="timeline-time">
                👤 <?= htmlspecialchars($t['petugas_nama'] ?? 'Sistem') ?> &bull;
                🕐 <?= timeAgo($t['created_at']) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Pelapor -->
    <?php if (!$l['is_anonim'] || isAdmin()): ?>
    <div class="card mb-2">
      <div class="card-header"><span style="font-weight:700">👤 Pelapor</span></div>
      <div class="card-body" style="display:flex;align-items:center;gap:1rem">
        <div style="width:48px;height:48px;border-radius:50%;background:var(--primary-xlight);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0">
          <?= $l['is_anonim'] ? '🎭' : '👤' ?>
        </div>
        <div>
          <div style="font-weight:700"><?= $l['is_anonim'] ? 'Anonim' : htmlspecialchars($l['nama_lengkap']) ?></div>
          <div style="font-size:.78rem;color:var(--text-muted)">
            Dilaporkan <?= timeAgo($l['created_at']) ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Komentar -->
    <div class="card mb-2">
      <div class="card-header">
        <span style="font-weight:700">💬 Komentar (<?= count($komentar) ?>)</span>
      </div>
      <div class="card-body">
        <?php if (empty($komentar)): ?>
        <p class="text-muted text-center text-small" style="padding:1rem 0">Belum ada komentar</p>
        <?php else: foreach($komentar as $k): ?>
        <div style="display:flex;gap:.75rem;margin-bottom:1rem">
          <div style="width:36px;height:36px;border-radius:50%;background:<?= $k['role']==='admin'?'var(--primary)':'var(--bg)' ?>;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.9rem">
            <?= $k['role']==='admin'?'👮':'👤' ?>
          </div>
          <div style="flex:1">
            <div style="display:flex;align-items:center;gap:.5rem">
              <span style="font-weight:700;font-size:.875rem"><?= htmlspecialchars($k['nama_lengkap']) ?></span>
              <?php if ($k['role']==='admin' || $k['role']==='petugas'): ?>
              <span class="badge badge-verified" style="font-size:.6rem">Petugas</span>
              <?php endif; ?>
            </div>
            <p style="font-size:.875rem;color:var(--text);margin-top:.25rem"><?= nl2br(htmlspecialchars($k['isi_komentar'])) ?></p>
            <div style="font-size:.72rem;color:var(--text-light);margin-top:.2rem"><?= timeAgo($k['created_at']) ?></div>
          </div>
        </div>
        <?php endforeach; endif; ?>

        <?php if (isLoggedIn()): ?>
        <div style="margin-top:1rem;border-top:1px solid var(--border);padding-top:1rem">
          <form id="formKomentar">
            <input type="hidden" name="laporan_id" value="<?= $l['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div style="display:flex;gap:.75rem;align-items:flex-end">
              <textarea name="komentar" id="inputKomentar" class="form-control" rows="2" placeholder="Tulis komentar..." style="flex:1"></textarea>
              <button type="submit" class="btn btn-primary btn-sm">Kirim</button>
            </div>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- padding wrapper -->
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // View map
  MapView.init('mapDetail', <?= $l['latitude'] ?>, <?= $l['longitude'] ?>, '<?= addslashes($l['judul']) ?>');

  // Komentar form
  document.getElementById('formKomentar')?.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch('../api/komentar-store.php', { method:'POST', body: fd });
    const data = await res.json();
    if (data.success) { Toast.show('Komentar terkirim', '', 'success'); setTimeout(()=>location.reload(),1000); }
    else Toast.show('Gagal', data.message, 'error');
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
