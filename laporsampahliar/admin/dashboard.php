<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$title = 'Admin Dashboard';

$db = getDB();

// Statistik utama
$stats = $db->query("
  SELECT
    COUNT(*) AS total,
    SUM(status='menunggu') AS menunggu,
    SUM(status='diverifikasi') AS diverifikasi,
    SUM(status='diproses') AS diproses,
    SUM(status='selesai') AS selesai,
    SUM(status='ditolak') AS ditolak
  FROM laporan
")->fetch();

$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='warga'")->fetchColumn();
$laporanHariIni= $db->query("SELECT COUNT(*) FROM laporan WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$laporanBulanIni=$db->query("SELECT COUNT(*) FROM laporan WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// Laporan terbaru 10
$terbaru = $db->query("
  SELECT l.*, u.nama_lengkap,
    (SELECT path_file FROM foto_laporan WHERE laporan_id=l.id AND is_thumbnail=1 LIMIT 1) AS thumb
  FROM laporan l JOIN users u ON l.user_id=u.id
  ORDER BY l.created_at DESC LIMIT 10
")->fetchAll();

// Data grafik 7 hari
$grafik7Hari = $db->query("
  SELECT DATE(created_at) AS tgl, COUNT(*) AS total
  FROM laporan WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  GROUP BY DATE(created_at) ORDER BY tgl
")->fetchAll();

// Grafik per kategori
$grafikKategori = $db->query("
  SELECT kategori, COUNT(*) AS total FROM laporan GROUP BY kategori ORDER BY total DESC
")->fetchAll();

// Grafik per bulan (12 bulan)
$grafik12Bulan = $db->query("
  SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, COUNT(*) AS total
  FROM laporan WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
  GROUP BY bln ORDER BY bln
")->fetchAll();

// Top pelapor
$topPelapor = $db->query("
  SELECT u.nama_lengkap, COUNT(l.id) AS total_laporan
  FROM users u JOIN laporan l ON u.id=l.user_id
  GROUP BY u.id ORDER BY total_laporan DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .admin-grid-4 { display:grid; grid-template-columns:repeat(2,1fr); gap:.875rem; }
    @media(min-width:900px){ .admin-grid-4{ grid-template-columns:repeat(4,1fr); } }
    .admin-grid-2 { display:grid; gap:1.25rem; }
    @media(min-width:900px){ .admin-grid-2{ grid-template-columns:1fr 1fr; } }
  </style>
</head>
<body>

<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">🗑️</div>
      <span><?= APP_NAME ?></span>
    </div>
    <nav>
      <a href="dashboard.php"      class="active"><span class="nav-ic">📊</span> Dashboard</a>
      <a href="laporan.php"                      ><span class="nav-ic">📋</span> Semua Laporan</a>
      <a href="peta.php"                         ><span class="nav-ic">🗺️</span> Peta Laporan</a>
      <a href="petugas.php"                      ><span class="nav-ic">👷</span> Manajemen Petugas</a>
      <a href="pengguna.php"                     ><span class="nav-ic">👥</span> Pengguna</a>
      <a href="laporan-grafik.php"               ><span class="nav-ic">📈</span> Grafik & Statistik</a>
      <a href="export.php"                       ><span class="nav-ic">📤</span> Export Data</a>
      <a href="pengaturan.php"                   ><span class="nav-ic">⚙️</span> Pengaturan</a>
      <a href="../pages/logout.php"    ><span class="nav-ic">🚪</span> Keluar</a>
    </nav>
  </aside>
  <div class="sidebar-overlay" onclick="Sidebar.close()"></div>

  <!-- Main -->
  <main class="admin-main">
    <header class="admin-topbar">
      <span class="menu-toggle" onclick="Sidebar.toggle()">☰</span>
      <div>
        <h2 style="font-size:1.1rem">Dashboard Admin</h2>
        <p style="font-size:.75rem;color:var(--text-muted)"><?= date('l, d F Y') ?></p>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:.75rem">
        <a href="../pages/notifikasi.php" style="position:relative;font-size:1.2rem">
          🔔
          <?php $nc=$db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id=? AND is_read=0"); $nc->execute([$_SESSION['user_id']]); $nc=$nc->fetchColumn(); if($nc>0): ?>
          <span style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.6rem;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700"><?=$nc?></span>
          <?php endif; ?>
        </a>
        <div style="font-size:.875rem;font-weight:600">👤 <?= htmlspecialchars($_SESSION['nama']) ?></div>
      </div>
    </header>

    <div class="admin-content">

      <!-- Stat Cards -->
      <div class="admin-grid-4 mb-3">
        <?php $statCards=[
          ['total','📋','Total Laporan',$stats['total'],'#3b82f6'],
          ['menunggu','⏳','Menunggu',$stats['menunggu'],'#f59e0b'],
          ['diproses','🔧','Diproses',$stats['diproses'],'#f97316'],
          ['selesai','✅','Selesai',$stats['selesai'],'#22c55e'],
        ]; foreach($statCards as [$key,$icon,$label,$val,$color]): ?>
        <div class="stat-card" style="--stat-color:<?=$color?>">
          <div class="stat-icon"><?=$icon?></div>
          <div class="stat-value"><?= number_format($val) ?></div>
          <div class="stat-label"><?=$label?></div>
          <a href="laporan.php?status=<?=$key?>" style="font-size:.72rem;color:var(--primary);font-weight:700;margin-top:.5rem;display:block">Lihat →</a>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Secondary stats -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.875rem;margin-bottom:1.5rem">
        <div style="background:#fff;border-radius:var(--radius-lg);padding:1rem;border:1px solid var(--border);text-align:center">
          <div style="font-size:1.5rem;font-weight:800;color:var(--primary)"><?= $laporanHariIni ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)">Hari Ini</div>
        </div>
        <div style="background:#fff;border-radius:var(--radius-lg);padding:1rem;border:1px solid var(--border);text-align:center">
          <div style="font-size:1.5rem;font-weight:800;color:var(--info)"><?= $laporanBulanIni ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)">Bulan Ini</div>
        </div>
        <div style="background:#fff;border-radius:var(--radius-lg);padding:1rem;border:1px solid var(--border);text-align:center">
          <div style="font-size:1.5rem;font-weight:800;color:var(--purple)"><?= $totalUsers ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)">Pengguna</div>
        </div>
      </div>

      <!-- Charts -->
      <div class="admin-grid-2 mb-3">
        <div class="card">
          <div class="card-header"><span style="font-weight:700">📈 Laporan 7 Hari Terakhir</span></div>
          <div class="card-body">
            <div class="chart-container"><canvas id="chartHarian"></canvas></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span style="font-weight:700">🍩 Sebaran Kategori</span></div>
          <div class="card-body">
            <div class="chart-container"><canvas id="chartKategori"></canvas></div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><span style="font-weight:700">📊 Tren 12 Bulan Terakhir</span></div>
        <div class="card-body">
          <div style="height:220px"><canvas id="chartBulanan"></canvas></div>
        </div>
      </div>

      <!-- Tabel Laporan Terbaru -->
      <div class="card mb-3">
        <div class="card-header">
          <span style="font-weight:700">📋 Laporan Terbaru</span>
          <a href="laporan.php" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Kode</th><th>Judul</th><th>Pelapor</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($terbaru as $r):
                $b = getStatusBadge($r['status']);
                $icons = ['TPS_ILEGAL'=>'🗑️','SAMPAH_JALAN'=>'🛣️','SUNGAI'=>'💧','LAHAN_KOSONG'=>'🌿','FASILITAS_UMUM'=>'🏛️','LAINNYA'=>'📍'];
              ?>
              <tr>
                <td style="font-weight:700;font-size:.8rem;white-space:nowrap"><?= $r['kode_laporan'] ?></td>
                <td style="max-width:200px">
                  <div style="font-weight:600;font-size:.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['judul']) ?></div>
                  <div style="font-size:.72rem;color:var(--text-muted)"><?= htmlspecialchars($r['kecamatan'].', '.$r['kota']) ?></div>
                </td>
                <td style="font-size:.875rem"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                <td><span style="font-size:.875rem"><?= ($icons[$r['kategori']]??'📍').' '.str_replace('_',' ',$r['kategori']) ?></span></td>
                <td><span class="badge <?= $b['class'] ?>"><?= $b['icon'].' '.$b['label'] ?></span></td>
                <td style="font-size:.8rem;white-space:nowrap"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                <td>
                  <a href="detail-laporan.php?id=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">Detail</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Pelapor -->
      <div class="card">
        <div class="card-header"><span style="font-weight:700">🏆 Top Pelapor</span></div>
        <div class="card-body">
          <?php foreach($topPelapor as $i=>$p): ?>
          <div style="display:flex;align-items:center;gap:.875rem;padding:.5rem 0;<?= $i<count($topPelapor)-1?'border-bottom:1px solid var(--border)':'' ?>">
            <div style="width:32px;height:32px;background:<?= ['#ffd700','#c0c0c0','#cd7f32','var(--bg)','var(--bg)'][$i] ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.875rem;border:2px solid var(--border)">
              <?= $i<3?['🥇','🥈','🥉'][$i]:($i+1) ?>
            </div>
            <div style="flex:1;font-weight:600"><?= htmlspecialchars($p['nama_lengkap']) ?></div>
            <div style="font-weight:800;color:var(--primary)"><?= $p['total_laporan'] ?> laporan</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div><!-- .admin-content -->
  </main>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
const primary = '#16a34a', gridColor = 'rgba(0,0,0,.06)';

// Chart Harian
new Chart(document.getElementById('chartHarian'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($grafik7Hari,'tgl')) ?>,
    datasets: [{ label:'Laporan', data: <?= json_encode(array_column($grafik7Hari,'total')) ?>,
      backgroundColor: primary, borderRadius: 6 }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
    scales:{ y:{ beginAtZero:true, grid:{color:gridColor} }, x:{grid:{display:false}} } }
});

// Chart Kategori
new Chart(document.getElementById('chartKategori'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($grafikKategori,'kategori')) ?>.map(k=>k.replace('_',' ')),
    datasets: [{ data: <?= json_encode(array_column($grafikKategori,'total')) ?>,
      backgroundColor:['#16a34a','#3b82f6','#f59e0b','#ef4444','#7c3aed','#06b6d4'],
      borderWidth:2, borderColor:'#fff' }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'right',labels:{boxWidth:12,font:{size:10}}}} }
});

// Chart 12 Bulan
new Chart(document.getElementById('chartBulanan'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($grafik12Bulan,'bln')) ?>,
    datasets: [{
      label:'Laporan per Bulan', data: <?= json_encode(array_column($grafik12Bulan,'total')) ?>,
      borderColor: primary, backgroundColor:'rgba(22,163,74,.1)',
      tension:.4, fill:true, pointBackgroundColor:primary, pointRadius:4
    }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
    scales:{ y:{beginAtZero:true, grid:{color:gridColor}}, x:{grid:{display:false}} } }
});
</script>
</body>
</html>
