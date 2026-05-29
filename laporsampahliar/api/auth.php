<?php
/**
 * api/auth.php
 * Handles login & register
 */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed', [], 405);

$action = sanitize($_GET['action'] ?? '');

// ============================================
// REGISTER
// ============================================
if ($action === 'register') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) jsonResponse(false, 'Token tidak valid');

    $nama   = sanitize($_POST['nama_lengkap'] ?? '');
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $hp     = sanitize($_POST['no_hp'] ?? '');
    $nik    = sanitize($_POST['nik'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $confirm= $_POST['password_confirm'] ?? '';

    if (!$nama || !$email || !$hp || !$pass) jsonResponse(false, 'Semua field wajib diisi');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Format email tidak valid');
    if (strlen($pass) < 8) jsonResponse(false, 'Password minimal 8 karakter');
    if ($pass !== $confirm) jsonResponse(false, 'Password tidak cocok');

    $db = getDB();

    // Cek email duplikat
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) jsonResponse(false, 'Email sudah terdaftar');

    // Cek NIK duplikat
    if ($nik) {
        $checkNik = $db->prepare("SELECT id FROM users WHERE nik = ?");
        $checkNik->execute([$nik]);
        if ($checkNik->fetch()) jsonResponse(false, 'NIK sudah terdaftar');
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (nama_lengkap, email, no_hp, nik, alamat, password, email_verified) VALUES (?,?,?,?,?,?,1)");
    $stmt->execute([$nama, $email, $hp, $nik ?: null, $alamat, $hash]);

    $userId = $db->lastInsertId();
    kirimNotifikasi($userId, 'Selamat Datang!', "Akun Anda berhasil dibuat. Selamat melapor!", 'sukses');

    jsonResponse(true, 'Pendaftaran berhasil! Silakan login.');
}

// ============================================
// LOGIN
// ============================================
if ($action === 'login') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) jsonResponse(false, 'Token tidak valid');

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) jsonResponse(false, 'Email dan password wajib diisi');

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        jsonResponse(false, 'Email atau password salah');
    }

    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['nama']     = $user['nama_lengkap'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['foto']     = $user['foto_profil'];

    $redirect = match($user['role']) {
        'admin'   => APP_URL . '/admin/dashboard.php',
        'petugas' => APP_URL . '/admin/dashboard.php',
        default   => APP_URL . '/index.php'
    };

    jsonResponse(true, 'Login berhasil', ['redirect' => $redirect, 'role' => $user['role']]);
}

// ============================================
// LOGOUT
// ============================================
if ($action === 'logout') {
    startSession();
    session_destroy();
    header('Location: ' . APP_URL . '/pages/login.php');
    exit;
}

jsonResponse(false, 'Action tidak dikenal', [], 400);
