
<?php

require_once __DIR__ . '/../includes/config.php';

requireAdmin();

$db = getDB();

$stmt = $db->prepare("
SELECT * FROM users
WHERE role = 'petugas'
ORDER BY created_at DESC
");

$stmt->execute();

$petugas = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Petugas</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="admin-layout">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">

        <div class="admin-topbar">

            <div class="flex items-center gap-2">

                <h2>👷 Data Petugas</h2>

            </div>

            <a href="tambah-petugas.php" class="btn btn-primary">
                + Tambah Petugas
            </a>

        </div>

        <div class="admin-content">

            <div class="card">

                <div class="card-header">

                    <h3>Daftar Petugas</h3>

                    <div class="search-bar" style="max-width:300px;">
                        <input type="text" placeholder="Cari petugas...">
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
                                    <th>No HP</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach($petugas as $i => $row): ?>

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
                                        <?= $row['no_hp'] ?>
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

                                        <div class="flex gap-1">

                                            <a href="edit-petugas.php?id=<?= $row['id'] ?>"
                                               class="btn btn-sm btn-outline">

                                                Edit

                                            </a>

                                            <a href="hapus-petugas.php?id=<?= $row['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Hapus petugas ini?')">

                                                Hapus

                                            </a>

                                        </div>

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
