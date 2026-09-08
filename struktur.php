<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi/koneksi.php';

$profile = null;
if (isset($koneksi) && $koneksi instanceof mysqli) { 
    $result = $koneksi->query("SELECT * FROM tb_profile LIMIT 1");
    if ($result) { $profile = $result->fetch_assoc(); }
}
if (!$profile) {
    $profile = ['nama_perusahaan' => 'Buku Tamu Digital', 'foto' => 'default-logo.png'];
}

$page_title = "Struktur Organisasi - Dinas Komunikasi dan Informatika";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }
        .org-container {
            overflow-x: auto;
            padding: 3rem 1rem;
            text-align: center;
        }
        .org-box {
            background: #0E5CAD;
            color: white;
            border-radius: 10px;
            padding: 14px 18px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            width: 220px;
            transition: all 0.3s ease;
        }
        .org-box:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
        .org-box.fungsional { background: #fff; color: #495057; border: 2px dashed #6c757d; width: 240px; }
        .org-name { font-weight: 700; font-size: 0.85rem; margin-bottom: 4px; line-height: 1.3; }
        .org-title { font-size: 0.72rem; opacity: 0.9; line-height: 1.2; }
        
        /* Garis Pohon CSS */
        .tree ul { padding-top: 25px; position: relative; list-style: none; display: flex; justify-content: center; margin: 0; }
        .tree li { position: relative; padding: 25px 10px 0 10px; text-align: center; }
        .tree li::before, .tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid #0E5CAD; width: 50%; height: 25px; }
        .tree li::after { right: auto; left: 50%; border-left: 2px solid #0E5CAD; }
        .tree li:only-child::after, .tree li:only-child::before { display: none; }
        .tree li:only-child { padding-top: 0; }
        .tree li:first-child::before, .tree li:last-child::after { border: 0; }
        .tree li:last-child::before { border-right: 2px solid #0E5CAD; border-radius: 0 6px 0 0; }
        .tree li:first-child::after { border-radius: 6px 0 0 0; }
        .tree ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid #0E5CAD; width: 0; height: 25px; }
    </style>
</head>
<body>

    <div class="container py-5">
        <!-- Header Judul -->
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary text-uppercase">Struktur Organisasi</h3>
            <h5 class="text-dark">Dinas Komunikasi dan Informatika, Statistik dan Persandian</h5>
            <p class="text-muted small">Kabupaten Bandung</p>
            <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
            </a>
        </div>

        <!-- Bagan Struktur Pohon -->
        <div class="card shadow-lg border-0 rounded-4 p-4 bg-white">
            <div class="org-container">
                <div class="tree">
                    <ul>
                        <li>
                            <!-- KEPALA DINAS -->
                            <div class="org-box" style="width: 240px;">
                                <div class="org-name">H. Teguh Purwayadi, S.STP., M.Si</div>
                                <div class="org-title">Kepala Dinas</div>
                            </div>
                            
                            <ul>
                                <!-- JABATAN FUNGSIONAL -->
                                <li>
                                    <div class="org-box fungsional">
                                        <div class="org-name">Kelompok Jabatan Fungsional</div>
                                    </div>
                                </li>

                                <!-- SEKRETARIS -->
                                <li>
                                    <div class="org-box" style="width: 240px;">
                                        <div class="org-name">Hj. Irma Novita, S.H., Sp1</div>
                                        <div class="org-title">Sekretaris</div>
                                    </div>

                                    <ul>
                                        <!-- KASUBAG KEUANGAN -->
                                        <li>
                                            <div class="org-box">
                                                <div class="org-name">Resty Resmawati, SE., M.Ak</div>
                                                <div class="org-title">Kepala Sub Bagian Keuangan</div>
                                            </div>
                                        </li>
                                        <!-- KASUBAG UMUM & KEPEGAWAIAN -->
                                        <li>
                                            <div class="org-box">
                                                <div class="org-name">Muhammad Imam Sampurna, S.T</div>
                                                <div class="org-title">Kepala Sub Bagian Umum & Kepegawaian</div>
                                            </div>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>

                <!-- KEPALA BIDANG (Baris Bawah) -->
                <div class="row mt-5 g-3 justify-content-center">
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="org-box h-100 w-100">
                            <div class="org-name">Angga Surasendana, S.Kom., M.Si.</div>
                            <div class="org-title">Kepala Bidang Aplikasi Informatika</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="org-box h-100 w-100">
                            <div class="org-name">Firman Nugraha, S.Sos., M.Si</div>
                            <div class="org-title">Kepala Bidang Teknologi Informasi dan Komunikasi</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="org-box h-100 w-100">
                            <div class="org-name">Al Azhar, S.Sos</div>
                            <div class="org-title">Kepala Bidang Informasi dan Komunikasi Publik</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="org-box h-100 w-100">
                            <div class="org-name">Asep Rochmansyah, S.Si., M.I.P</div>
                            <div class="org-title">Kepala Bidang Statistik</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="org-box h-100 w-100">
                            <div class="org-name">Santi Rosmayanti, S. STP</div>
                            <div class="org-title">Kepala Bidang Persandian</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>