
<?php

require_once __DIR__ . '/../includes/config.php';

requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($_POST as $kunci => $nilai) {

        $stmt = $db->prepare("
            UPDATE pengaturan 
            SET nilai = ?
            WHERE kunci = ?
        ");

        $stmt->execute([$nilai, $kunci]);
    }

    $success = "Pengaturan berhasil disimpan";
}

$stmt = $db->query("SELECT * FROM pengaturan");
$pengaturan = $stmt->fetchAll();

$data = [];

foreach ($pengaturan as $item) {
    $data[$item['kunci']] = $item['nilai'];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pengaturan</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="admin-layout">

 <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">

        <div class="admin-topbar">

            <h2>⚙️ Pengaturan Aplikasi</h2>

        </div>

        <div class="admin-content">

            <?php if(isset($success)): ?>

                <div class="alert alert-success">
                    <?= $success ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="card">

                    <div class="card-header">
                        <h3>Pengaturan Umum</h3>
                    </div>

                    <div class="card-body">

                        <div class="form-group">
                            <label class="form-label">
                                Nama Aplikasi
                            </label>

                            <input
                                type="text"
                                name="nama_aplikasi"
                                class="form-control"
                                value="<?= $data['nama_aplikasi'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Nama Instansi
                            </label>

                            <input
                                type="text"
                                name="nama_instansi"
                                class="form-control"
                                value="<?= $data['nama_instansi'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Email Instansi
                            </label>

                            <input
                                type="email"
                                name="email_instansi"
                                class="form-control"
                                value="<?= $data['email_instansi'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Telepon Instansi
                            </label>

                            <input
                                type="text"
                                name="telepon_instansi"
                                class="form-control"
                                value="<?= $data['telepon_instansi'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Warna Tema
                            </label>

                            <input
                                type="color"
                                name="warna_tema"
                                class="form-control"
                                value="<?= $data['warna_tema'] ?? '#16a34a' ?>">
                        </div>

                    </div>

                    <div class="card-footer">

                        <button class="btn btn-primary">
                            💾 Simpan Pengaturan
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </main>

</div>

</body>
</html>
