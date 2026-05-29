<?php
/**
 * api/laporan-store.php
 * API endpoint untuk menyimpan laporan baru
 */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

startSession();
if (!isLoggedIn()) {
    jsonResponse(false, 'Silakan login terlebih dahulu', [], 401);
}

// CSRF
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Token keamanan tidak valid', [], 403);
}

// Validasi input
$required = ['judul','kategori','deskripsi','alamat_lengkap','latitude','longitude'];
foreach ($required as $f) {
    if (empty($_POST[$f])) jsonResponse(false, "Field '$f' wajib diisi");
}

$judul         = sanitize($_POST['judul']);
$kategori      = sanitize($_POST['kategori']);
$deskripsi     = sanitize($_POST['deskripsi']);
$urgensi       = sanitize($_POST['tingkat_urgensi'] ?? 'sedang');
$alamat        = sanitize($_POST['alamat_lengkap']);
$kelurahan     = sanitize($_POST['kelurahan'] ?? '');
$kecamatan     = sanitize($_POST['kecamatan'] ?? '');
$kota          = sanitize($_POST['kota'] ?? '');
$provinsi      = sanitize($_POST['provinsi'] ?? '');
$lat           = (float)$_POST['latitude'];
$lng           = (float)$_POST['longitude'];
$isAnonim      = isset($_POST['is_anonim']) ? 1 : 0;
$userId        = (int)$_SESSION['user_id'];

// Validasi kategori & urgensi
$validKategori = ['TPS_ILEGAL','SAMPAH_JALAN','SUNGAI','LAHAN_KOSONG','FASILITAS_UMUM','LAINNYA'];
$validUrgensi  = ['rendah','sedang','tinggi','darurat'];
if (!in_array($kategori, $validKategori)) jsonResponse(false, 'Kategori tidak valid');
if (!in_array($urgensi,  $validUrgensi))  jsonResponse(false, 'Urgensi tidak valid');
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) jsonResponse(false, 'Koordinat tidak valid');

$db = getDB();

try {
    $db->beginTransaction();

    $kode = generateKodeLaporan();

    $stmt = $db->prepare("
        INSERT INTO laporan
          (kode_laporan, user_id, judul, deskripsi, kategori, tingkat_urgensi,
           alamat_lengkap, kelurahan, kecamatan, kota, provinsi, latitude, longitude, is_anonim)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([$kode,$userId,$judul,$deskripsi,$kategori,$urgensi,$alamat,$kelurahan,$kecamatan,$kota,$provinsi,$lat,$lng,$isAnonim]);
    $laporanId = $db->lastInsertId();

    // Simpan foto
    $fotoSaved = 0;
    if (!empty($_FILES['fotos']['name'][0])) {
        $fotos = $_FILES['fotos'];
        $fileCount = count($fotos['name']);
        for ($i = 0; $i < min($fileCount, 5); $i++) {
            if ($fotos['error'][$i] !== UPLOAD_ERR_OK) continue;
            $singleFile = [
                'name'     => $fotos['name'][$i],
                'type'     => $fotos['type'][$i],
                'tmp_name' => $fotos['tmp_name'][$i],
                'error'    => $fotos['error'][$i],
                'size'     => $fotos['size'][$i],
            ];
            $result = uploadFoto($singleFile, 'laporan');
            if ($result['success']) {
                $isThumbnail = ($fotoSaved === 0) ? 1 : 0;
                $stmtFoto = $db->prepare("INSERT INTO foto_laporan (laporan_id, nama_file, path_file, ukuran_file, tipe_file, is_thumbnail) VALUES (?,?,?,?,?,?)");
                $stmtFoto->execute([$laporanId, $result['filename'], $result['path'], $result['size'], $result['type'], $isThumbnail]);
                $fotoSaved++;
            }
        }
    }

    // Tracking awal
    $stmtTrack = $db->prepare("INSERT INTO tracking_laporan (laporan_id, status_baru, keterangan, diubah_oleh) VALUES (?,?,?,?)");
    $stmtTrack->execute([$laporanId, 'menunggu', 'Laporan baru diterima sistem', $userId]);

    // Notifikasi ke user
    kirimNotifikasi($userId, 'Laporan Terkirim!', "Laporan $kode telah diterima dan sedang menunggu verifikasi.", 'sukses', $laporanId);

    // Notifikasi ke semua admin
    $admins = $db->query("SELECT id FROM users WHERE role='admin'")->fetchAll();
    foreach ($admins as $admin) {
        kirimNotifikasi($admin['id'], 'Laporan Baru Masuk', "Laporan $kode - $judul membutuhkan verifikasi.", 'info', $laporanId);
    }

    $db->commit();
    jsonResponse(true, "Laporan $kode berhasil dikirim!", ['kode' => $kode, 'id' => $laporanId]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Laporan store error: " . $e->getMessage());
    jsonResponse(false, 'Terjadi kesalahan server. Coba lagi.', [], 500);
}
