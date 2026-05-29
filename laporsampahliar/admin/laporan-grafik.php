```php id="m7v0bh"
<?php

require_once __DIR__ . '/../includes/config.php';

requireAdmin();

$db = getDB();

/* ============================================
   TOTAL STATISTIK
============================================ */

$totalLaporan = $db->query("
SELECT COUNT(*) FROM laporan
")->fetchColumn();

$totalSelesai = $db->query("
SELECT COUNT(*) FROM laporan
WHERE status='selesai'
")->fetchColumn();

$totalDiproses = $db->query("
SELECT COUNT(*) FROM laporan
WHERE status='diproses'
")->fetchColumn();

$totalMenunggu = $db->query("
SELECT COUNT(*) FROM laporan
WHERE status='menunggu'
")->fetchColumn();


/* ============================================
   DATA STATUS
============================================ */

$statusQuery = $db->query("
SELECT status, COUNT(*) as total
FROM laporan
GROUP BY status
");

$statusData = $statusQuery->fetchAll();

$statusLabels = [];
$statusTotals = [];

foreach($statusData as $row){

    $statusLabels[] = $row['status'];
    $statusTotals[] = $row['total'];

}


/* ============================================
   DATA KATEGORI
============================================ */

$kategoriQuery = $db->query("
SELECT kategori, COUNT(*) as total
FROM laporan
GROUP BY kategori
");

$kategoriData = $kategoriQuery->fetchAll();

$kategoriLabels = [];
$kategoriTotals = [];

foreach($kategoriData as $row){

    $kategoriLabels[] = $row['kategori'];
    $kategoriTotals[] = $row['total'];

}


/* ============================================
   DATA URGENSI
============================================ */

$urgensiQuery = $db->query("
SELECT tingkat_urgensi, COUNT(*) as total
FROM laporan
GROUP BY tingkat_urgensi
");

$urgensiData = $urgensiQuery->fetchAll();

$urgensiLabels = [];
$urgensiTotals = [];

foreach($urgensiData as $row){

    $urgensiLabels[] = $row['tingkat_urgensi'];
    $urgensiTotals[] = $row['total'];

}

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Grafik Laporan</title>

<link rel="stylesheet" href="../assets/css/style.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<div class="admin-layout">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">

        <div class="admin-topbar">

            <h2>📊 Grafik Laporan</h2>

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
                        <?= $totalLaporan ?>
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
                        <?= $totalSelesai ?>
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
                        <?= $totalDiproses ?>
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
                        <?= $totalMenunggu ?>
                    </div>

                    <div class="stat-label">
                        Menunggu
                    </div>

                </div>

            </div>


            <!-- Grafik -->
            <div class="grid"
                 style="
                 display:grid;
                 grid-template-columns:repeat(auto-fit,minmax(350px,1fr));
                 gap:1.5rem;
                 ">

                <!-- Status -->
                <div class="card">

                    <div class="card-header">

                        <h3>Status Laporan</h3>

                    </div>

                    <div class="card-body">

                        <div class="chart-container">

                            <canvas id="statusChart"></canvas>

                        </div>

                    </div>

                </div>


                <!-- Kategori -->
                <div class="card">

                    <div class="card-header">

                        <h3>Kategori Sampah</h3>

                    </div>

                    <div class="card-body">

                        <div class="chart-container">

                            <canvas id="kategoriChart"></canvas>

                        </div>

                    </div>

                </div>


                <!-- Urgensi -->
                <div class="card">

                    <div class="card-header">

                        <h3>Tingkat Urgensi</h3>

                    </div>

                    <div class="card-body">

                        <div class="chart-container">

                            <canvas id="urgensiChart"></canvas>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<script>

/* ============================================
   STATUS CHART
============================================ */

new Chart(document.getElementById('statusChart'), {

    type: 'bar',

    data: {

        labels: <?= json_encode($statusLabels) ?>,

        datasets: [{

            label: 'Jumlah Laporan',

            data: <?= json_encode($statusTotals) ?>,

            backgroundColor: [
                '#f59e0b',
                '#3b82f6',
                '#f97316',
                '#22c55e',
                '#ef4444'
            ],

            borderRadius: 10

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false

    }

});


/* ============================================
   KATEGORI CHART
============================================ */

new Chart(document.getElementById('kategoriChart'), {

    type: 'pie',

    data: {

        labels: <?= json_encode($kategoriLabels) ?>,

        datasets: [{

            data: <?= json_encode($kategoriTotals) ?>,

            backgroundColor: [
                '#16a34a',
                '#3b82f6',
                '#f59e0b',
                '#ef4444',
                '#7c3aed',
                '#f97316'
            ]

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false

    }

});


/* ============================================
   URGENSI CHART
============================================ */

new Chart(document.getElementById('urgensiChart'), {

    type: 'doughnut',

    data: {

        labels: <?= json_encode($urgensiLabels) ?>,

        datasets: [{

            data: <?= json_encode($urgensiTotals) ?>,

            backgroundColor: [
                '#22c55e',
                '#f59e0b',
                '#ef4444',
                '#7c3aed'
            ]

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false

    }

});

</script>

</body>
</html>
```
