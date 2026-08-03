<?php
session_start();

// Cek apakah admin sudah login, jika tidak, redirect ke halaman login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Memanggil file koneksi DULUAN, karena kita butuh $koneksi
require_once __DIR__ . '/../koneksi/koneksi.php'; // Pastikan path ini benar

// Mengambil nama admin dari session untuk ditampilkan
$admin_nama = htmlspecialchars($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin'));
$page_title = "Dashboard Admin";

// Inisialisasi variabel statistik
$total_tamu_hari_ini = 0;
$total_survei_hari_ini = 0;
$total_tamu_bulan_ini = 0;
$total_tamu_keseluruhan = 0;

// Pastikan $koneksi ada dan merupakan objek mysqli yang valid
if (isset($koneksi) && $koneksi instanceof mysqli) {

    // 1. Total Tamu Hari Ini
    $sql_tamu_today = "SELECT COUNT(*) as total FROM tb_tamu WHERE tanggal_kunjungan = CURDATE()";
    $result_tamu_today = $koneksi->query($sql_tamu_today);
    if ($result_tamu_today) {
        $total_tamu_hari_ini = $result_tamu_today->fetch_assoc()['total'] ?? 0;
    } else {
        error_log("Error query total_tamu_hari_ini: " . $koneksi->error);
    }

    // 2. Total Survei Hari Ini
    $sql_survei_today = "SELECT COUNT(*) as total FROM tb_kepuasan WHERE tanggal_survei = CURDATE()";
    $result_survei_today = $koneksi->query($sql_survei_today);
    if ($result_survei_today) {
        $total_survei_hari_ini = $result_survei_today->fetch_assoc()['total'] ?? 0;
    } else {
        error_log("Error query total_survei_hari_ini: " . $koneksi->error);
    }

    // 3. Total Tamu Bulan Ini
    $sql_tamu_month = "SELECT COUNT(*) as total FROM tb_tamu WHERE MONTH(tanggal_kunjungan) = MONTH(CURDATE()) AND YEAR(tanggal_kunjungan) = YEAR(CURDATE())";
    $result_tamu_month = $koneksi->query($sql_tamu_month);
    if ($result_tamu_month) {
        $total_tamu_bulan_ini = $result_tamu_month->fetch_assoc()['total'] ?? 0;
    } else {
        error_log("Error query total_tamu_bulan_ini: " . $koneksi->error);
    }

    // 4. Total Tamu Keseluruhan
    $sql_tamu_all = "SELECT COUNT(*) as total FROM tb_tamu";
    $result_tamu_all = $koneksi->query($sql_tamu_all);
    if ($result_tamu_all) {
        $total_tamu_keseluruhan = $result_tamu_all->fetch_assoc()['total'] ?? 0;
    } else {
        error_log("Error query total_tamu_keseluruhan: " . $koneksi->error);
    }

    // 5. Skor Kepuasan (Rata-rata rating)
    // Asumsi: rata-rata dari semua kolom nilai untuk semua data
    $sql_avg_kepuasan = "SELECT AVG((nilai_pelayanan + nilai_fasilitas + nilai_keramahan + nilai_kecepatan) / 4) as avg_score FROM tb_kepuasan";
    $result_avg = $koneksi->query($sql_avg_kepuasan);
    $skor_kepuasan = 0;
    if ($result_avg) {
        $row_avg = $result_avg->fetch_assoc();
        $skor_kepuasan = number_format((float)$row_avg['avg_score'], 1); // 1 desimal, misal 4.5
    }

    // 6. Data Tren (7 Hari Terakhir)
    $list_tanggal = [];
    $list_jumlah = [];
    
    // Generate dates for last 7 days to ensure 0 values are represented if used in array mapping, 
    // but simpler approach is to trust query and fill gaps in JS or PHP. 
    // Here we just fetch what exists.
    $sql_trend = "SELECT DATE(tanggal_kunjungan) as tgl, COUNT(*) as jumlah 
                  FROM tb_tamu 
                  WHERE tanggal_kunjungan >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                  GROUP BY tgl ORDER BY tgl ASC";
    $result_trend = $koneksi->query($sql_trend);

    // Siapkan array kosong 7 hari terakhir
    $data_trend = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $data_trend[$d] = 0;
    }

    if ($result_trend) {
        while ($row = $result_trend->fetch_assoc()) {
             $data_trend[$row['tgl']] = (int)$row['jumlah'];
        }
    }
    
    // Konversi ke index array untuk JS
    $labels_chart = array_keys($data_trend);
    $data_chart = array_values($data_trend);

    // $koneksi->close(); // Tidak perlu ditutup di sini jika masih ada potensi penggunaan di partials atau bagian lain.
                       // PHP akan menutupnya otomatis.
} else {
    // Handle jika $koneksi tidak tersedia (seharusnya tidak terjadi jika require_once berhasil)
    error_log("Variabel koneksi tidak tersedia atau bukan instance mysqli di admin/index.php");
    // Anda bisa set pesan error di sini jika mau
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Buku Tamu Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
    <style>
        /* Stat Cards Specifics */
        .card-admin-stat {
            border-left: 0px solid transparent; /* Optional accent */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-admin-stat .stat-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        .stat-icon-wrapper.bg-soft-primary { background-color: rgba(78, 115, 223, 0.1); color: #4e73df; }
        .stat-icon-wrapper.bg-soft-success { background-color: rgba(28, 200, 138, 0.1); color: #1cc88a; }
        .stat-icon-wrapper.bg-soft-info { background-color: rgba(54, 185, 204, 0.1); color: #36b9cc; }
        .stat-icon-wrapper.bg-soft-warning { background-color: rgba(246, 194, 62, 0.1); color: #f6c23e; }

        .card-admin-stat h5 {
            color: #8898aa;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-admin-stat .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 0;
            line-height: 1.2;
        }
        .card-admin-stat .card-link {
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
            transition: color 0.2s;
        }
        .card-admin-stat .card-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php
    if (file_exists(__DIR__ . '/_partials/navbar.php')) {
        include_once __DIR__ . '/_partials/navbar.php';
    }
    if (file_exists(__DIR__ . '/_partials/sidebar.php')) {
        include_once __DIR__ . '/_partials/sidebar.php';
    }
    ?>

    <main class="main-content">
        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-4 mb-4 border-bottom-0">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1"><?php echo htmlspecialchars($page_title); ?></h1>
                     <p class="text-muted mb-0">Ringkasan aktivitas dan statistik terbaru.</p>
                </div>
                
                 <!-- Breadcrumb (Optional) or Actions -->
            </div>
            
            <div class="row">
                <!-- Card 1: Tamu Hari Ini -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-admin-stat h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5>Total Tamu (Hari Ini)</h5>
                                    <p class="stat-value"><?php echo number_format($total_tamu_hari_ini); ?></p>
                                </div>
                                <div class="stat-icon-wrapper bg-soft-primary">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                            </div>
                            <a href="data_tamu.php" class="card-link text-primary">
                                Lihat Detail <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Skor Kepuasan -->
                <div class="col-lg-3 col-md-6 mb-4">
                     <div class="card card-admin-stat h-100">
                        <div class="card-body">
                             <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5>Skor Kepuasan</h5>
                                    <p class="stat-value"><?php echo $skor_kepuasan; ?> <span class="text-muted fs-6 fw-normal">/ 5.0</span></p>
                                </div>
                                <div class="stat-icon-wrapper bg-soft-success">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            </div>
                            <a href="data_kepuasan.php" class="card-link text-success">
                                Lihat Hasil <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Bulan Ini -->
                <div class="col-lg-3 col-md-6 mb-4">
                     <div class="card card-admin-stat h-100">
                        <div class="card-body">
                             <div class="d-flex justify-content-between align-items-start">
                                <div>
                                     <h5>Tamu Bulan Ini</h5>
                                    <p class="stat-value"><?php echo number_format($total_tamu_bulan_ini); ?></p>
                                </div>
                                <div class="stat-icon-wrapper bg-soft-info">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                            </div>
                             <a href="data_tamu.php" class="card-link text-info">
                                Lihat Detail <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Total Semua -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card card-admin-stat h-100">
                        <div class="card-body">
                             <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5>Total Keseluruhan</h5>
                                    <p class="stat-value"><?php echo number_format($total_tamu_keseluruhan); ?></p>
                                </div>
                                <div class="stat-icon-wrapper bg-soft-warning">
                                    <i class="bi bi-journal-album"></i>
                                </div>
                            </div>
                             <a href="data_tamu.php" class="card-link text-warning">
                                Lihat Arsip <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Grafik Tren Kunjungan -->
             <div class="row mb-5">
                 <div class="col-xl-8 col-lg-7">
                     <div class="card shadow mb-4">
                         <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                             <h6 class="m-0 font-weight-bold text-primary">Tren Kunjungan (7 Hari Terakhir)</h6>
                         </div>
                         <div class="card-body">
                             <div class="chart-area">
                                 <canvas id="myAreaChart"></canvas>
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="col-xl-4 col-lg-5">
                      <!-- Bisa ditambahkan widget lain di sini, misal pie chart jenis keperluan -->
                      <div class="card shadow mb-4">
                         <div class="card-header py-3">
                             <h6 class="m-0 font-weight-bold text-primary">Informasi Cepat</h6>
                         </div>
                         <div class="card-body">
                             <p class="mb-1"><i class="bi bi-info-circle text-info"></i> Jam sibuk biasanya terjadi antara pukul 09:00 - 11:00.</p>
                             <hr>
                             <p class="mb-0 small text-muted">Data diperbarui secara real-time.</p>
                         </div>
                     </div>
                 </div>
             </div>

            <div class="mt-4">
                <h4>Aktivitas Terbaru:</h4>
                <div class="list-group">
                    <?php
                        // Ambil 3 tamu terakhir sebagai contoh aktivitas
                        $sql_aktivitas = "SELECT nama_tamu, keperluan, waktu_masuk, tanggal_kunjungan FROM tb_tamu ORDER BY tanggal_kunjungan DESC, waktu_masuk DESC LIMIT 3";
                        $result_aktivitas = isset($koneksi) ? $koneksi->query($sql_aktivitas) : null;
                        if ($result_aktivitas && $result_aktivitas->num_rows > 0) {
                            while($aktivitas = $result_aktivitas->fetch_assoc()):
                    ?>
                    <a href="data_tamu.php" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Tamu: <?php echo htmlspecialchars($aktivitas['nama_tamu']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($aktivitas['tanggal_kunjungan'] . ' ' . $aktivitas['waktu_masuk']))); ?></small>
                        </div>
                        <p class="mb-1">Keperluan: <?php echo nl2br(htmlspecialchars(mb_strimwidth($aktivitas['keperluan'], 0, 100, "..."))); ?></p>
                    </a>
                    <?php
                            endwhile;
                        } else {
                            echo '<div class="list-group-item"><p class="mb-1">Belum ada aktivitas tamu terbaru.</p></div>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Toggle sidebar di mobile
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn'); 
        const adminSidebar = document.getElementById('adminSidebar'); 

        if (sidebarToggleBtn && adminSidebar) {
            sidebarToggleBtn.addEventListener('click', function() {
                adminSidebar.classList.toggle('active');
            });
        }

        // --- Chart.js Implementasi ---
        const ctx = document.getElementById('myAreaChart');
        if (ctx) {
            // Data dari PHP
            const labels = <?php echo json_encode($labels_chart); ?>;
            const data = <?php echo json_encode($data_chart); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Kunjungan',
                        data: data,
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#2e59d9',
                        borderColor: '#4e73df',
                        borderWidth: 1,
                        borderRadius: 5,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 7
                            }
                        },
                        y: {
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                callback: function(value, index, values) {
                                    return value; // Format angka jika perlu
                                }
                            },
                            grid: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        },
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFont: {
                                size: 14,
                            },
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            intersect: false,
                            mode: 'index',
                            caretPadding: 10,
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
