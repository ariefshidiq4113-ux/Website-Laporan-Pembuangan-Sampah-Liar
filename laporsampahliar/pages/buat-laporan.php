<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$title = 'Buat Laporan';
$activePage = 'buat';
$csrf = getCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
  <div class="page-header">
    <h1 class="page-title">📸 Buat Laporan</h1>
    <p class="page-subtitle">Laporkan sampah liar di sekitar Anda</p>
  </div>

  <form id="formLaporan" action="../api/laporan-store.php" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

    <div style="padding:0 1rem">

      <!-- STEP 1: Foto -->
      <div class="card mb-2">
        <div class="card-header">
          <span style="font-weight:700">📸 Foto Sampah</span>
          <span class="text-muted text-small">Maks. 5 foto</span>
        </div>
        <div class="card-body">
          <div class="photo-upload-area" onclick="document.getElementById('foto-input').click()">
            <div class="photo-upload-icon">📷</div>
            <p style="font-weight:600;margin-bottom:.25rem">Tap untuk ambil/pilih foto</p>
            <p class="text-muted text-small">JPG, PNG, WEBP — Maks 5MB per foto</p>
          </div>
          <input type="file" id="foto-input" name="fotos[]" multiple accept="image/*" style="display:none" capture="environment">
          <div class="photo-grid mt-2" id="photo-preview"></div>
        </div>
      </div>

      <!-- STEP 2: Info Laporan -->
      <div class="card mb-2">
        <div class="card-header"><span style="font-weight:700">📝 Informasi Laporan</span></div>
        <div class="card-body">

          <div class="form-group">
            <label class="form-label">Judul Laporan <span class="required">*</span></label>
            <input type="text" id="judul" name="judul" class="form-control" placeholder="Contoh: TPS Ilegal di Jl. Merdeka" maxlength="200">
          </div>

          <div class="form-group">
            <label class="form-label">Kategori <span class="required">*</span></label>
            <select id="kategori" name="kategori" class="form-control">
              <option value="">-- Pilih Kategori --</option>
              <option value="TPS_ILEGAL">🗑️ TPS Ilegal</option>
              <option value="SAMPAH_JALAN">🛣️ Sampah di Jalan</option>
              <option value="SUNGAI">💧 Pencemaran Sungai</option>
              <option value="LAHAN_KOSONG">🌿 Lahan Kosong</option>
              <option value="FASILITAS_UMUM">🏛️ Fasilitas Umum</option>
              <option value="LAINNYA">📍 Lainnya</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Tingkat Urgensi <span class="required">*</span></label>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem">
              <?php foreach(['rendah'=>['🟢','Rendah'],'sedang'=>['🟡','Sedang'],'tinggi'=>['🔴','Tinggi'],'darurat'=>['🟣','Darurat']] as $val=>[$emoji,$label]): ?>
              <label style="cursor:pointer">
                <input type="radio" name="tingkat_urgensi" value="<?=$val?>" style="display:none" <?=$val==='sedang'?'checked':''?>>
                <div class="urgensi-chip" data-val="<?=$val?>" style="border:2px solid var(--border);border-radius:var(--radius-sm);padding:.5rem .25rem;text-align:center;transition:all .15s;<?=$val==='sedang'?'border-color:var(--primary);background:var(--primary-xlight)':''?>">
                  <div style="font-size:1.2rem"><?=$emoji?></div>
                  <div style="font-size:.7rem;font-weight:700;margin-top:.2rem"><?=$label?></div>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi <span class="required">*</span></label>
            <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" placeholder="Ceritakan kondisi sampah, sudah berapa lama, dampaknya, dll..." maxlength="2000"></textarea>
            <div style="font-size:.72rem;color:var(--text-light);text-align:right;margin-top:.25rem">
              <span id="charCount">0</span>/2000
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
              <span>Laporan Anonim?</span>
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:500">
                <input type="checkbox" name="is_anonim" value="1" id="isAnonim" style="width:18px;height:18px;accent-color:var(--primary)">
                <span class="text-small">Ya, sembunyikan nama</span>
              </label>
            </label>
          </div>
        </div>
      </div>

      <!-- STEP 3: Lokasi GPS -->
      <div class="card mb-2">
        <div class="card-header">
          <span style="font-weight:700">📍 Lokasi Sampah</span>
          <button type="button" onclick="GPS.getCurrentLocation('latitude','longitude','alamat_lengkap')" class="btn btn-primary btn-sm">
            🎯 GPS
          </button>
        </div>
        <div class="card-body">

          <!-- GPS Status -->
          <div class="map-status mb-2" id="gps-status">
            <div class="map-dot"></div>
            <span class="text-small">Klik tombol GPS atau sentuh peta untuk tandai lokasi</span>
          </div>

          <!-- Map -->
          <div id="map" style="height:260px;border-radius:var(--radius-lg);border:1px solid var(--border);margin-bottom:1rem"></div>

          <input type="hidden" id="latitude"  name="latitude"  required>
          <input type="hidden" id="longitude" name="longitude" required>

          <div class="form-group">
            <label class="form-label">Alamat Lengkap <span class="required">*</span></label>
            <textarea id="alamat_lengkap" name="alamat_lengkap" class="form-control" rows="2" placeholder="Alamat otomatis terisi dari GPS..."></textarea>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
            <div class="form-group">
              <label class="form-label">Kelurahan/Desa</label>
              <input type="text" id="kelurahan" name="kelurahan" class="form-control" placeholder="Otomatis">
            </div>
            <div class="form-group">
              <label class="form-label">Kecamatan</label>
              <input type="text" id="kecamatan" name="kecamatan" class="form-control" placeholder="Otomatis">
            </div>
            <div class="form-group">
              <label class="form-label">Kota/Kabupaten</label>
              <input type="text" id="kota" name="kota" class="form-control" placeholder="Otomatis">
            </div>
            <div class="form-group">
              <label class="form-label">Provinsi</label>
              <input type="text" id="provinsi" name="provinsi" class="form-control" placeholder="Otomatis">
            </div>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <button type="submit" id="submitBtn" class="btn btn-primary btn-lg btn-block mb-3">
        🚀 Kirim Laporan
      </button>

      <p class="text-center text-muted text-small mb-3">
        Laporan akan diverifikasi dalam 1×24 jam
      </p>
    </div>
  </form>
</div>

<script>
// Init photo upload
document.addEventListener('DOMContentLoaded', () => {
  PhotoUpload.init('foto-input', 'photo-preview', 5);

  // Init GPS map
  GPS.init('map', 'latitude', 'longitude', 'alamat_lengkap');

  // Char counter
  const desc = document.getElementById('deskripsi');
  desc?.addEventListener('input', () => {
    document.getElementById('charCount').textContent = desc.value.length;
  });

  // Urgensi chips
  document.querySelectorAll('.urgensi-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.urgensi-chip').forEach(c => {
        c.style.borderColor = 'var(--border)';
        c.style.background = '';
      });
      chip.style.borderColor = 'var(--primary)';
      chip.style.background  = 'var(--primary-xlight)';
      const radio = chip.closest('label').querySelector('input[type=radio]');
      if (radio) radio.checked = true;
    });
  });

  // Form submit
  document.getElementById('formLaporan').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');

    // Validate
    if (!Validate.form('formLaporan', {
      judul:          ['required', 'min:5'],
      kategori:       ['required'],
      deskripsi:      ['required', 'min:20'],
      alamat_lengkap: ['required'],
    })) return;

    if (!document.getElementById('latitude').value) {
      Toast.show('Lokasi belum ditandai', 'Klik GPS atau sentuh peta', 'warning'); return;
    }

    // Build FormData
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div> Mengirim...';

    const fd = new FormData(e.target);
    // Attach photo files
    PhotoUpload.getFiles().forEach(f => fd.append('fotos[]', f));

    try {
      const res = await fetch('../api/laporan-store.php', { method:'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        Toast.show('Laporan Terkirim!', data.message, 'success');
        setTimeout(() => window.location = '../pages/riwayat.php', 1500);
      } else {
        Toast.show('Gagal', data.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '🚀 Kirim Laporan';
      }
    } catch(err) {
      Toast.show('Error', 'Terjadi kesalahan jaringan', 'error');
      btn.disabled = false;
      btn.innerHTML = '🚀 Kirim Laporan';
    }
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
