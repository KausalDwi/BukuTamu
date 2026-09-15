<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

$koneksiPath = __DIR__ . DIRECTORY_SEPARATOR . "koneksi" . DIRECTORY_SEPARATOR . "koneksi.php";
if (file_exists($koneksiPath)) {
    require_once $koneksiPath;
} else {
    die("File koneksi database tidak ditemukan.");
}

date_default_timezone_set('Asia/Jakarta');

$profile = null;
if (isset($koneksi) && $koneksi instanceof mysqli) { 
    $result = $koneksi->query("SELECT * FROM tb_profile LIMIT 1");
    if ($result) {
        $profile = $result->fetch_assoc();
    } else {
        $profile = ['nama_perusahaan' => 'Perusahaan Default', 'foto' => 'default-logo.png', 'foto2' => 'default-image.png'];
    }
} else {
    $profile = ['nama_perusahaan' => 'Perusahaan Default', 'foto' => 'default-logo.png', 'foto2' => 'default-image.png'];
}

$currentDay = mktime(0, 0, 0, (int)date("n"), (int)date("j"), (int)date("Y"));

function tglIndonesia(string $str): string {
    $translations = [
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jum\'at',
        'Sat' => 'Sabtu', 'January' => 'Januari', 'February' => 'Februari',
        'March' => 'Maret', 'April' => 'April', 'May' => 'Mei',
        'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
        'September' => 'September', 'October' => 'Oktober',
        'November' => 'November', 'December' => 'Desember'
    ];
    return strtr(trim($str), $translations);
}

$currentPage = $_GET['page'] ?? 'beranda';
$isiPath = __DIR__ . DIRECTORY_SEPARATOR . "isi.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Diskominfo - <?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Perusahaan') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-color: #0E5CAD;
            --accent-color: #FF9A70;
            --neutral-lightest: #F8F9FA;
            --neutral-lighter: #E9ECEF;
            --neutral-light: #DEE2E6;
            --neutral-dark: #495057;
            --neutral-darker: #343A40;
            --gradient-main: linear-gradient(135deg, rgb(121, 147, 241) 0%, rgb(61, 64, 153) 100%);
            --gradient-accent: linear-gradient(135deg, #FF9A70 0%, #FD5E53 100%);
            --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.1);
            --shadow-strong: 0 12px 35px rgba(0, 0, 0, 0.12);
            --radius-md: 0.75rem;
            --radius-lg: 1.25rem;
            --radius-full: 9999px;
            --transition-medium: all 0.35s ease-in-out;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--neutral-lightest);
            color: var(--neutral-darker);
            line-height: 1.7;
        }
        .app-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .app-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-soft);
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--neutral-light);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        .company-logo {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: var(--radius-md);
            border: 2px solid var(--primary-color);
        }
        .logo-badge {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(14, 92, 173, 0.1);
            border: 1px solid rgba(14, 92, 173, 0.2);
        }
        .company-title h1 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0;
        }
        .header-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: rgba(14, 92, 173, 0.1);
            color: var(--primary-color);
            font-size: 0.7rem;
            font-weight: 600;
        }
        /* Perbaikan Tombol Navigasi Atas agar Rapinya Pas dan Sejajar */
        .menu-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .menu-actions .btn {
            border-radius: var(--radius-full);
            padding: 0.5rem 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            transition: var(--transition-medium);
            box-shadow: var(--shadow-soft);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }
        .menu-actions .btn-gradient {
            background: var(--gradient-main);
            color: white;
            border: none;
        }
        .menu-actions .btn-outline-dynamic {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: #fff;
        }
        .menu-actions .btn-outline-dynamic:hover, .menu-actions .btn-gradient:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .main-content {
            flex: 1;
            padding: 2.5rem 0;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }
        @media (min-width: 992px) {
            .content-grid {
                grid-template-columns: 2fr 3fr;
            }
        }
        .welcome-section {
            background: var(--gradient-main);
            color: white;
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-strong);
            display: flex;
            flex-direction: column;
        }
        .form-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--neutral-lighter);
        }
        .section-header {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .section-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 70px;
            height: 4px;
            background: var(--gradient-accent);
            border-radius: var(--radius-full);
        }
        .section-header h2 {
            font-weight: 700;
            color: var(--primary-color);
        }
        .app-footer {
            padding: 2rem 0;
            background: #0f172a;
            color: #e2e8f0;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="app-container">
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo-container">
                    <span class="logo-badge">
                        <img src="admin/images/<?= htmlspecialchars($profile['foto'] ?? 'default-logo.png') ?>" alt="Logo" class="company-logo">
                    </span>
                    <div>
                        <div class="header-chip mb-1">
                            <i class="bi bi-stars"></i> Layanan Publik
                        </div>
                        <h1><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Instansi') ?></h1>
                    </div>
                </a>

                <div class="d-flex align-items-center gap-3">
                    <div class="clock-info d-none d-md-block text-end">
                        <div class="small text-muted"><?= tglIndonesia(date('D, d F Y', $currentDay)) ?></div>
                        <div id="clock" class="fw-bold text-primary small">
                            <i class="bi bi-clock"></i> <span class="time"></span>
                        </div>
                    </div>

                    <!-- Tombol Navigasi Atas -->
                    <div class="menu-actions">
                        <a href="?page=ketersediaan" class="btn btn-outline-dynamic">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                            <span class="d-none d-sm-inline">Cek Pegawai</span>
                        </a>

                        <a href="struktur.php" class="btn btn-outline-dynamic">
                            <i class="bi bi-diagram-3-fill"></i>
                            <span class="d-none d-sm-inline">Struktur Organisasi</span>
                        </a>

                        <a href="?page=spk" class="btn btn-gradient">
                            <i class="bi bi-star-fill"></i>
                            <span class="d-none d-sm-inline">Indeks Kepuasan</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                <section class="welcome-section animate__animated animate__fadeInLeft">
                    <span class="badge mb-3 bg-white bg-opacity-20 text-white align-self-start">
                        <i class="bi bi-stars"></i> Selamat Datang!
                    </span>
                    <h2 class="display-6 mb-3 fw-bold">Buku Tamu <?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Instansi') ?></h2>
                    <p class="lead mb-4 opacity-90 fs-6">Silakan isi data diri Anda pada formulir di samping untuk keperluan dokumentasi dan pelayanan yang lebih baik.</p>
                    
                    <div class="mt-auto text-center pt-3">
                        <img src="admin/images/<?= htmlspecialchars($profile['foto2'] ?? 'default-image.png') ?>" alt="Illustration" class="img-fluid" style="max-height: 180px; object-fit:contain;">
                    </div>
                </section>

                <section class="form-section animate__animated animate__fadeInRight">
                    <div class="section-header">
                        <h2 class="h3 mb-0">
                            <?php 
                            if ($currentPage === "spk") {
                                echo 'Formulir Kepuasan Layanan';
                            } elseif ($currentPage === "ketersediaan") {
                                echo 'Ketersediaan Pegawai';
                            } else {
                                echo 'Registrasi Kunjungan Tamu';
                            }
                            ?>
                        </h2>
                    </div>

                    <?php
                    if (file_exists($isiPath)) {
                        include $isiPath;
                    } else {
                        echo '<div class="alert alert-warning">Formulir tidak ditemukan.</div>';
                    }
                    ?>
                </section>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="container text-center">
            <div class="fw-bold text-white"><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Buku Tamu Digital') ?></div>
            <div class="text-muted small">Buku Tamu Digital • Layanan Publik Terpadu</div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toTimeString().split(' ')[0];
            const clockElement = document.querySelector('#clock .time');
            if (clockElement) clockElement.textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>