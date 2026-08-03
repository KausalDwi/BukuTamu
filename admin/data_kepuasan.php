<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Pastikan path ini benar
require_once __DIR__ . '/../koneksi/csrf.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Data Survei Kepuasan";
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$csrf_delete_kepuasan_token = csrf_generate_token('delete_kepuasan');

//======================================================================
// DEFINISI FUNGSI DISPLAY RATING
//======================================================================
if (!function_exists('generate_star_display')) {
    function generate_star_display($rating_value, $max_stars = 5) {
        $color_class = 'text-warning'; // Default yellow
        if($rating_value >= 5) $color_class = 'text-warning'; // Gold
        elseif($rating_value <= 2) $color_class = 'text-danger'; // Red for low
        
        $stars_html = '<div class="text-nowrap" style="font-size: 0.85rem;">';
        for ($i = 1; $i <= $max_stars; $i++) {
            $icon = ($i <= $rating_value) ? 'bi-star-fill' : 'bi-star';
            $stars_html .= '<i class="bi '.$icon.' '.$color_class.' me-1"></i>';
        }
        $stars_html .= '</div>';
        return $stars_html;
    }
}

// Handle Aksi Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    if (!csrf_validate_token($_POST['csrf_token'] ?? '', 'delete_kepuasan')) {
        $_SESSION['message'] = "Permintaan tidak valid.";
        $_SESSION['message_type'] = "danger";
        header("Location: data_kepuasan.php");
        exit;
    }

    $id_kepuasan_to_delete = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id_kepuasan_to_delete) {
        $sql_delete = "DELETE FROM tb_kepuasan WHERE id_kepuasan = ?";
        if ($stmt_delete = $koneksi->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $id_kepuasan_to_delete);
            if ($stmt_delete->execute()) {
                $_SESSION['message'] = "Data survei berhasil dihapus.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal menghapus data: " . $stmt_delete->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt_delete->close();
        } else {
            $_SESSION['message'] = "Gagal menyiapkan statement: " . $koneksi->error;
            $_SESSION['message_type'] = "danger";
        }
        header("Location: data_kepuasan.php");
        exit;
    }
}

// Fetch Data
$survei_list = [];
$sql_select_survei = "SELECT id_kepuasan, id_tamu_fk, nama_responden, tanggal_survei, waktu_survei, 
                             nilai_pelayanan, nilai_fasilitas, nilai_keramahan, nilai_kecepatan, saran_masukan 
                      FROM tb_kepuasan 
                      ORDER BY tanggal_survei DESC, waktu_survei DESC";
$result_survei = $koneksi->query($sql_select_survei);
if ($result_survei && $result_survei->num_rows > 0) {
    while ($row = $result_survei->fetch_assoc()) {
        $survei_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Buku Tamu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
    <style>
        /* Table Styling */
        .table-responsive { margin-top: 0; }
        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            padding: 1rem 0.75rem;
            text-align: center;
        }
        .table tbody tr td {
            vertical-align: middle;
            color: #555;
            font-size: 0.95rem;
            padding: 0.75rem;
            border-bottom-color: #f0f2f5;
        }
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
        
        .saran-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-style: italic;
            color: #6c757d;
        }

         /* Soft Buttons */
         .btn-soft-primary { background-color: rgba(52, 152, 219, 0.1); color: #3498db; border: none; transition: all 0.2s; }
        .btn-soft-primary:hover { background-color: #3498db; color: #fff; }
        .btn-soft-danger { background-color: rgba(231, 76, 60, 0.1); color: #e74c3c; border: none; transition: all 0.2s; }
        .btn-soft-danger:hover { background-color: #e74c3c; color: #fff; }
        
        .action-buttons .btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; margin: 0 2px; }

        /* DataTables Custom */
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input {
             border-radius: 6px; padding: 0.375rem 0.75rem; border: 1px solid #dee2e6;
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
                    <h1 class="h3 fw-bold text-dark mb-1">Indeks Kepuasan</h1>
                    <p class="text-muted mb-0">Laporan dan umpan balik pengunjung.</p>
                </div>
                 <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                    <a href="export_kepuasan_pdf.php" class="btn btn-danger text-white shadow-sm border-0 d-flex align-items-center" style="background: linear-gradient(135deg, #e74c3c, #c0392b); border-radius: 10px;">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Ekspor PDF
                    </a>
                    <a href="export_kepuasan.php" class="btn btn-primary text-white shadow-sm border-0 d-flex align-items-center" style="background: linear-gradient(135deg, #1cc88a, #13855c); border-radius: 10px;">
                        <i class="bi bi-file-earmark-excel-fill me-2"></i> Ekspor Report
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'info-circle'; ?>-fill fs-4 me-3"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold text-secondary">Riwayat Survei</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (!empty($survei_list)): ?>
                    <div class="table-responsive">
                        <table id="tabelDataSurvei" class="table table-hover w-100" style="border-collapse: separate; border-spacing: 0;">
                            <thead>
                                <tr>
                                    <th class="border-top-0 rounded-start-2">No</th>
                                    <th class="border-top-0">Waktu</th>
                                    <th class="border-top-0 text-start">Responden</th>
                                    <th class="border-top-0">Pelayanan</th>
                                    <th class="border-top-0">Fasilitas</th>
                                    <th class="border-top-0">Keramahan</th>
                                    <th class="border-top-0">Speed</th>
                                    <th class="border-top-0 text-start">Saran</th>
                                    <th class="border-top-0 rounded-end-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $nomor = 1; foreach ($survei_list as $survei): ?>
                                <tr>
                                    <td class="text-center fw-medium"><?php echo $nomor++; ?></td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="fw-medium"><?php echo htmlspecialchars(date('d M Y', strtotime($survei['tanggal_survei']))); ?></span>
                                            <small class="text-muted"><?php echo htmlspecialchars(date('H:i', strtotime($survei['waktu_survei']))); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($survei['nama_responden'] ?: 'Anonim'); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo generate_star_display($survei['nilai_pelayanan']); ?></td>
                                    <td class="text-center"><?php echo generate_star_display($survei['nilai_fasilitas']); ?></td>
                                    <td class="text-center"><?php echo generate_star_display($survei['nilai_keramahan']); ?></td>
                                    <td class="text-center"><?php echo generate_star_display($survei['nilai_kecepatan']); ?></td>
                                    <td class="saran-preview" title="<?php echo htmlspecialchars($survei['saran_masukan']); ?>">
                                        <?php echo htmlspecialchars($survei['saran_masukan'] ?: '-'); ?>
                                    </td>
                                    <td class="text-center action-buttons">
                                        <button class="btn btn-soft-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $survei['id_kepuasan']; ?>" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus data survei ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $survei['id_kepuasan']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_delete_kepuasan_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-soft-danger btn-sm" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                        <!-- Simple Modal for Detail -->
                                        <div class="modal fade" id="detailModal<?php echo $survei['id_kepuasan']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header border-bottom-0">
                                                        <h5 class="modal-title fw-bold">Detail Ulasan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <p class="mb-2"><strong>Oleh:</strong> <?php echo htmlspecialchars($survei['nama_responden'] ?: 'Anonim'); ?></p>
                                                        <div class="p-3 bg-light rounded-3 mb-3">
                                                            <small class="d-block text-muted mb-1">Saran / Masukan:</small>
                                                            <p class="mb-0 fst-italic">"<?php echo htmlspecialchars($survei['saran_masukan'] ?: 'Tidak ada masukan tambahan.'); ?>"</p>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-6"><small>Pelayanan:</small><br><?php echo generate_star_display($survei['nilai_pelayanan']); ?></div>
                                                            <div class="col-6"><small>Fasilitas:</small><br><?php echo generate_star_display($survei['nilai_fasilitas']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                         <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" alt="Empty" style="max-width: 150px; opacity: 0.6;">
                        <p class="text-muted mt-3">Belum ada data survei kepuasan.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelDataSurvei').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json", "searchPlaceholder": "Cari responden..." },
                "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rtip',
                "pageLength": 10
            });
            
             const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const adminSidebar = document.getElementById('adminSidebar');
            if (sidebarToggleBtn && adminSidebar) {
                sidebarToggleBtn.addEventListener('click', function() {
                    adminSidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
