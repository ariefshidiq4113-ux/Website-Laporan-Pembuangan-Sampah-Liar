```php
<?php

require_once __DIR__ . '/../includes/config.php';

requireLogin();

$db = getDB();

$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

$user = $stmt->fetch();


// Statistik laporan
$totalLaporan = 0;

if ($user['role'] == 'warga') {

    $q = $db->prepare("SELECT COUNT(*) as total FROM laporan WHERE user_id = ?");
    $q->execute([$userId]);

    $totalLaporan = $q->fetch()['total'];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body{
            background:#f3f4f6;
        }

        .profile-card{
            border:none;
            border-radius:20px;
            overflow:hidden;
            box-shadow:0 10px 25px rgba(0,0,0,0.08);
        }

        .profile-header{
            background:linear-gradient(135deg,#16a34a,#22c55e);
            padding:40px;
            text-align:center;
            color:white;
        }

        .profile-photo{
            width:130px;
            height:130px;
            object-fit:cover;
            border-radius:50%;
            border:5px solid white;
            margin-bottom:15px;
        }

        .badge-role{
            padding:8px 15px;
            border-radius:20px;
            font-size:13px;
        }

        .info-item{
            padding:15px;
            border-bottom:1px solid #eee;
        }

        .info-item:last-child{
            border-bottom:none;
        }

        .btn-custom{
            border-radius:12px;
            padding:10px 20px;
        }

    </style>
</head>
<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card profile-card">

                <!-- Header -->
                <div class="profile-header">

                    <img
                    src="../assets/uploads/profile/<?=
                    $user['foto_profil'] ?? 'default.png'; ?>"
                    class="profile-photo">

                    <h3><?= $user['nama_lengkap']; ?></h3>

                    <p><?= $user['email']; ?></p>

                    <?php if($user['role'] == 'admin'): ?>

                        <span class="badge bg-danger badge-role">
                            ADMIN
                        </span>

                    <?php elseif($user['role'] == 'petugas'): ?>

                        <span class="badge bg-warning text-dark badge-role">
                            PETUGAS
                        </span>

                    <?php else: ?>

                        <span class="badge bg-success badge-role">
                            WARGA
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Body -->
                <div class="card-body p-4">

                    <div class="row">

                        <div class="col-md-6">

                            <div class="info-item">
                                <small class="text-muted">Nama Lengkap</small>
                                <h6><?= $user['nama_lengkap']; ?></h6>
                            </div>

                            <div class="info-item">
                                <small class="text-muted">Email</small>
                                <h6><?= $user['email']; ?></h6>
                            </div>

                            <div class="info-item">
                                <small class="text-muted">No HP</small>
                                <h6><?= $user['no_hp']; ?></h6>
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-item">
                                <small class="text-muted">NIK</small>
                                <h6><?= $user['nik'] ?: '-'; ?></h6>
                            </div>

                            <div class="info-item">
                                <small class="text-muted">Status Akun</small>
                                <h6><?= strtoupper($user['status']); ?></h6>
                            </div>

                            <div class="info-item">
                                <small class="text-muted">Total Laporan</small>
                                <h6><?= $totalLaporan; ?> laporan</h6>
                            </div>

                        </div>

                    </div>

                    <hr>

                    <div class="d-flex gap-2 flex-wrap">

                        <a href="edit-profile.php"
                        class="btn btn-success btn-custom">

                            <i class="bi bi-pencil-square"></i>
                            Edit Profil

                        </a>

                        <a href="ganti-password.php"
                        class="btn btn-dark btn-custom">

                            <i class="bi bi-lock"></i>
                            Ganti Password

                        </a>

                        <a href="../index.php"
                        class="btn btn-secondary btn-custom">

                            <i class="bi bi-house"></i>
                            Beranda

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
```
