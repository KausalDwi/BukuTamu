<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Sesuaikan path jika berbeda

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Detail Survei Kepuasan";
$survei_detail = null;
$error_message = '';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_kepuasan_to_view = $_GET['id'];

    // Fetch detail data survei kepuasan
    // Memilih semua kolom yang relevan dari tb_kepuasan
    $sql_select_detail = "SELECT ks.id_kepuasan, ks.id_tamu_fk, ks.nama_responden, 
                                 ks.tanggal_survei, ks.waktu_survei, 
                                 ks.nilai_pelayanan, ks.nilai_fasilitas, 
                                 ks.nilai_keramahan, ks.nilai_kecepatan, 
                                 ks.saran_masukan, ks.created_at,
                                 t.nama_tamu AS nama_tamu_terkait 
                          FROM tb_kepuasan ks
                          LEFT JOIN tb_tamu t ON ks.id_tamu_fk = t.id_tamu
                          WHERE ks.id_kepuasan = ?";
    
    if ($stmt_detail = $koneksi->prepare($sql_select_detail)) {
        $stmt_detail->bind_param("i", $id_kepuasan_to_view);
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();
        if ($result_detail->num_rows === 1) {
            $survei_detail = $result_detail->fetch_assoc();
        } else {
            $error_message = "Data survei kepuasan tidak ditemukan.";
            $_SESSION['message'] = $error_message;
            $_SESSION['message_type'] = "danger";
            header("Location: data_kepuasan.php");
            exit;
        }
        $stmt_detail->close();
    } else {
        $error_message = "Gagal menyiapkan statement untuk mengambil detail survei: " . $koneksi->error;
        error_log("SQL Prepare error for detail_kepuasan: " . $koneksi->error);
        $_SESSION['message'] = "Terjadi kesalahan saat mengambil data survei.";
        $_SESSION['message_type'] = "danger";
        header("Location: data_kepuasan.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID survei tidak valid atau tidak disediakan.";
    $_SESSION['message_type'] = "warning";
    header("Location: data_kepuasan.php");
    exit;
}

// Fungsi sederhana untuk menampilkan bintang rating (bisa dipindah ke file helper jika sering dipakai)
function display_rating_detail($rating_value) {
    $stars_html = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars_html .= ($i <= $rating_value) ? '<i class="bi bi-star-fill rating-stars"></i>' : '<i class="bi bi-star rating-stars"></i>';
    }
    return $stars_html . " <span class='ms-1'>($rating_value/5)</span>";
}

// $koneksi->close(); // Ditutup otomatis
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ($survei_detail ? ' #ID-' . $survei_detail['id_kepuasan'] : ''); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
    <style>
        
        .detail-label { font-weight: 600; color: #495057; }
        .detail-value { color: #212529; }
        .detail-group { margin-bottom: 1rem; display: flex; align-items: center; }
        .detail-group .detail-label { min-width: 180px; /* Agar label sejajar */ }
        .rating-stars { color: #ffc107; /* Warna untuk bintang rating */ }
        .saran-masukan-box { white-space: pre-wrap; background-color: #e9ecef; padding: 15px; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <?php
    if (file_exists(__DIR__ . '/_partials/navbar.php')) { include_once __DIR__ . '/_partials/navbar.php'; }
    if (file_exists(__DIR__ . '/_partials/sidebar.php')) { include_once __DIR__ . '/_partials/sidebar.php'; }
    ?>

    <main class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="data_kepuasan.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Kembali ke Daftar Survei
                </a>
            </div>

            <?php if ($error_message && !$survei_detail): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($survei_detail): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Detail Survei #<?php echo $survei_detail['id_kepuasan']; ?> 
                        (<?php echo htmlspecialchars(date('d M Y, H:i', strtotime($survei_detail['tanggal_survei'] . ' ' . $survei_detail['waktu_survei']))); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="detail-group">
                        <span class="detail-label"><i class="bi bi-person-fill me-2"></i>Nama Responden:</span>
                        <span class="detail-value">
                            <?php 
                            if (!empty($survei_detail['nama_responden'])) {
                                echo htmlspecialchars($survei_detail['nama_responden']);
                            } elseif (!empty($survei_detail['id_tamu_fk'])) {
                                echo 'Tamu: ' . htmlspecialchars($survei_detail['nama_tamu_terkait'] ?: 'ID ' . $survei_detail['id_tamu_fk']);
                                if ($survei_detail['nama_tamu_terkait']) {
                                     echo ' <a href="detail_tamu.php?id='.$survei_detail['id_tamu_fk'].'" class="ms-1" title="Lihat Detail Tamu"><i class="bi bi-box-arrow-up-right"></i></a>';
                                }
                            } else {
                                echo '<em>Anonim</em>';
                            }
                            ?>
                        </span>
                    </div>
                    <hr>
                    <h6 class="mt-3 mb-2 text-muted">Penilaian Layanan:</h6>
                    <div class="detail-group">
                        <span class="detail-label"><i class="bi bi-headset me-2"></i>Kualitas Pelayanan:</span>
                        <span class="detail-value"><?php echo display_rating_detail($survei_detail['nilai_pelayanan']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label"><i class="bi bi-building-gear me-2"></i>Fasilitas:</span>
                        <span class="detail-value"><?php echo display_rating_detail($survei_detail['nilai_fasilitas']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label"><i class="bi bi-emoji-smile-fill me-2"></i>Keramahan Staf:</span>
                        <span class="detail-value"><?php echo display_rating_detail($survei_detail['nilai_keramahan']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label"><i class="bi bi-clock-history me-2"></i>Kecepatan Layanan:</span>
                        <span class="detail-value"><?php echo display_rating_detail($survei_detail['nilai_kecepatan']); ?></span>
                    </div>
                    <hr>
                    <h6 class="mt-3 mb-2 text-muted">Saran dan Masukan:</h6>
                    <div class="saran-masukan-box">
                        <?php echo $survei_detail['saran_masukan'] ? nl2br(htmlspecialchars($survei_detail['saran_masukan'])) : '<em>Tidak ada saran atau masukan.</em>'; ?>
                    </div>
                     <hr>
                     <div class="detail-group">
                        <span class="detail-label"><i class="bi bi-calendar-plus me-2"></i>Survei Dibuat Pada:</span>
                        <span class="detail-value"><?php echo htmlspecialchars(date('d M Y, H:i:s', strtotime($survei_detail['created_at']))); ?></span>
                    </div>

                </div>
                <div class="card-footer text-end">
                    <a href="data_kepuasan.php" class="btn btn-secondary"><i class="bi bi-list-ul"></i> Kembali ke Daftar</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const adminSidebar = document.getElementById('adminSidebar');
        if (sidebarToggleBtn && adminSidebar) {
            sidebarToggleBtn.addEventListener('click', function() {
                adminSidebar.classList.toggle('active');
            });
        }
    </script>
</body>
</html>