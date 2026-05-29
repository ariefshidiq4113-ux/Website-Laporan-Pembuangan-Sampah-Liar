
<?php

require_once __DIR__ . '/../includes/config.php';

requireAdmin();

$db = getDB();

$stmt = $db->prepare("
SELECT * FROM users
WHERE role = 'warga'
ORDER BY created_at DESC
");

$stmt->execute();

$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Pengguna</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="admin-layout">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">

        <div class="admin-topbar">

            <h2>👥 Data Pengguna</h2>

        </div>

        <div class="admin-content">

            <div class="card">

                <div class="card-header">

                    <h3>Daftar Pengguna</h3>

                    <div class="search-bar" style="max-width:300px;">
                        <input type="text" placeholder="Cari pengguna...">
                    </div>

                </div>

                <div class="card-body">

                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>NIK</th>
                                    <th>Verifikasi</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach($users as $i => $row): ?>

                                <tr>

                                    <td><?= $i + 1 ?></td>

                                    <td>

                                        <div class="fw-700">
                                            <?= $row['nama_lengkap'] ?>
                                        </div>

                                    </td>

                                    <td>
                                        <?= $row['email'] ?>
                                    </td>

                                    <td>
                                        <?= $row['nik'] ?: '-' ?>
                                    </td>

                                    <td>

                                        <?php if($row['email_verified']): ?>

                                            <span class="badge badge-verified">
                                                Verified
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-waiting">
                                                Pending
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if($row['status'] == 'aktif'): ?>

                                            <span class="badge badge-done">
                                                Aktif
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-rejected">
                                                Nonaktif
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?= date('d M Y', strtotime($row['created_at'])) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>
