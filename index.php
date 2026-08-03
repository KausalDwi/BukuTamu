<?php
declare(strict_types=1);

// PENTING: session_start() harus diletakkan di baris paling atas sebelum ada output HTML apa pun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

// Pastikan file koneksi.php ada dan dapat diakses
$koneksiPath = __DIR__ . DIRECTORY_SEPARATOR . "koneksi" . DIRECTORY_SEPARATOR . "koneksi.php";
if (file_exists($koneksiPath)) {
    require_once $koneksiPath;
} else {
    die("File koneksi database tidak ditemukan. Harap periksa konfigurasi.");
}

date_default_timezone_set('Asia/Jakarta');

// Fetch company profile data
$profile = null;
if (isset($koneksi) && $koneksi instanceof mysqli) { 
    $result = $koneksi->query("SELECT * FROM tb_profile LIMIT 1");
    if ($result) {
        $profile = $result->fetch_assoc();
    } else {
        $profile = ['nama_perusahaan' => 'Perusahaan Default', 'foto' => 'default-logo.png', 'foto2' => 'default-image.png'];
    }
} else {
    $profile = ['nama_perusahaan' => 'Perusahaan Default (Koneksi Gagal)', 'foto' => 'default-logo.png', 'foto2' => 'default-image.png'];
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

// PANGGIL FILE isi.php DI ATAS SINI AGAR LOGIKA POST & REDIRECT BERJALAN SEBELUM HTML DIRENDER
$isiPath = __DIR__ . DIRECTORY_SEPARATOR . "isi.php";
if (file_exists($isiPath)) {
    // Kita buffer output dari isi.php agar bagian logika (PHP) tereksekusi duluan
    // sementara bagian tampilan HTML-nya bisa diletakkan di dalam tag <section class="form-section">
    // (Namun jika isi.php sudah diatur agar bagian logika dipisah dari HTML, kita bisa include langsung di sini atau di bawah)
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Diskominfo Lahat - <?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Perusahaan') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bright-gradient-start: rgb(121, 147, 241);
            --bright-gradient-end: rgb(61, 64, 153);
            --bright-gradient-alt-start: #FF9A70;
            --bright-gradient-alt-end: #FD5E53;

            --primary-color: #0E5CAD;
            --accent-color: #FF9A70;

            --neutral-lightest: #F8F9FA;
            --neutral-lighter: #E9ECEF;
            --neutral-light: #DEE2E6;
            --neutral-medium: #CED4DA;
            --neutral-dark: #495057;
            --neutral-darker: #343A40;
            --neutral-darkest: #212529;

            --gradient-main: linear-gradient(135deg, var(--bright-gradient-start) 0%, var(--bright-gradient-end) 100%);
            --gradient-accent: linear-gradient(135deg, var(--bright-gradient-alt-start) 0%, var(--bright-gradient-alt-end) 100%);
            --gradient-border: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            --primary-gradient: var(--gradient-main);

            --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.1);
            --shadow-strong: 0 12px 35px rgba(0, 0, 0, 0.12);

            --radius-sm: 0.375rem;
            --radius-md: 0.75rem;
            --radius-lg: 1.25rem;
            --radius-xl: 2rem;
            --radius-full: 9999px;

            --transition-fast: all 0.2s ease-in-out;
            --transition-medium: all 0.35s ease-in-out;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--neutral-lightest);
            color: var(--neutral-darker);
            line-height: 1.7;
            font-weight: 400;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .app-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-soft);
            padding: 1rem 0;
            border-bottom: 1px solid var(--neutral-light);
            position: sticky;
            top: 0;
            z-index: 1000;
            overflow: hidden;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .company-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: var(--radius-md);
            border: 2px solid var(--primary-color);
            box-shadow: var(--shadow-soft);
        }

        .logo-badge {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(78, 115, 223, 0.12);
            border: 1px solid rgba(78, 115, 223, 0.25);
        }

        .company-title h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .company-title small {
            font-size: 0.8rem;
            color: var(--neutral-dark);
            font-weight: 500;
        }

        .header-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: rgba(78, 115, 223, 0.12);
            color: #1e3a8a;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .menu-actions .btn {
            border-radius: var(--radius-full);
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition-medium);
            box-shadow: var(--shadow-soft);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .menu-actions .btn-gradient {
            background: var(--gradient-main);
            color: white;
            border: none;
        }

        .menu-actions .btn-outline-dynamic {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
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
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-strong);
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
            padding: 2.25rem 0 2rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            font-size: 0.875rem;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
        }

        .app-footer .footer-inner {
            display: grid;
            gap: 1.5rem;
            align-items: center;
        }

        .app-footer .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .app-footer .footer-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.08);
            padding: 6px;
        }
    </style>
</head>
<body class="app-container">
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo-container text-decoration-none">
                    <span class="logo-badge">
                        <img src="admin/images/<?= htmlspecialchars($profile['foto'] ?? 'default-logo.png') ?>" alt="Company Logo" class="company-logo">
                    </span>
                    <div class="company-title">
                        <div class="header-chip mb-2">
                            <i class="bi bi-stars"></i>
                            Layanan Publik Digital
                        </div>
                        <h1 class="mb-0"><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Perusahaan') ?></h1>
                    </div>
                </a>

                <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                    <div class="clock-info d-none d-md-block text-end">
                        <div class="date-display small text-muted"><?= tglIndonesia(date('D, d F Y', $currentDay)) ?></div>
                        <div id="clock" class="clock-display d-inline-flex align-items-center gap-1 fw-bold text-primary">
                            <i class="bi bi-clock"></i>
                            <span class="time"></span>
                        </div>
                    </div>

                    <div class="menu-actions">
                        <?php if ($currentPage === "spk"): ?>
                            <a href="index.php" class="btn btn-outline-dynamic">
                                <i class="bi bi-person-plus-fill"></i>
                                <span class="d-none d-sm-inline">Register Tamu</span>
                            </a>
                        <?php else: ?>
                            <a href="?page=spk" class="btn btn-gradient">
                                <i class="bi bi-star-fill"></i>
                                <span class="d-none d-sm-inline">Indeks Kepuasan</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                <section class="welcome-section animate__animated animate__fadeInLeft">
                    <div class="welcome-content">
                        <span class="badge mb-3 bg-white bg-opacity-20 text-white align-self-start">
                            <i class="bi bi-stars"></i> Selamat Datang!
                        </span>
                        <h2 class="display-5 mb-3 fw-bold">Buku Tamu <?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Instansi Kami') ?></h2>
                        <p class="lead mb-4 opacity-90"><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Instansi Kami') ?> senang menyambut Anda. Silakan isi data diri Anda pada formulir di samping untuk keperluan dokumentasi dan pelayanan yang lebih baik.</p>
                        
                        <div class="mt-auto text-center pt-4">
                            <img src="admin/images/<?= htmlspecialchars($profile['foto2'] ?? 'default-image.png') ?>" alt="Welcome Illustration" class="img-fluid" style="max-height: 200px; object-fit:contain;">
                        </div>
                    </div>
                </section>

                <section class="form-section animate__animated animate__fadeInRight">
                    <div class="section-header">
                        <h2 class="h3 mb-0">
                            <?= $currentPage === "spk" ? 'Formulir Kepuasan Layanan' : 'Registrasi Kunjungan Tamu' ?>
                        </h2>
                    </div>

                    <?php
                    // Memuat file isi.php yang berisi form dan logika pemrosesan
                    if (file_exists($isiPath)) {
                        include $isiPath;
                    } else {
                        echo '<div class="alert alert-warning" role="alert">Konten formulir (isi.php) tidak ditemukan.</div>';
                    }
                    ?>
                </section>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="container">
            <div class="footer-inner">
                <div class="footer-brand">
                    <img src="admin/images/<?= htmlspecialchars($profile['foto'] ?? 'default-logo.png') ?>" alt="Logo" class="footer-logo">
                    <div>
                        <div class="footer-title fw-bold text-white"><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Buku Tamu Digital') ?></div>
                        <div class="footer-subtitle text-muted small">Buku Tamu Digital • Layanan Publik Terpadu</div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Realtime Clock Script
        function updateClock() {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;

            const clockElement = document.querySelector('#clock .time');
            if (clockElement) clockElement.textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // SweetAlert Handler for Session Messages
        $(document).ready(function() {
            <?php
            if (isset($_SESSION['sukses'])) {
                echo "Swal.fire({
                    title: 'Berhasil!',
                    text: '" . addslashes($_SESSION['sukses']) . "',
                    icon: 'success',
                    confirmButtonColor: '#0E5CAD',
                    timer: 3500
                });";
                unset($_SESSION['sukses']);
            }
            if (isset($_SESSION['gagal'])) {
                echo "Swal.fire({
                    title: 'Gagal!',
                    text: '" . addslashes($_SESSION['gagal']) . "',
                    icon: 'error',
                    confirmButtonColor: '#FD5E53',
                    timer: 4000
                });";
                unset($_SESSION['gagal']);
            }
            ?>
        });
    </script>
</body>
</html>