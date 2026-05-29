
<?php

require_once __DIR__ . '/../includes/config.php';

$db = getDB();

$stmt = $db->query("
SELECT 
    laporan.*,
    users.nama_lengkap
FROM laporan
JOIN users ON users.id = laporan.user_id
ORDER BY laporan.created_at DESC
");

$laporan = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Peta Laporan Sampah</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<!-- Leaflet CSS -->
<link rel="stylesheet"
href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>

body{
    background:#f3f4f6;
}

#map{
    height:600px;
    width:100%;
    border-radius:20px;
}

.map-container{
    background:white;
    padding:20px;
    border-radius:20px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

</style>
</head>
<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold text-success">
                Peta Laporan Sampah
            </h2>

            <p class="text-muted">
                Lokasi laporan masyarakat
            </p>
        </div>

        <a href="../index.php" class="btn btn-success">
            Kembali
        </a>

    </div>

    <div class="map-container">

        <div id="map"></div>

    </div>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>

// Default map
var map = L.map('map').setView([-6.2088, 106.8456], 10);

// Tile map
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {

    attribution: '&copy; OpenStreetMap'

}).addTo(map);


// Data laporan dari PHP
var laporan = <?= json_encode($laporan); ?>;


// Marker
laporan.forEach(function(item){

    if(item.latitude && item.longitude){

        var marker = L.marker([
            parseFloat(item.latitude),
            parseFloat(item.longitude)
        ]).addTo(map);

        marker.bindPopup(`
            <div style="width:200px">

                <h6>${item.judul}</h6>

                <p>${item.deskripsi.substring(0,100)}...</p>

                <small>
                    <b>Status:</b> ${item.status}
                </small>

                <br>

                <small>
                    <b>Pelapor:</b> ${item.nama_lengkap}
                </small>

            </div>
        `);

    }

});

</script>

</body>
</html>

