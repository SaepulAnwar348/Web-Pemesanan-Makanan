<?php
session_start();
include '../koneksi.php';

// --- 1. LOGIKA LOGOUT ---
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: ../index.php"); 
    exit;
}

// --- 2. CEK SESI LOGIN ---
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../index.php");
    exit;
}

// --- 3. QUERY DATA DASHBOARD ---

// Ambil data total menu
$resMenu = mysqli_query($conn, "SELECT COUNT(*) AS total FROM menu");
$totalMenu = ($resMenu && ($row = mysqli_fetch_assoc($resMenu))) ? (int)($row['total'] ?? 0) : 0;

// Ambil total pesanan
$resPesanan = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan");
$totalPesanan = ($resPesanan && ($row = mysqli_fetch_assoc($resPesanan))) ? (int)($row['total'] ?? 0) : 0;

// Ambil total pendapatan (Keseluruhan dari pesanan selesai)
$resPendapatan = mysqli_query($conn, "SELECT SUM(total) AS total FROM pesanan WHERE status='Selesai'");
$pendapatan = ($resPendapatan && ($row = mysqli_fetch_assoc($resPendapatan))) ? (float)($row['total'] ?? 0) : 0;

// Total Pesanan Menunggu Konfirmasi
$waiting_orders_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan WHERE status='Menunggu'");
$totalMenunggu = ($waiting_orders_query && ($row = mysqli_fetch_assoc($waiting_orders_query))) ? (int)($row['total'] ?? 0) : 0;

// Ambil 5 pesanan terbaru (menggunakan LEFT JOIN agar pesanan tetap tampil meski data user terkait telah terhapus)
$pesananBaru = mysqli_query($conn, "SELECT p.*, u.nama FROM pesanan p LEFT JOIN users u ON p.id_user = u.id_user ORDER BY p.id_pesanan DESC LIMIT 5");

// Ambil menu dengan rating tertinggi
$menuTop = mysqli_query($conn, "SELECT * FROM menu ORDER BY rating_rata DESC LIMIT 5");


// LOGIKA GRAFIK PENDAPATAN
$grafikPendapatanResult = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(tanggal, '%Y-%m-%d') AS hari_key,
        DATE_FORMAT(tanggal, '%d %b') AS hari_label,
        DATE_FORMAT(tanggal, '%d %b %Y') AS hari_full, 
        SUM(total) AS total_pendapatan 
    FROM 
        pesanan 
    WHERE 
        status='Selesai' 
    GROUP BY 
        hari_key, hari_label, hari_full 
    ORDER BY 
        hari_key ASC
");

$dataHari = [];
$dataPendapatan = [];
$dataHariLengkap = [];

if ($grafikPendapatanResult) {
    while ($data = mysqli_fetch_assoc($grafikPendapatanResult)) {
        $dataHari[] = $data['hari_label'];
        $dataPendapatan[] = (float)$data['total_pendapatan'];
        $dataHariLengkap[] = $data['hari_full'];
    }
}

$jsonHari = json_encode($dataHari);
$jsonPendapatan = json_encode($dataPendapatan);
$jsonHariLengkap = json_encode($dataHariLengkap);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - ZIFOOD</title>
    <link rel="stylesheet" href="CSS/dashboard.admin.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   

    <script>
        function logoutConfirm() {
            return confirm("Yakin mau logout dari akun admin?");
        }
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="sidebar">
        <a href="dashboard.admin.php" class="nav-link active" title="Dashboard"><i class="fas fa-chart-line"></i></a>
        <a href="menu.php" class="nav-link" title="Kelola Menu"><i class="fas fa-utensils"></i></a>
        <a href="tambah.php" class="nav-link" title="Tambah Menu"><i class="fas fa-plus"></i></a>
        
        <a href="pesanan.php" class="nav-link" title="Kelola Pesanan">
            <i class="fas fa-receipt"></i>
            <?php if ($totalMenunggu > 0): ?>
                <span class="notification-badge"><?= $totalMenunggu; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="dashboard.admin.php?logout=true" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        
        <div class="header">
            <h1>Hello, <?= htmlspecialchars($_SESSION['username_admin'] ?? 'Admin'); ?>!</h1>
        </div>

        <div class="kpi-card revenue">
            <h3>Total Pendapatan</h3>
            <div class="kpi-value">Rp <?= number_format((float)$pendapatan, 0, ',', '.'); ?></div>
          
        </div>
        
        <div class="kpi-card">
            <h3>Total Pesanan</h3>
            <div class="kpi-value"><?= $totalPesanan; ?></div>
        </div>
        
        <div class="kpi-card">
            <h3>Total Menu</h3>
            <div class="kpi-value"><?= $totalMenu; ?></div>
        </div>
        
        <div class="chart-card">
            <h3>Pendapatan Harian</h3>
            <canvas id="pendapatanChart" style="max-height: 250px;"></canvas>
        </div>

        <div class="list-card">
            
            <div class="recent-orders">
                <h3>Pesanan Terbaru</h3>
                <div class="order-summary-kpi" style="background-color: var(--bg-light); padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: var(--theme-primary);"><?= $totalMenunggu; ?></div>
                    <small style="color: #666;">
                    Pesanan menunggu konfirmasi</small>
                </div>

                <?php if ($pesananBaru && mysqli_num_rows($pesananBaru) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($pesananBaru)): ?>
                        <div class="order-item">
                            <span>#<?= $row['id_pesanan']; ?> - <?= htmlspecialchars($row['nama'] ?? 'Pelanggan'); ?></span>
                            <span style="font-weight: 600; color: var(--text-dark);">Rp<?= number_format((float)($row['total'] ?? 0), 0, ',', '.'); ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="font-size: 12px; color: #999;">Belum ada pesanan terbaru.</p>
                <?php endif; ?>
                <a href="pesanan.php" style="display: block; text-align: center; margin-top: 15px; color: var(--theme-primary); text-decoration: none;">Lihat Semua Pesanan</a>
            </div>

            <div class="sales-by-category">
                <h3>Menu dengan Rating Tertinggi</h3>
                    <?php if ($menuTop && mysqli_num_rows($menuTop) > 0): ?>
                        <ol style="padding-left: 20px; margin: 0;">
                        <?php while ($m = mysqli_fetch_assoc($menuTop)): ?>
                            <li class="top-menu-item">
                                <span><?= htmlspecialchars($m['nama_menu'] ?? '-'); ?></span>
                                <span>⭐ <?= number_format((float)($m['rating_rata'] ?? 0), 1); ?></span>
                            </li>
                        <?php endwhile; ?>
                        </ol>
                    <?php else: ?>
                        <p style="font-size: 12px; color: #999;">Belum ada data menu top.</p>
                    <?php endif; ?>
            </div>
            
        </div>
        
    </div>
</div>

<script>
    const labelHari = <?= $jsonHari; ?>; 
    const dataTotalPendapatan = <?= $jsonPendapatan; ?>;
    const labelHariLengkap = <?= $jsonHariLengkap; ?>;

    const canvasElem = document.getElementById('pendapatanChart');
    if (canvasElem && typeof Chart !== 'undefined') {
        const ctx = canvasElem.getContext('2d');
        const pendapatanChart = new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: labelHari,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: dataTotalPendapatan,
                    backgroundColor: 'rgba(255, 87, 34, 0.7)',
                    borderColor: 'rgba(255, 87, 34, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                    maxBarThickness: 50,
                    barPercentage: 0.6, 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: true, borderDash: [5, 5] },
                        title: { display: true, text: 'Rupiah (Rp)' }
                    },
                    x: { 
                        grid: { display: false },
                        title: { display: true, text: 'Hari' } 
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        mode: 'index', 
                        intersect: false,
                        callbacks: {
                            title: function(items) {
                                if (!items.length) return '';
                                const idx = items[0].dataIndex;
                                return (labelHariLengkap && labelHariLengkap[idx]) ? labelHariLengkap[idx] : items[0].label;
                            },
                            label: function(context) {
                                const val = context.raw || 0;
                                return 'Pendapatan: Rp ' + Number(val).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }
</script>

</body>
</html>