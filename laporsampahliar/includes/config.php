<?php
// ============================================
// includes/config.php
// Konfigurasi Aplikasi & Koneksi Database
// ============================================

// --- Konfigurasi Database ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'laporsampahliar');
define('DB_CHARSET', 'utf8mb4');

// --- Konfigurasi Aplikasi ---
define('APP_NAME', 'LaporSampahLiar');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/laporsampahliar');
define('APP_PATH', dirname(__DIR__) . '/');

// --- Konfigurasi Upload ---
define('UPLOAD_PATH', APP_PATH . 'assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// --- Konfigurasi Session ---
define('SESSION_LIFETIME', 7200); // 2 jam

// --- Zona Waktu ---
date_default_timezone_set('Asia/Jakarta');

// --- Error Reporting (nonaktifkan di production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// Koneksi Database (PDO)
// ============================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Koneksi database gagal: ' . $e->getMessage()
            ]));
        }
    }
    return $pdo;
}

// ============================================
// Helper Functions
// ============================================

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_set_cookie_params(SESSION_LIFETIME);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isPetugas(): bool {
    startSession();
    return isLoggedIn() && in_array($_SESSION['role'], ['admin', 'petugas']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/pages/login.php');
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/pages/dashboard.php');
        exit;
    }
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateKodeLaporan(): string {
    $tahun = date('Y');
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as total FROM laporan WHERE YEAR(created_at) = $tahun");
    $row = $stmt->fetch();
    $nomor = str_pad($row['total'] + 1, 4, '0', STR_PAD_LEFT);
    return "RPT-{$tahun}-{$nomor}";
}

function uploadFoto(array $file, string $subfolder = 'laporan'): array {
    $uploadDir = UPLOAD_PATH . $subfolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error upload file'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    if (!in_array($file['type'], ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'Format file tidak didukung'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $namaFile = uniqid('foto_', true) . '.' . $ext;
    $destPath = $uploadDir . $namaFile;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }

    return [
        'success'  => true,
        'filename' => $namaFile,
        'path'     => $destPath,
        'url'      => UPLOAD_URL . $subfolder . '/' . $namaFile,
        'size'     => $file['size'],
        'type'     => $file['type']
    ];
}

function kirimNotifikasi(int $userId, string $judul, string $pesan, string $tipe = 'info', ?int $laporanId = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifikasi (user_id, laporan_id, judul, pesan, tipe) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $laporanId, $judul, $pesan, $tipe]);
    } catch (Exception $e) {
        // Silent fail
    }
}

function getStatusBadge(string $status): array {
    $map = [
        'menunggu'    => ['label' => 'Menunggu',    'class' => 'badge-waiting',  'icon' => '⏳'],
        'diverifikasi'=> ['label' => 'Diverifikasi', 'class' => 'badge-verified', 'icon' => '✅'],
        'diproses'    => ['label' => 'Diproses',    'class' => 'badge-process',  'icon' => '🔧'],
        'selesai'     => ['label' => 'Selesai',     'class' => 'badge-done',     'icon' => '🎉'],
        'ditolak'     => ['label' => 'Ditolak',     'class' => 'badge-rejected', 'icon' => '❌'],
    ];
    return $map[$status] ?? ['label' => $status, 'class' => 'badge-default', 'icon' => '•'];
}

function getUrgensiColor(string $urgensi): string {
    return match($urgensi) {
        'rendah'  => '#22c55e',
        'sedang'  => '#f59e0b',
        'tinggi'  => '#ef4444',
        'darurat' => '#7c3aed',
        default   => '#6b7280'
    };
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' tahun lalu';
    if ($diff->m > 0) return $diff->m . ' bulan lalu';
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

function jsonResponse(bool $success, string $message, array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function getCsrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
