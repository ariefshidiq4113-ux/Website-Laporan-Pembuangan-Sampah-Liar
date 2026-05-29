```php id="opqfux"
<?php

require_once __DIR__ . '/../includes/config.php';

requireLogin();
requireAdmin();

$db = getDB();

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "
SELECT 
    laporan.*,
    users.nama_lengkap
FROM laporan
JOIN users ON users.id = laporan.user_id
WHERE 1=1
";

$params = [];

if (!empty($search)) {

    $sql .= " AND (
        laporan.judul LIKE ?
        OR laporan.deskripsi LIKE ?
        OR users.nama_lengkap LIKE ?
    )";

    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {

    $sql .= " AND laporan.status = ?";

    $params[] = $status;
}

$sql .= " ORDER BY laporan.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$laporan = $stmt->fetchAll();


// Statistik
$total = $db->query("SELECT COUNT(*) FROM laporan")->fetchColumn();
$menunggu = $db->query("SELECT COUNT(*) FROM laporan WHERE status='menunggu'")->fetchColumn();
$diproses = $db->query("SELECT COUNT(*) FROM laporan WHERE status='diproses'")->fetchColumn();
$selesai = $db->query("SELECT COUNT(*) FROM laporan WHERE status='selesai'")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Laporan</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body{
            background:#f3f4f6;
        }

        .card-stat{
            border:none;
            border-radius:20px;
            color:white;
            padding:25px;
        }

        .table-container{
            background:white;
            border-radius:20px;
            padding:25px;
            box-shadow:0 5px 15px rgba(0,0,0,0.05);
        }

        .badge-status{
            padding:7px 12px;
            border-radius:20px;
            font-size:12px;
        }

        .menunggu{
            background:orange;
        }

        .diverifikasi{
            background:blue;
        }

        .diproses{
            background:purple;
        }

        .selesai{
            background:green;
        }

        .ditolak{
            background:red;
        }

    </style>
</head>
<body>

<div class="container py-5">

    <!-- Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold text-success">
                <i class="bi bi-trash"></i>
                Manajemen Laporan
            </h2>

            <p class="text-muted">
                Kelola seluruh laporan sampah masyarakat
            </p>

        </div>

        <a href="../dashboard.php" class="btn btn-dark">
            <i class="bi bi-arrow-left"></i>
            Dashboard
        </a>

    </div>

    <!-- Statistik -->
    <div class="row mb-4">

        <div class="col-md-3 mb-3">

            <div class="card-stat bg-success">

                <h3><?= $total; ?></h3>

                <p>Total Laporan</p>

            </div>

        </div>

        <div class="col-md-3 mb-3">

            <div class="card-stat bg-warning">

                <h3><?= $menunggu; ?></h3>

                <p>Menunggu</p>

            </div>

        </div>

        <div class="col-md-3 mb-3">

            <div class="card-stat bg-primary">

                <h3><?= $diproses; ?></h3>

                <p>Diproses</p>

            </div>

        </div>

        <div class="col-md-3 mb-3">

            <div class="card-stat bg-dark">

                <h3><?= $selesai; ?></h3>

                <p>Selesai</p>

            </div>

        </div>

    </div>

    <!-- Table -->
    <div class="table-container">

        <form method="GET" class="row mb-4">

            <div class="col-md-5 mb-2">

                <input type="text"
                name="search"
                class="form-control"
                placeholder="Cari laporan..."
                value="<?= htmlspecialchars($search); ?>">

            </div>

            <div class="col-md-4 mb-2">

                <select name="status" class="form-select">

                    <option value="">Semua Status</option>

                    <option value="menunggu">Menunggu</option>
                    <option value="diverifikasi">Diverifikasi</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                    <option value="ditolak">Ditolak</option>

                </select>

            </div>

            <div class="col-md-3 mb-2">

                <button class="btn btn-success w-100">

                    <i class="bi bi-search"></i>
                    Filter

                </button>

            </div>

        </form>

        <div class="table-responsive">

            <table id="tableLaporan"
            class="table table-bordered table-hover align-middle">

                <thead class="table-success">

                    <tr>

                        <th>No</th>
                        <th>Kode</th>
                        <th>Judul</th>
                        <th>Pelapor</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach($laporan as $i => $row): ?>

                        <tr>

                            <td><?= $i + 1; ?></td>

                            <td>
                                <?= $row['kode_laporan']; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['judul']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['nama_lengkap']); ?>
                            </td>

                            <td>
                                <?= $row['kategori']; ?>
                            </td>

                            <td>

                                <span class="badge badge-status <?= $row['status']; ?> text-white">

                                    <?= strtoupper($row['status']); ?>

                                </span>

                            </td>

                            <td>
                                <?= date('d M Y', strtotime($row['created_at'])); ?>
                            </td>

                            <td>

                                <div class="d-flex gap-1">

                                    <a href="detail-laporan.php?id=<?= $row['id']; ?>"
                                    class="btn btn-success btn-sm">

                                        <i class="bi bi-eye"></i>

                                    </a>

                                    <a href="edit-status.php?id=<?= $row['id']; ?>"
                                    class="btn btn-primary btn-sm">

                                        <i class="bi bi-pencil"></i>

                                    </a>

                                    <a href="hapus-laporan.php?id=<?= $row['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Hapus laporan ini?')">

                                        <i class="bi bi-trash"></i>

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

<!-- JQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>

new DataTable('#tableLaporan');

</script>

</body>
</html>
