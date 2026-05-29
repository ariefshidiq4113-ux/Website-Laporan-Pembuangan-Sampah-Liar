<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$title = 'Manajemen Laporan';

$db = getDB();

// Filter & Search
$status    = sanitize($_GET['status']   ?? '');
$kategori  = sanitize($_GET['kategori'] ?? '');
$urgensi   = sanitize($_GET['urgensi']  ?? '');
$search    = sanitize($_GET['q']        ?? '');
$sort      = sanitize($_GET['sort']     ?? 'terbaru');
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 15;
$offset    = ($page - 1) * $limit;

$where  = ["1=1"];
$params = [];
if ($status)   { $where[] = "l.status = ?";            $params[] = $status; }
if ($kategori) { $where[] = "l.kategori = ?";          $params[] = $kategori; }
if ($urgensi)  { $where[] = "l.tingkat_urgensi = ?";   $params[] = $urgensi; }
if ($search)   { $where[] = "(l.judul LIKE ? OR l.kode_laporan LIKE ? OR l.alamat_lengkap LIKE ? OR u.nama_lengkap LIKE ?)";
                 $s = "%$search%"; $params = array_merge($params, [$s,$s,$s,$s]); }

$orderMap = ['terbaru'=>'l.created_at DESC','terlama'=>'l.created_at ASC','urgensi'=>"FIELD(l.tingkat_urgensi,'darurat','tinggi','sedang','rendah')"];
$orderBy  = $orderMap[$sort] ?? 'l.created_at DESC';

$whereStr = implode(' AND ', $where);

$totalStmt = $db->prepare("SELECT COUNT(*) FROM laporan l JOIN users u ON l.user_id=u.id WHERE $whereStr");
$totalStmt->execute($params);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

$stmt = $db->prepare("
  SELECT l.*, u.nama_lengkap, u.email,
    (SELECT path_file FROM foto_laporan WHERE laporan_id=l.id AND is_thumbnail=1 LIMIT 1) AS thumb,
    (SELECT COUNT(*) FROM komentar WHERE laporan_id=l.id) AS jml_komentar
  FROM laporan l JOIN users u ON l.user_id=u.id
  WHERE $whereStr ORDER BY $orderBy LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Count per status
$cntStmt = $db->query("SELECT status, COUNT(*) as c FROM laporan GROUP BY status");
$cnt = [];
foreach ($cntStmt->fetchAll() as $r) $cnt[$r['status']] = $r['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="sidebar-overlay" onclick="Sidebar.close()"></div>

  <main class="admin-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="admin-content">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem">
        <div>
          <h1 style="font-size:1.3rem;font-weight:800">📋 Manajemen Laporan</h1>
          <p class="text-muted text-small">Total <?= number_format($totalRows) ?> laporan ditemukan</p>
        </div>
        <a href="export.php" class="btn btn-outline btn-sm">📤 Export</a>
      </div>

      <!-- Filter Bar -->
      <div class="card mb-2" style="padding:1rem">
        <form method="GET" style="display:grid;gap:.75rem">
          <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <!-- Search -->
            <div class="search-bar" style="flex:1;min-width:200px">
              <span>🔍</span>
              <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari laporan, kode, pelapor...">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Cari</button>
            <?php if ($search||$status||$kategori||$urgensi): ?>
            <a href="laporan.php" class="btn btn-ghost btn-sm">✕ Reset</a>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <select name="status" class="form-control" style="width:auto;padding:.375rem .75rem;font-size:.8rem" onchange="this.form.submit()">
              <option value="">Semua Status</option>
              <?php foreach(['menunggu','diverifikasi','diproses','selesai','ditolak'] as $s): ?>
              <option value="<?=$s?>" <?=$status===$s?'selected':''?>><?= ucfirst($s) ?> (<?=$cnt[$s]??0?>)</option>
              <?php endforeach; ?>
            </select>
            <select name="kategori" class="form-control" style="width:auto;padding:.375rem .75rem;font-size:.8rem" onchange="this.form.submit()">
              <option value="">Semua Kategori</option>
              <?php foreach(['TPS_ILEGAL','SAMPAH_JALAN','SUNGAI','LAHAN_KOSONG','FASILITAS_UMUM','LAINNYA'] as $k): ?>
              <option value="<?=$k?>" <?=$kategori===$k?'selected':''?>><?= str_replace('_',' ',$k) ?></option>
              <?php endforeach; ?>
            </select>
            <select name="urgensi" class="form-control" style="width:auto;padding:.375rem .75rem;font-size:.8rem" onchange="this.form.submit()">
              <option value="">Semua Urgensi</option>
              <?php foreach(['rendah','sedang','tinggi','darurat'] as $u): ?>
              <option value="<?=$u?>" <?=$urgensi===$u?'selected':''?>><?= ucfirst($u) ?></option>
              <?php endforeach; ?>
            </select>
            <select name="sort" class="form-control" style="width:auto;padding:.375rem .75rem;font-size:.8rem" onchange="this.form.submit()">
              <option value="terbaru" <?=$sort==='terbaru'?'selected':''?>>Terbaru</option>
              <option value="terlama" <?=$sort==='terlama'?'selected':''?>>Terlama</option>
              <option value="urgensi" <?=$sort==='urgensi'?'selected':''?>>Urgensi</option>
            </select>
          </div>
        </form>
      </div>

      <!-- Status Filter Chips -->
      <div class="filter-chips mb-2">
        <a href="laporan.php" class="chip <?= !$status?'active':'' ?>">📋 Semua</a>
        <a href="?status=menunggu"     class="chip <?= $status==='menunggu'    ?'active':'' ?>">⏳ Menunggu (<?=$cnt['menunggu']??0?>)</a>
        <a href="?status=diverifikasi" class="chip <?= $status==='diverifikasi'?'active':'' ?>">✅ Diverifikasi (<?=$cnt['diverifikasi']??0?>)</a>
        <a href="?status=diproses"     class="chip <?= $status==='diproses'    ?'active':'' ?>">🔧 Diproses (<?=$cnt['diproses']??0?>)</a>
        <a href="?status=selesai"      class="chip <?= $status==='selesai'     ?'active':'' ?>">🎉 Selesai (<?=$cnt['selesai']??0?>)</a>
        <a href="?status=ditolak"      class="chip <?= $status==='ditolak'     ?'active':'' ?>">❌ Ditolak (<?=$cnt['ditolak']??0?>)</a>
      </div>

      <!-- Table -->
      <div class="card">
        <?php if (empty($laporan)): ?>
        <div class="empty-state"><div class="empty-icon">📭</div><h3>Tidak ada laporan</h3></div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th><input type="checkbox" id="checkAll" onchange="toggleAll(this)"></th>
                <th>Kode</th>
                <th>Laporan</th>
                <th>Pelapor</th>
                <th>Urgensi</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($laporan as $r):
                $badge  = getStatusBadge($r['status']);
                $urg    = $r['tingkat_urgensi'];
                $kIcons = ['TPS_ILEGAL'=>'🗑️','SAMPAH_JALAN'=>'🛣️','SUNGAI'=>'💧','LAHAN_KOSONG'=>'🌿','FASILITAS_UMUM'=>'🏛️','LAINNYA'=>'📍'];
              ?>
              <tr id="row-<?= $r['id'] ?>">
                <td><input type="checkbox" name="ids[]" value="<?= $r['id'] ?>" class="row-check"></td>
                <td>
                  <div style="font-weight:700;font-size:.78rem;white-space:nowrap;color:var(--primary)"><?= $r['kode_laporan'] ?></div>
                  <div style="font-size:.7rem;color:var(--text-muted)"><?= ($kIcons[$r['kategori']]??'📍').' '.str_replace('_',' ',$r['kategori']) ?></div>
                </td>
                <td style="max-width:220px">
                  <div style="display:flex;gap:.5rem;align-items:flex-start">
                    <?php if ($r['thumb']): ?>
                    <img src="<?= UPLOAD_URL . 'laporan/' . basename($r['thumb']) ?>" style="width:36px;height:36px;border-radius:6px;object-fit:cover;flex-shrink:0">
                    <?php else: ?>
                    <div style="width:36px;height:36px;border-radius:6px;background:var(--primary-xlight);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0"><?= $kIcons[$r['kategori']]??'📍' ?></div>
                    <?php endif; ?>
                    <div>
                      <div style="font-weight:600;font-size:.875rem;line-height:1.3"><?= htmlspecialchars(mb_strimwidth($r['judul'],0,50,'…')) ?></div>
                      <div style="font-size:.72rem;color:var(--text-muted)">📍 <?= htmlspecialchars($r['kecamatan'].', '.$r['kota']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="font-size:.875rem;font-weight:600"><?= htmlspecialchars($r['nama_lengkap']) ?></div>
                  <div style="font-size:.72rem;color:var(--text-muted)"><?= htmlspecialchars($r['email']) ?></div>
                </td>
                <td>
                  <span class="badge badge-urgensi-<?= $urg ?>"><?= ucfirst($urg) ?></span>
                </td>
                <td>
                  <span class="badge <?= $badge['class'] ?>"><?= $badge['icon'].' '.$badge['label'] ?></span>
                </td>
                <td style="font-size:.8rem;white-space:nowrap;color:var(--text-muted)">
                  <?= date('d M Y', strtotime($r['created_at'])) ?>
                </td>
                <td>
                  <div style="display:flex;gap:.25rem">
                    <a href="detail-laporan.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">Detail</a>
                    <button onclick="quickStatus(<?= $r['id'] ?>, '<?= $r['status'] ?>')" class="btn btn-ghost btn-sm" title="Ubah Status">⚡</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Bulk Action & Pagination -->
        <div style="padding:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;border-top:1px solid var(--border)">
          <div style="display:flex;align-items:center;gap:.75rem">
            <span style="font-size:.8rem;color:var(--text-muted)">Pilih semua:</span>
            <select id="bulkAction" class="form-control" style="width:auto;padding:.3rem .6rem;font-size:.8rem">
              <option value="">-- Aksi Massal --</option>
              <option value="diverifikasi">✅ Verifikasi</option>
              <option value="diproses">🔧 Proses</option>
              <option value="selesai">🎉 Selesaikan</option>
              <option value="ditolak">❌ Tolak</option>
            </select>
            <button onclick="bulkAction()" class="btn btn-ghost btn-sm">Terapkan</button>
          </div>
          <!-- Pagination -->
          <div style="display:flex;gap:.35rem">
            <?php if ($page > 1): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="btn btn-ghost btn-sm">←</a><?php endif; ?>
            <?php for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"
               class="btn btn-sm <?= $p===$page?'btn-primary':'btn-ghost' ?>"><?=$p?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="btn btn-ghost btn-sm">→</a><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Quick Status Modal -->
<div class="modal-overlay" id="modalStatus">
  <div class="modal" style="border-radius:var(--radius-xl);max-width:440px;margin:auto">
    <div class="modal-handle"></div>
    <div class="modal-header">
      <span class="modal-title">⚡ Ubah Status Laporan</span>
      <button class="modal-close" onclick="Modal.close('modalStatus')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="modalLaporanId">
      <div class="form-group">
        <label class="form-label">Status Baru</label>
        <select id="modalStatusBaru" class="form-control">
          <option value="diverifikasi">✅ Diverifikasi</option>
          <option value="diproses">🔧 Diproses</option>
          <option value="selesai">🎉 Selesai</option>
          <option value="ditolak">❌ Ditolak</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Keterangan (opsional)</label>
        <textarea id="modalKeterangan" class="form-control" rows="3" placeholder="Tulis keterangan..."></textarea>
      </div>
      <button onclick="submitQuickStatus()" class="btn btn-primary btn-block">Simpan Perubahan</button>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
function quickStatus(id, currentStatus) {
  document.getElementById('modalLaporanId').value = id;
  document.getElementById('modalStatusBaru').value = currentStatus;
  Modal.open('modalStatus');
}
async function submitQuickStatus() {
  const id  = document.getElementById('modalLaporanId').value;
  const st  = document.getElementById('modalStatusBaru').value;
  const ket = document.getElementById('modalKeterangan').value;
  const fd  = new FormData();
  fd.append('laporan_id', id); fd.append('status', st); fd.append('keterangan', ket);
  fd.append('csrf_token', '<?= getCsrfToken() ?>');
  const res  = await fetch('../api/laporan-update.php', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) { Toast.show('Status diperbarui!','','success'); setTimeout(()=>location.reload(),1000); }
  else Toast.show('Gagal', data.message, 'error');
  Modal.close('modalStatus');
}
function toggleAll(cb) { document.querySelectorAll('.row-check').forEach(c=>c.checked=cb.checked); }
async function bulkAction() {
  const act  = document.getElementById('bulkAction').value;
  const ids  = [...document.querySelectorAll('.row-check:checked')].map(c=>c.value);
  if (!act || !ids.length) { Toast.show('Pilih laporan & aksi','','warning'); return; }
  if (!confirm(`Ubah ${ids.length} laporan ke status "${act}"?`)) return;
  const fd = new FormData();
  fd.append('aksi','bulk'); fd.append('status',act); fd.append('csrf_token','<?= getCsrfToken() ?>');
  ids.forEach(id=>fd.append('ids[]',id));
  const res  = await fetch('../api/laporan-update.php', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) { Toast.show('Berhasil!',data.message,'success'); setTimeout(()=>location.reload(),1000); }
  else Toast.show('Gagal',data.message,'error');
}
</script>
</body>
</html>