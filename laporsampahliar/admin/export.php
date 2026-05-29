
<?php

require_once __DIR__ . '/../includes/config.php';

requireAdmin();

$db = getDB();

$stmt = $db->query("
SELECT 
    laporan.kode_laporan,
    laporan.judul,
    laporan.kategori,
    laporan.status,
    laporan.tingkat_urgensi,
    laporan.kota,
    laporan.created_at,
    users.nama_lengkap
FROM laporan
JOIN users ON users.id = laporan.user_id
ORDER BY laporan.created_at DESC
");

$data = $stmt->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="laporan-sampah.csv"');

$output = fopen("php://output", "w");

fputcsv($output, [
    'Kode',
    'Judul',
    'Kategori',
    'Status',
    'Urgensi',
    'Kota',
    'Pelapor',
    'Tanggal'
]);

foreach ($data as $row) {

    fputcsv($output, [
        $row['kode_laporan'],
        $row['judul'],
        $row['kategori'],
        $row['status'],
        $row['tingkat_urgensi'],
        $row['kota'],
        $row['nama_lengkap'],
        $row['created_at']
    ]);
}

fclose($output);

exit;
