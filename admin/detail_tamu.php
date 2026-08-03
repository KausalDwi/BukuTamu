<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Sesuaikan path jika berbeda

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Detail Kunjungan Tamu";
$tamu_detail = null;
$error_message = '';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_tamu_to_view = $_GET['id'];

    // Fetch detail data tamu
    $sql_select_detail = "SELECT id_tamu, tanggal_kunjungan, waktu_masuk, nama_tamu, 
                                 asal_instansi, jabatan, no_telepon, email_tamu, 
                                 bertemu_dengan, keperluan, catatan_tambahan, 
                                 foto_tamu, tanda_tangan, status_keluar, waktu_keluar, created_at 
                          FROM tb_tamu 
                          WHERE id_tamu = ?";
    
    if ($stmt_detail = $koneksi->prepare($sql_select_detail)) {
        $stmt_detail->bind_param("i", $id_tamu_to_view);
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();
        if ($result_detail->num_rows === 1) {
            $tamu_detail = $result_detail->fetch_assoc();
        } else {
            $error_message = "Data tamu tidak ditemukan.";
            $_SESSION['message'] = $error_message;
            $_SESSION['message_type'] = "danger";
            header("Location: data_tamu.php");
            exit;
        }
        $stmt_detail->close();
    } else {
        error_log("SQL Prepare error for detail_tamu: " . $koneksi->error);
        $_SESSION['message'] = "Terjadi kesalahan saat mengambil data.";
        $_SESSION['message_type'] = "danger";
        header("Location: data_tamu.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID tamu tidak valid atau tidak disediakan.";
    $_SESSION['message_type'] = "warning";
    header("Location: data_tamu.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ($tamu_detail ? ' - ' . htmlspecialchars($tamu_detail['nama_tamu']) : ''); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
    <style>

        /* Detail Styling */
        .detail-item { margin-bottom: 1rem; }
        .detail-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #858796;
            margin-bottom: 0.25rem;
            display: block;
        }
        .detail-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        /* Image Preview Box */
        .photo-preview {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            background-color: #f8f9fa;
            border: 1px solid #e3e6f0;
            padding: 0.5rem;
            text-align: center;
        }
        .photo-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            object-fit: contain;
            max-height: 400px;
        }
        .signature-preview img {
            max-height: 120px;
            border-bottom: 1px dashed #ccc;
        }
    </style>
</head>
<body>
    <?php
    if (file_exists(__DIR__ . '/_partials/navbar.php')) { include_once __DIR__ . '/_partials/navbar.php'; }
    if (file_exists(__DIR__ . '/_partials/sidebar.php')) { include_once __DIR__ . '/_partials/sidebar.php'; }
    ?>

    <main class="main-content">
        <div class="container-fluid px-0">
             <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-4 mb-4 border-bottom-0">
                <div>
                     <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="data_tamu.php" class="text-decoration-none text-muted">Data Tamu</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Detail Kunjungan</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center">
                        <h1 class="h3 fw-bold text-dark mb-0 me-3">Detail Tamu</h1>
                        <span class="badge rounded-pill bg-light text-dark border">
                            ID: #<?php echo str_pad($tamu_detail['id_tamu'], 4, '0', STR_PAD_LEFT); ?>
                        </span>
                    </div>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                    <a href="data_tamu.php" class="btn btn-light shadow-sm border text-muted">
                        <i class="bi bi-arrow-left me-2"></i> Kembali
                    </a>
                    <a href="export_tamu.php?id=<?php echo $tamu_detail['id_tamu']; ?>&action=print" target="_blank" class="btn btn-primary text-white shadow-sm border-0 d-flex align-items-center px-3" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                        <i class="bi bi-printer-fill me-2"></i> Cetak PDF
                    </a>
                </div>
            </div>

            <?php if ($tamu_detail): ?>
            <div class="row g-4">
                <!-- Left Column: Photo & Status -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-4 text-center">
                            <div class="photo-preview mb-3">
                                <?php 
                                    $foto_path = '../uploads/tamu/' . $tamu_detail['foto_tamu'];
                                    if (!empty($tamu_detail['foto_tamu']) && file_exists($foto_path)): 
                                ?>
                                    <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Tamu">
                                <?php else: ?>
                                    <div class="py-5 text-muted">
                                        <i class="bi bi-person-x-fill display-4 text-gray-300"></i>
                                        <p class="mt-2 small">Foto tidak tersedia</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($tamu_detail['nama_tamu']); ?></h5>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($tamu_detail['asal_instansi'] ?: 'Umum/Pribadi'); ?></p>
                            
                            <div class="d-flex justify-content-center gap-2 mb-4">
                                <?php if($tamu_detail['status_keluar'] == 'Masuk'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i> Sedang Berkunjung
                                    </span>
                                <?php else: ?>
                                     <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">
                                        <i class="bi bi-box-arrow-right me-1"></i> Sudah Keluar
                                    </span>
                                <?php endif; ?>
                            </div>

                            <hr>
                            
                            <div class="text-start mt-3">
                                <span class="detail-label mb-2">Tanda Tangan</span>
                                <div class="signature-preview p-3 border rounded bg-light text-center">
                                    <?php 
                                        if (!empty($tamu_detail['tanda_tangan']) && strpos($tamu_detail['tanda_tangan'], 'data:image') === 0):
                                    ?>
                                        <img src="<?php echo $tamu_detail['tanda_tangan']; ?>" alt="TTD" class="img-fluid">
                                    <?php else: ?>
                                        <small class="text-muted fst-italic">Belum ada tanda tangan.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Details -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header pt-4 px-4 bg-white border-bottom-0 d-flex justify-content-between">
                            <h5 class="mb-0 fw-bold text-secondary">Informasi Lengkap</h5>
                            <a href="edit_tamu.php?id=<?php echo $tamu_detail['id_tamu']; ?>" class="btn btn-sm btn-soft-warning text-warning" style="background-color: rgba(246, 194, 62, 0.1);">
                                <i class="bi bi-pencil-square me-1"></i> Edit Data
                            </a>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Waktu Kedatangan</span>
                                        <div class="detail-value">
                                            <i class="bi bi-calendar3 me-2 text-primary"></i>
                                            <?php echo date('d F Y', strtotime($tamu_detail['tanggal_kunjungan'])); ?>
                                            <span class="mx-1">•</span>
                                            <?php echo date('H:i', strtotime($tamu_detail['waktu_masuk'])); ?> WIB
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Waktu Keluar</span>
                                        <div class="detail-value">
                                            <?php if($tamu_detail['waktu_keluar']): ?>
                                                <i class="bi bi-clock-history me-2 text-secondary"></i>
                                                <?php echo date('H:i', strtotime($tamu_detail['waktu_keluar'])); ?> WIB
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12"><hr class="my-0 border-light"></div>

                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Jabatan</span>
                                        <div class="detail-value"><?php echo htmlspecialchars($tamu_detail['jabatan'] ?: '-'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Kontak (HP/WA)</span>
                                        <div class="detail-value"><?php echo htmlspecialchars($tamu_detail['no_telepon'] ?: '-'); ?></div>
                                    </div>
                                </div>
                                 <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Email</span>
                                        <div class="detail-value"><?php echo htmlspecialchars($tamu_detail['email_tamu'] ?: '-'); ?></div>
                                    </div>
                                </div>
                                 <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Bertemu Dengan</span>
                                        <div class="detail-value text-primary fw-bold">
                                            <i class="bi bi-person-fill me-1"></i>
                                            <?php echo htmlspecialchars($tamu_detail['bertemu_dengan']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12"><hr class="my-0 border-light"></div>

                                <div class="col-12">
                                    <div class="detail-item">
                                        <span class="detail-label">Keperluan</span>
                                        <div class="p-3 bg-light rounded-3 text-secondary" style="font-size: 0.95rem; line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($tamu_detail['keperluan'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if($tamu_detail['catatan_tambahan']): ?>
                                <div class="col-12">
                                    <div class="detail-item">
                                        <span class="detail-label">Catatan Tambahan (Status Kunjungan)</span>
                                        <div class="p-3 bg-light rounded-3 text-secondary fst-italic border-start border-4 border-warning">
                                            <?php echo nl2br(htmlspecialchars($tamu_detail['catatan_tambahan'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-danger">Data tidak dapat dimuat.</div>
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