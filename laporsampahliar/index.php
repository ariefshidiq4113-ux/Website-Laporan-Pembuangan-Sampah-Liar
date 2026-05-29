<?php
require_once __DIR__ . '/includes/config.php';
startSession();
$title = 'Beranda';
$activePage = 'home';

$db = getDB();

// Stats
$statsQ = $db->query("
  SELECT
    COUNT(*) AS total,
    SUM(status='menunggu') AS menunggu,
    SUM(status='diproses') AS diproses,
    SUM(status='selesai')  AS selesai
  FROM laporan
");
$stats = $statsQ->fetch();

// Laporan terbaru
$laporanQ = $db->query("
  SELECT l.*, u.nama_lengkap, u.foto_profil,
         (SELECT path_file FROM foto_laporan WHERE laporan_id=l.id AND is_thumbnail=1 LIMIT 1) AS thumb
  FROM laporan l JOIN users u ON l.user_id=u.id
  ORDER BY l.created_at DESC LIMIT 10
");
$laporanList = $laporanQ->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-wrapper">

  <!-- Hero Section -->
  <section style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); padding: 2rem 1rem 3rem; position:relative; overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;font-size:8rem;opacity:.08;line-height:1">🗑️</div>
    <div style="position:absolute;bottom:-10px;left:10px;font-size:5rem;opacity:.06;line-height:1">♻️</div>
    <div style="position:relative;z-index:1">
      <div style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.2);color:#fff;border-radius:999px;padding:.3rem .875rem;font-size:.75rem;font-weight:700;margin-bottom:.875rem">
        🌱 Platform Resmi Dinas Lingkungan Hidup
      </div>
      <h1 style="color:#fff;font-size:1.6rem;line-height:1.3;margin-bottom:.75rem">
        Laporkan Sampah Liar<br>di Sekitar Anda
      </h1>
      <p style="color:rgba(255,255,255,.85);font-size:.9rem;margin-bottom:1.5rem">
        Bersama kita jaga kebersihan lingkungan. Laporan Anda diproses langsung oleh petugas kebersihan daerah.
      </p>
      <?php if (!isLoggedIn()): ?>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap">
        <a href="pages/buat-laporan.php" class="btn btn-lg" style="background:#fff;color:var(--primary);border-color:#fff;font-weight:800">
          📸 Buat Laporan
        </a>
        <a href="pages/semua-laporan.php" class="btn btn-lg" style="background:transparent;color:#fff;border-color:rgba(255,255,255,.5)">
          📋 Lihat Semua
        </a>
      </div>
      <?php else: ?>
      <a href="pages/buat-laporan.php" class="btn btn-lg" style="background:#fff;color:var(--primary);border-color:#fff;font-weight:800">
        📸 Buat Laporan Baru
      </a>
      <?php endif; ?>
    </div>
  </section>

  <!-- Stats Band -->
  <section style="padding:1rem">
    <div class="stats-grid">
      <div class="stat-card" style="--stat-color:#3b82f6">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?= number_format($stats['total']) ?></div>
        <div class="stat-label">Total Laporan</div>
      </div>
      <div class="stat-card" style="--stat-color:#f59e0b">
        <div class="stat-icon">⏳</div>
        <div class="stat-value"><?= number_format($stats['menunggu']) ?></div>
        <div class="stat-label">Menunggu</div>
      </div>
      <div class="stat-card" style="--stat-color:#f97316">
        <div class="stat-icon">🔧</div>
        <div class="stat-value"><?= number_format($stats['diproses']) ?></div>
        <div class="stat-label">Diproses</div>
      </div>
      <div class="stat-card" style="--stat-color:#22c55e">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= number_format($stats['selesai']) ?></div>
        <div class="stat-label">Selesai</div>
      </div>
    </div>
  </section>

  <!-- Cara Melapor -->
  <section style="padding:1rem">
    <h2 style="font-size:1.15rem;padding:0 .25rem;margin-bottom:1rem">Cara Melapor</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
      <?php $steps=[['📸','Ambil Foto','Foto kondisi sampah liar'],['📍','Tandai Lokasi','GPS otomatis atau pilih di peta'],['📝','Isi Formulir','Beri deskripsi singkat'],['🚀','Kirim Laporan','Petugas segera ditugaskan']];
      foreach($steps as $i=>[$icon,$title,$desc]): ?>
      <div style="background:#fff;border-radius:var(--radius-lg);padding:1rem;border:1px solid var(--border);text-align:center">
        <div style="font-size:2rem;margin-bottom:.5rem"><?=$icon?></div>
        <div style="font-weight:700;font-size:.875rem"><?=$title?></div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.2rem"><?=$desc?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Laporan Terbaru -->
  <section style="padding:0 1rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
      <h2 style="font-size:1.15rem">Laporan Terbaru</h2>
      <a href="pages/semua-laporan.php" class="text-small text-muted" style="font-weight:600">Lihat semua →</a>
    </div>
  </section>

  <div class="laporan-list">
    <?php if (empty($laporanList)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>Belum ada laporan</h3>
      <p>Jadilah yang pertama melapor!</p>
    </div>
    <?php else: foreach($laporanList as $l):
      $badge = getStatusBadge($l['status']);
      $kategoriIcon = ['TPS_ILEGAL'=>'🗑️','SAMPAH_JALAN'=>'🛣️','SUNGAI'=>'💧','LAHAN_KOSONG'=>'🌿','FASILITAS_UMUM'=>'🏛️','LAINNYA'=>'📍'][$l['kategori']] ?? '📍';
    ?>
    <div class="laporan-card" onclick="window.location='pages/detail-laporan.php?id=<?=$l['id']?>'">
      <div class="card-top">
        <?php if ($l['thumb']): ?>
          <img class="card-thumb" src="<?= UPLOAD_URL . 'laporan/' . basename($l['thumb']) ?>" alt="Foto">
        <?php else: ?>
          <div class="card-thumb-placeholder"><?= $kategoriIcon ?></div>
        <?php endif; ?>
        <div class="card-meta">
          <div class="card-title"><?= htmlspecialchars($l['judul']) ?></div>
          <div class="card-loc">📍 <?= htmlspecialchars($l['kecamatan'] . ', ' . $l['kota']) ?></div>
          <div style="display:flex;gap:.4rem;margin-top:.4rem;flex-wrap:wrap">
            <span class="badge <?= $badge['class'] ?>"><?= $badge['icon'] ?> <?= $badge['label'] ?></span>
            <span class="badge badge-urgensi-<?= $l['tingkat_urgensi'] ?>"><?= ucfirst($l['tingkat_urgensi']) ?></span>
          </div>
        </div>
      </div>
      <div class="card-bottom">
        <span class="card-time">🕐 <?= timeAgo($l['created_at']) ?></span>
        <span style="font-size:.78rem;color:var(--text-muted)">
          <?= $l['is_anonim'] ? '🎭 Anonim' : '👤 ' . htmlspecialchars($l['nama_lengkap']) ?>
        </span>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- CTA Banner -->
  <section style="margin:1.5rem 1rem">
    <div style="background:linear-gradient(135deg,#0f172a,#1e3a5f);border-radius:var(--radius-xl);padding:1.5rem;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:.75rem">♻️</div>
      <h3 style="color:#fff;margin-bottom:.5rem">Bersama Kita Bisa!</h3>
      <p style="color:rgba(255,255,255,.7);font-size:.875rem;margin-bottom:1.25rem">
        Setiap laporan membuat lingkungan kita lebih bersih dan sehat.
      </p>
      <?php if (!isLoggedIn()): ?>
      <a href="pages/register.php" class="btn btn-primary">Daftar Sekarang</a>
      <?php else: ?>
      <a href="pages/buat-laporan.php" class="btn btn-primary">Buat Laporan</a>
      <?php endif; ?>
    </div>
  </section>

</div><!-- .page-wrapper -->

<?php include __DIR__ . '/includes/footer.php'; ?>
