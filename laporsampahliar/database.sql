-- ============================================
-- DATABASE: laporsampahliar
-- Sistem Pelaporan Sampah Liar
-- ============================================

CREATE DATABASE IF NOT EXISTS laporsampahliar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laporsampahliar;

-- ============================================
-- TABEL USERS (Warga Pelapor)
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nik VARCHAR(20) UNIQUE,
    alamat TEXT,
    foto_profil VARCHAR(255) DEFAULT 'default.png',
    role ENUM('warga','admin','petugas') DEFAULT 'warga',
    status ENUM('aktif','nonaktif','pending') DEFAULT 'aktif',
    token_reset VARCHAR(255) NULL,
    token_verifikasi VARCHAR(255) NULL,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABEL LAPORAN
-- ============================================
CREATE TABLE laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_laporan VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    kategori ENUM('TPS_ILEGAL','SAMPAH_JALAN','SUNGAI','LAHAN_KOSONG','FASILITAS_UMUM','LAINNYA') NOT NULL,
    tingkat_urgensi ENUM('rendah','sedang','tinggi','darurat') DEFAULT 'sedang',
    -- Lokasi
    alamat_lengkap TEXT NOT NULL,
    kelurahan VARCHAR(100),
    kecamatan VARCHAR(100),
    kota VARCHAR(100),
    provinsi VARCHAR(100),
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    -- Status Tracking
    status ENUM('menunggu','diverifikasi','diproses','selesai','ditolak') DEFAULT 'menunggu',
    -- Metadata
    jumlah_like INT DEFAULT 0,
    jumlah_komentar INT DEFAULT 0,
    is_anonim TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABEL FOTO LAPORAN
-- ============================================
CREATE TABLE foto_laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    path_file VARCHAR(500) NOT NULL,
    ukuran_file INT,
    tipe_file VARCHAR(50),
    is_thumbnail TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE
);

-- ============================================
-- TABEL TRACKING / RIWAYAT STATUS
-- ============================================
CREATE TABLE tracking_laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    status_baru ENUM('menunggu','diverifikasi','diproses','selesai','ditolak') NOT NULL,
    status_lama ENUM('menunggu','diverifikasi','diproses','selesai','ditolak'),
    keterangan TEXT,
    foto_bukti VARCHAR(255),
    diubah_oleh INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
    FOREIGN KEY (diubah_oleh) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- TABEL KOMENTAR
-- ============================================
CREATE TABLE komentar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    user_id INT NOT NULL,
    isi_komentar TEXT NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABEL LIKE LAPORAN
-- ============================================
CREATE TABLE like_laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (laporan_id, user_id),
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABEL NOTIFIKASI
-- ============================================
CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    laporan_id INT,
    judul VARCHAR(200) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('info','sukses','peringatan','error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE SET NULL
);

-- ============================================
-- TABEL PENUGASAN PETUGAS
-- ============================================
CREATE TABLE penugasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    petugas_id INT NOT NULL,
    catatan TEXT,
    deadline DATE,
    status ENUM('ditugaskan','dikerjakan','selesai') DEFAULT 'ditugaskan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
    FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABEL PENGATURAN APLIKASI
-- ============================================
CREATE TABLE pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kunci VARCHAR(100) UNIQUE NOT NULL,
    nilai TEXT,
    keterangan VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- DATA AWAL
-- ============================================

-- Admin default (password: Admin123!)
INSERT INTO users (nama_lengkap, email, no_hp, password, role, email_verified) VALUES
('Administrator', 'admin@laporsampahliar.id', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Petugas Kebersihan 1', 'petugas1@laporsampahliar.id', '081234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'petugas', 1),
('Warga Demo', 'warga@demo.id', '081234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'warga', 1);

INSERT INTO pengaturan (kunci, nilai, keterangan) VALUES
('nama_aplikasi', 'LaporSampahLiar', 'Nama aplikasi'),
('nama_instansi', 'Dinas Lingkungan Hidup', 'Nama instansi pengelola'),
('email_instansi', 'info@laporsampahliar.id', 'Email resmi instansi'),
('telepon_instansi', '(021) 1234-5678', 'Telepon instansi'),
('maks_foto', '5', 'Maksimum foto per laporan'),
('maks_ukuran_foto', '5', 'Maksimum ukuran foto dalam MB'),
('auto_notifikasi', '1', 'Kirim notifikasi otomatis'),
('warna_tema', '#16a34a', 'Warna tema utama'),
('koordinat_default_lat', '-6.2088', 'Koordinat default latitude'),
('koordinat_default_lng', '106.8456', 'Koordinat default longitude');

-- ============================================
-- DATA LAPORAN CONTOH
-- ============================================
INSERT INTO laporan (kode_laporan, user_id, judul, deskripsi, kategori, tingkat_urgensi, alamat_lengkap, kelurahan, kecamatan, kota, provinsi, latitude, longitude, status) VALUES
('RPT-2024-001', 3, 'TPS Ilegal di Pinggir Jalan Raya Serang', 'Terdapat tumpukan sampah besar di pinggir jalan yang sudah berhari-hari tidak diangkut. Baunya sangat menyengat dan mengganggu warga sekitar.', 'TPS_ILEGAL', 'tinggi', 'Jl. Raya Serang KM 15, depan Pasar Kramatwatu', 'Kramatwatu', 'Kramatwatu', 'Serang', 'Banten', -6.0512, 106.1234, 'diproses'),
('RPT-2024-002', 3, 'Sampah Mengotori Sungai Ciujung', 'Warga membuang sampah langsung ke sungai Ciujung sehingga air menjadi kotor dan menimbulkan bau tidak sedap.', 'SUNGAI', 'darurat', 'Bantaran Sungai Ciujung, Kampung Ciawi', 'Ciawi', 'Petir', 'Serang', 'Banten', -6.2810, 106.0987, 'diverifikasi'),
('RPT-2024-003', 3, 'Lahan Kosong Jadi Tempat Buang Sampah', 'Lahan kosong di belakang perumahan digunakan warga tidak bertanggung jawab sebagai tempat pembuangan sampah liar.', 'LAHAN_KOSONG', 'sedang', 'Perumahan Griya Asri Blok C, Cimanuk', 'Cimanuk', 'Cimanuk', 'Pandeglang', 'Banten', -6.3456, 105.8765, 'selesai');

INSERT INTO tracking_laporan (laporan_id, status_baru, status_lama, keterangan, diubah_oleh) VALUES
(1, 'menunggu', NULL, 'Laporan baru diterima sistem', 3),
(1, 'diverifikasi', 'menunggu', 'Laporan telah diverifikasi oleh admin', 1),
(1, 'diproses', 'diverifikasi', 'Petugas sudah ditugaskan untuk menangani', 1),
(2, 'menunggu', NULL, 'Laporan baru diterima sistem', 3),
(2, 'diverifikasi', 'menunggu', 'Laporan valid, menunggu penugasan petugas', 1),
(3, 'menunggu', NULL, 'Laporan baru diterima sistem', 3),
(3, 'diverifikasi', 'menunggu', 'Laporan valid', 1),
(3, 'diproses', 'diverifikasi', 'Sedang dikerjakan', 1),
(3, 'selesai', 'diproses', 'Sampah telah dibersihkan dan lokasi dirapikan', 1);
