<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: laporan.php'); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT l.*, u.nama_lengkap, u.email, u.no_hp FROM laporan l JOIN users u ON l.user_id=u.id WHERE l.id=?");
$stmt->execute([$id]);
$l = $stmt->fetch();
if (!$l) { header('Location: laporan.php'); exit; }

$fotos = $db->prepare("SELECT * FROM foto_laporan WHERE laporan_id=? ORDER BY is_thumbnail DESC");
$fotos->execute([$id]); $fotos = $fotos->fetchAll();

$tracking = $db->prepare("SELECT t.*, u.nama_lengkap as pn FROM tracking_laporan t LEFT JOIN users u ON t.diubah_oleh=u.id WHERE t.laporan_id=? ORDER BY t.created_at ASC");
$tracking->execute([$id]); $tracking = $tracking->fetchAll();

$komentar = $db->prepare("SELECT k.*, u.nama_lengkap, u.role FROM komentar k JOIN users u ON k.user_id=u.id WHERE k.laporan_id=? ORDER BY k.created_at ASC");
$komentar->execute([$id]); $komentar = $komentar->fetchAll();

$petugas = $db->query("SELECT id, nama_lengkap FROM users WHERE role IN ('admin','petugas') ORDER BY nama_lengkap")->fetchAll();
$penugasan = $db->prepare("SELECT p.*, u.nama_lengkap as pn FROM penugasan p JOIN users u ON p.petugas_id=u.id WHERE p.laporan_id=? ORDER BY p.created_at DESC LIMIT 1");
$penugasan->execute([$id]); $penugasan = $penugasan->fetch();

$badge = getStatusBadge($l['status']);
$title = 'Detail Laporan';
$csrf  = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $title ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="sidebar-overlay" onclick="Sidebar.close()"></div>
  <main class="admin-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="admin-content">

      <!-- Breadcrumb -->
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:var(--text-muted);margin-bottom:1.25rem">
        <a href="laporan.php" style="color:var(--primary)">← Kembali ke Laporan</a>
        <span>/</span>
        <span><?= htmlspecialchars($l['kode_laporan']) ?></span>
      </div>

      <!-- Header Card -->
      <div class="card mb-2">
        <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));padding:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
          <div>
            <div style="color:rgba(255,255,255,.75);font-size:.75rem;font-weight:700;margin-bottom:.3rem"><?= $l['kode_laporan'] ?></div>
            <h1 style="color:#fff;font-size:1.2rem;margin-bottom:.5rem"><?= htmlspecialchars($l['judul']) ?></h1>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
              <span class="badge <?= $badge['class'] ?>"><?= $badge['icon'].' '.$badge['label'] ?></span>
              <span class="badge badge-urgensi-<?= $l['tingkat_urgensi'] ?>"><?= ucfirst($l['tingkat_urgensi']) ?></span>
            </div>
          </div>
          <!-- Quick Status Change -->
          <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <?php $nextStatus=['menunggu'=>['diverifikasi','✅ Verifikasi','btn-outline'],
              'diverifikasi'=>['diproses','🔧 Tugaskan Petugas','btn-primary'],
              'diproses'=>['selesai','🎉 Tandai Selesai','btn-primary']];
            if (isset($nextStatus[$l['status']])): [$ns,$nl,$nc2]=$nextStatus[$l['status']]; ?>
            <button onclick="updateStatus('<?=$ns?>','',<?=$l['id']?>)" class="btn <?=$nc2?>" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.4)"><?=$nl?></button>
            <?php endif; ?>
            <?php if ($l['status']!=='ditolak' && $l['status']!=='selesai'): ?>
            <button onclick="openTolakModal()" class="btn" style="background:rgba(239,68,68,.2);color:#fff;border-color:rgba(239,68,68,.4)">❌ Tolak</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div style="display:grid;gap:1.25rem" class="admin-grid-2-detail">

        <!-- Left Col -->
        <div>
          <!-- Fotos -->
          <?php if (!empty($fotos)): ?>
          <div class="card mb-2">
            <div class="card-header"><span style="font-weight:700">📸 Foto (<?= count($fotos) ?>)</span></div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;padding:1rem">
              <?php foreach($fotos as $f): ?>
              <a href="<?= UPLOAD_URL.'laporan/'.basename($f['path_file']) ?>" target="_blank">
                <img src="<?= UPLOAD_URL.'laporan/'.basename($f['path_file']) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:var(--radius-sm)">
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Deskripsi -->
          <div class="card mb-2">
            <div class="card-header"><span style="font-weight:700">📝 Deskripsi</span></div>
            <div class="card-body">
              <p style="font-size:.9rem;line-height:1.7"><?= nl2br(htmlspecialchars($l['deskripsi'])) ?></p>
            </div>
          </div>

          <!-- Lokasi -->
          <div class="card mb-2">
            <div class="card-header">
              <span style="font-weight:700">📍 Lokasi</span>
              <a href="https://www.google.com/maps?q=<?= $l['latitude'] ?>,<?= $l['longitude'] ?>" target="_blank" class="btn btn-ghost btn-sm">Google Maps ↗</a>
            </div>
            <div class="card-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;font-size:.875rem">
                <div><span style="color:var(--text-muted);font-size:.75rem">Kelurahan</span><div style="font-weight:600"><?= htmlspecialchars($l['kelurahan']?:'—') ?></div></div>
                <div><span style="color:var(--text-muted);font-size:.75rem">Kecamatan</span><div style="font-weight:600"><?= htmlspecialchars($l['kecamatan']?:'—') ?></div></div>
                <div><span style="color:var(--text-muted);font-size:.75rem">Kota</span><div style="font-weight:600"><?= htmlspecialchars($l['kota']?:'—') ?></div></div>
                <div><span style="color:var(--text-muted);font-size:.75rem">Provinsi</span><div style="font-weight:600"><?= htmlspecialchars($l['provinsi']?:'—') ?></div></div>
              </div>
              <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.875rem">📌 <?= htmlspecialchars($l['alamat_lengkap']) ?></p>
              <div id="mapAdmin" style="height:220px;border-radius:var(--radius-lg);border:1px solid var(--border)"></div>
              <div style="font-size:.72rem;color:var(--text-light);margin-top:.4rem">Koordinat: <?= $l['latitude'] ?>, <?= $l['longitude'] ?></div>
            </div>
          </div>

          <!-- Komentar -->
          <div class="card mb-2">
            <div class="card-header"><span style="font-weight:700">💬 Komentar (<?= count($komentar) ?>)</span></div>
            <div class="card-body">
              <?php if (empty($komentar)): ?>
              <p class="text-muted text-small text-center">Belum ada komentar</p>
              <?php else: foreach($komentar as $k): ?>
              <div style="display:flex;gap:.75rem;margin-bottom:.875rem;padding-bottom:.875rem;border-bottom:1px solid var(--border)">
                <div style="width:34px;height:34px;border-radius:50%;background:<?= $k['role']==='admin'?'var(--primary)':'var(--bg)' ?>;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <?= $k['role']==='admin'?'👮':'👤' ?>
                </div>
                <div>
                  <div style="font-weight:700;font-size:.875rem"><?= htmlspecialchars($k['nama_lengkap']) ?> <?= $k['role']==='admin'?'<span class="badge badge-verified" style="font-size:.6rem">Admin</span>':'' ?></div>
                  <p style="font-size:.875rem;margin-top:.2rem"><?= nl2br(htmlspecialchars($k['isi_komentar'])) ?></p>
                  <div style="font-size:.7rem;color:var(--text-light)"><?= timeAgo($k['created_at']) ?></div>
                </div>
              </div>
              <?php endforeach; endif; ?>
              <!-- Admin Comment Form -->
              <form id="formAdminKomentar" style="margin-top:1rem">
                <input type="hidden" name="laporan_id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <textarea name="komentar" class="form-control" rows="3" placeholder="Tulis balasan resmi dari instansi..."></textarea>
                <button type="submit" class="btn btn-primary btn-sm mt-1">Kirim Balasan Resmi</button>
              </form>
            </div>
          </div>
        </div>

        <!-- Right Col -->
        <div>
          <!-- Info Pelapor -->
          <div class="card mb-2">
            <div class="card-header"><span style="font-weight:700">👤 Pelapor</span></div>
            <div class="card-body">
              <div style="display:flex;gap:1rem;align-items:center;margin-bottom:1rem">
                <div style="width:48px;height:48px;border-radius:50%;background:var(--primary-xlight);display:flex;align-items:center;justify-content:center;font-size:1.5rem"><?= $l['is_anonim']?'🎭':'👤' ?></div>
                <div>
                  <div style="font-weight:700"><?= $l['is_anonim']?'Anonim':htmlspecialchars($l['nama_lengkap']) ?></div>
                  <?php if (!$l['is_anonim']): ?>
                  <div style="font-size:.8rem;color:var(--text-muted)">✉️ <?= htmlspecialchars($l['email']) ?></div>
                  <div style="font-size:.8rem;color:var(--text-muted)">📱 <?= htmlspecialchars($l['no_hp']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;font-size:.8rem">
                <div><span style="color:var(--text-muted)">Tanggal Lapor</span><div style="font-weight:600"><?= date('d M Y H:i', strtotime($l['created_at'])) ?></div></div>
                <div><span style="color:var(--text-muted)">Kategori</span><div style="font-weight:600"><?= str_replace('_',' ',$l['kategori']) ?></div></div>
              </div>
            </div>
          </div>

          <!-- Update Status -->
          <div class="card mb-2">
            <div class="card-header"><span style="font-weight:700">⚡ Update Status</span></div>
            <div class="card-body">
              <form id="formUpdateStatus">
                <input type="hidden" name="laporan_id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="form-group">
                  <label class="form-label">Status</label>
                  <select name="status" id="statusSelect" class="form-control">
                    <?php foreach(['menunggu','diverifikasi','diproses','selesai','ditolak'] as $s): ?>
                    <option value="<?=$s?>" <?=$l['status']===$s?'selected':''?>><?= getStatusBadge($s)['icon'].' '.ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Keterangan</label>
                  <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan untuk pelapor..."></textarea>
                </div>
                <div class="form-group">
                  <label class="form-label">Foto Bukti (Opsional)</label>
                  <input type="file" name="foto_bukti" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary btn-block">💾 Simpan Status</button>
              </form>
            </div>
          </div>

          <!-- Penugasan Petugas -->
          <div class="card mb-2">
            <div class="card-header"><span style="font-weight:700">👷 Penugasan Petugas</span></div>
            <div class="card-body">
              <?php if ($penugasan): ?>
              <div style="background:var(--primary-xlight);border-radius:var(--radius-sm);padding:.875rem;margin-bottom:1rem;border:1px solid var(--primary-light)">
                <div style="font-weight:700;font-size:.875rem">👷 <?= htmlspecialchars($penugasan['pn']) ?></div>
                <div style="font-size:.78rem;color:var(--text-muted);margin-top:.2rem">Deadline: <?= $penugasan['deadline'] ? date('d M Y',$penugasan['deadline']) : '—' ?></div>
                <span class="badge badge-verified" style="margin-top:.4rem"><?= ucfirst($penugasan['status']) ?></span>
              </div>
              <?php endif; ?>
              <form id="formTugas">
                <input type="hidden" name="laporan_id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="form-group">
                  <label class="form-label">Pilih Petugas</label>
                  <select name="petugas_id" class="form-control">
                    <option value="">-- Pilih Petugas --</option>
                    <?php foreach($petugas as $p): ?>
                    <option value="<?=$p['id']?>"><?= htmlspecialchars($p['nama_lengkap']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Deadline</label>
                  <input type="date" name="deadline" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Catatan Tugas</label>
                  <textarea name="catatan" class="form-control" rows="2" placeholder="Instruksi untuk petugas..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">👷 Tugaskan Petugas</button>
              </form>
            </div>
          </div>

          <!-- Timeline -->
          <div class="card">
            <div class="card-header"><span style="font-weight:700">📊 Riwayat Status</span></div>
            <div class="card-body">
              <div class="timeline">
                <?php foreach($tracking as $t): $b=getStatusBadge($t['status_baru']); ?>
                <div class="timeline-item">
                  <div class="timeline-dot done"></div>
                  <div class="timeline-content">
                    <div class="timeline-title"><?= $b['icon'].' '.$b['label'] ?></div>
                    <?php if ($t['keterangan']): ?><div class="timeline-desc"><?= htmlspecialchars($t['keterangan']) ?></div><?php endif; ?>
                    <div class="timeline-time">👤 <?= htmlspecialchars($t['pn']??'Sistem') ?> &bull; <?= timeAgo($t['created_at']) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Tolak Modal -->
<div class="modal-overlay" id="modalTolak">
  <div class="modal" style="border-radius:var(--radius-xl);max-width:420px;margin:auto">
    <div class="modal-handle"></div>
    <div class="modal-header"><span class="modal-title">❌ Tolak Laporan</span><button class="modal-close" onclick="Modal.close('modalTolak')">✕</button></div>
    <div class="modal-body">
      <div class="alert alert-warning"><span class="alert-icon">⚠️</span>Berikan alasan penolakan yang jelas.</div>
      <textarea id="alasanTolak" class="form-control" rows="4" placeholder="Alasan penolakan..."></textarea>
      <button onclick="submitTolak()" class="btn btn-danger btn-block mt-2">Konfirmasi Tolak</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  MapView.init('mapAdmin', <?= $l['latitude'] ?>, <?= $l['longitude'] ?>, '<?= addslashes($l['judul']) ?>');

  // Update Status
  document.getElementById('formUpdateStatus').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch('../api/laporan-update.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) { Toast.show('Status diperbarui!','','success'); setTimeout(()=>location.reload(),1200); }
    else Toast.show('Gagal',data.message,'error');
  });

  // Penugasan
  document.getElementById('formTugas').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch('../api/tugas-store.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) { Toast.show('Petugas ditugaskan!','','success'); setTimeout(()=>location.reload(),1200); }
    else Toast.show('Gagal',data.message,'error');
  });

  // Admin komentar
  document.getElementById('formAdminKomentar').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch('../api/komentar-store.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) { Toast.show('Komentar terkirim','','success'); setTimeout(()=>location.reload(),1000); }
    else Toast.show('Gagal',data.message,'error');
  });
});

function updateStatus(status, ket, id) {
  const fd = new FormData();
  fd.append('laporan_id', id||<?= $id ?>); fd.append('status',status);
  fd.append('keterangan',ket); fd.append('csrf_token','<?= $csrf ?>');
  fetch('../api/laporan-update.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(d.success){Toast.show('Status diperbarui!','','success');setTimeout(()=>location.reload(),1200);}
    else Toast.show('Gagal',d.message,'error');
  });
}
function openTolakModal() { Modal.open('modalTolak'); }
function submitTolak() {
  const alasan = document.getElementById('alasanTolak').value;
  if (!alasan.trim()) { Toast.show('Isi alasan penolakan','','warning'); return; }
  updateStatus('ditolak', alasan, <?= $id ?>);
  Modal.close('modalTolak');
}
</script>
<style>
@media(min-width:900px){.admin-grid-2-detail{grid-template-columns:1.4fr 1fr;}}
</style>
</body>
</html>