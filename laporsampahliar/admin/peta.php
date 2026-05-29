```php id="j5brm1"
<?php

require_once __DIR__ . '/../includes/config.php';

requireAdmin();

$db = getDB();

/* ============================================
   AMBIL DATA LAPORAN
============================================ */

$query = "
SELECT 
    laporan.*,
    users.nama_lengkap
FROM laporan
JOIN users ON users.id = laporan.user_id
ORDER BY laporan.created_at DESC
";

$stmt = $db->query($query);

$laporan = $stmt->fetchAll();


/* ============================================
   TOTAL
============================================ */

$total = count($laporan);

$selesai = 0;
$diproses = 0;
$menunggu = 0;

foreach($laporan as $row){

    if($row['status'] == 'selesai'){
        $selesai++;
    }

    if($row['status'] == 'diproses'){
        $diproses++;
    }

    if($row['status'] == 'menunggu'){
        $menunggu++;
    }

}

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Peta Laporan</title>

<link rel="stylesheet"
href="../assets/css/style.css">

<link rel="stylesheet"
href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>

.admin-map{

    height: 650px;
    width: 100%;
    border-radius: var(--radius-lg);

}

.popup-title{

    font-weight: 700;
    margin-bottom: .5rem;

}

.popup-desc{

    font-size: .85rem;
    color: var(--text-muted);

}

.popup-meta{

    margin-top: .75rem;
    font-size: .8rem;

}

.legend{

    display:flex;
    gap:1rem;
    flex-wrap:wrap;

}

.legend-item{

    display:flex;
    align-items:center;
    gap:.4rem;
    font-size:.85rem;

}

.legend-dot{

    width:12px;
    height:12px;
    border-radius:50%;

}

</style>

</head>

<body>

<div class="admin-layout">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">

        <div class="admin-topbar">

            <h2>🗺️ Peta Laporan</h2>

        </div>

        <div class="admin-content">

            <!-- Statistik -->
            <div class="stats-grid mb-3">

                <div class="stat-card"
                     style="--stat-color:#16a34a">

                    <div class="stat-icon">
                        📄
                    </div>

                    <div class="stat-value">
                        <?= $total ?>
                    </div>

                    <div class="stat-label">
                        Total Laporan
                    </div>

                </div>

                <div class="stat-card"
                     style="--stat-color:#22c55e">

                    <div class="stat-icon">
                        ✅
                    </div>

                    <div class="stat-value">
                        <?= $selesai ?>
                    </div>

                    <div class="stat-label">
                        Selesai
                    </div>

                </div>

                <div class="stat-card"
                     style="--stat-color:#f59e0b">

                    <div class="stat-icon">
                        🔧
                    </div>

                    <div class="stat-value">
                        <?= $diproses ?>
                    </div>

                    <div class="stat-label">
                        Diproses
                    </div>

                </div>

                <div class="stat-card"
                     style="--stat-color:#ef4444">

                    <div class="stat-icon">
                        ⏳
                    </div>

                    <div class="stat-value">
                        <?= $menunggu ?>
                    </div>

                    <div class="stat-label">
                        Menunggu
                    </div>

                </div>

            </div>


            <!-- Card Map -->
            <div class="card">

                <div class="card-header">

                    <div>

                        <h3>Peta Persebaran Sampah</h3>

                        <p class="text-muted text-small">
                            Monitoring lokasi laporan masyarakat
                        </p>

                    </div>

                    <div class="legend">

                        <div class="legend-item">

                            <div class="legend-dot"
                                 style="background:#f59e0b"></div>

                            Diproses

                        </div>

                        <div class="legend-item">

                            <div class="legend-dot"
                                 style="background:#22c55e"></div>

                            Selesai

                        </div>

                        <div class="legend-item">

                            <div class="legend-dot"
                                 style="background:#ef4444"></div>

                            Menunggu

                        </div>

                    </div>

                </div>

                <div class="card-body">

                    <div id="map"
                         class="admin-map"></div>

                </div>

            </div>

        </div>

    </main>

</div>


<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>

/* ============================================
   INIT MAP
============================================ */

var map = L.map('map').setView(
    [-6.2088, 106.8456],
    10
);


/* ============================================
   TILE
============================================ */

L.tileLayer(
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',

{
    attribution:'© OpenStreetMap'
}

).addTo(map);


/* ============================================
   DATA
============================================ */

var laporan = <?= json_encode($laporan); ?>;


/* ============================================
   MARKER COLORS
============================================ */

function getColor(status){

    if(status == 'selesai'){
        return 'green';
    }

    if(status == 'diproses'){
        return 'orange';
    }

    if(status == 'menunggu'){
        return 'red';
    }

    return 'blue';

}


/* ============================================
   LOOP MARKER
============================================ */

laporan.forEach(function(item){

    if(item.latitude && item.longitude){

        var marker = L.circleMarker(
            [
                parseFloat(item.latitude),
                parseFloat(item.longitude)
            ],

            {
                radius:10,
                fillColor:getColor(item.status),
                color:'#fff',
                weight:2,
                opacity:1,
                fillOpacity:.9
            }

        ).addTo(map);


        marker.bindPopup(`

            <div style="width:230px;">

                <div class="popup-title">

                    ${item.judul}

                </div>

                <div class="popup-desc">

                    ${item.deskripsi.substring(0,120)}...

                </div>

                <div class="popup-meta">

                    <b>Status:</b>
                    ${item.status}

                    <br><br>

                    <b>Kategori:</b>
                    ${item.kategori}

                    <br><br>

                    <b>Pelapor:</b>
                    ${item.nama_lengkap}

                    <br><br>

                    <b>Kota:</b>
                    ${item.kota}

                </div>

            </div>

        `);

    }

});

</script>

</body>
</html>
```
