<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; 
require_once __DIR__ . '/../koneksi/csrf.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta'); // Pastikan zona waktu sesuai

$page_title = "Data Kunjungan Tamu";
$message = $_SESSION['message'] ?? ''; 
$message_type = $_SESSION['message_type'] ?? ''; 
unset($_SESSION['message'], $_SESSION['message_type']); 

$csrf_delete_tamu_token = csrf_generate_token('delete_tamu');
$csrf_checkout_tamu_token = csrf_generate_token('checkout_tamu'); // Token baru untuk fitur keluar

// Handle Aksi Checkout (Tamu Keluar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout' && isset($_POST['id'])) {
    if (!csrf_validate_token($_POST['csrf_token'] ?? '', 'checkout_tamu')) {
        $_SESSION['message'] = "Permintaan tidak valid. Silakan coba lagi.";
        $_SESSION['message_type'] = "danger";
        header("Location: data_tamu.php");
        exit;
    }

    $id_tamu_to_checkout = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $waktu_keluar_sekarang = date("H:i:s");

    if ($id_tamu_to_checkout) {
        $sql_checkout = "UPDATE tb_tamu SET waktu_keluar = ? WHERE id = ?";
        if ($stmt_checkout = $koneksi->prepare($sql_checkout)) {
            $stmt_checkout->bind_param("si", $waktu_keluar_sekarang, $id_tamu_to_checkout);
            if ($stmt_checkout->execute()) {
                $_SESSION['message'] = "Jam keluar tamu berhasil dicatat.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal mencatat jam keluar: " . $stmt_checkout->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt_checkout->close();
        } else {
            $_SESSION['message'] = "Gagal menyiapkan statement checkout: " . $koneksi->error;
            $_SESSION['message_type'] = "danger";
        }
        header("Location: data_tamu.php");
        exit;
    }
}

// Handle Aksi Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    if (!csrf_validate_token($_POST['csrf_token'] ?? '', 'delete_tamu')) {
        $_SESSION['message'] = "Permintaan tidak valid. Silakan coba lagi.";
        $_SESSION['message_type'] = "danger";
        header("Location: data_tamu.php");
        exit;
    }

    $id_tamu_to_delete = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id_tamu_to_delete) {
        $sql_delete = "DELETE FROM tb_tamu WHERE id = ?";
        if ($stmt_delete = $koneksi->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $id_tamu_to_delete);
            if ($stmt_delete->execute()) {
                $_SESSION['message'] = "Data tamu berhasil dihapus.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal menghapus data tamu: " . $stmt_delete->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt_delete->close();
        } else {
            $_SESSION['message'] = "Gagal menyiapkan statement hapus: " . $koneksi->error;
            $_SESSION['message_type'] = "danger";
        }
        header("Location: data_tamu.php");
        exit;
    } else {
        $_SESSION['message'] = "ID tamu tidak valid untuk dihapus.";
        $_SESSION['message_type'] = "danger";
        header("Location: data_tamu.php");
        exit;
    }
}

// Fetch semua data tamu
$tamu_list = [];
$sql_select_tamu = "SELECT * FROM tb_tamu ORDER BY tanggal_kunjungan DESC, waktu_masuk DESC";

$result_tamu = $koneksi->query($sql_select_tamu);
if ($result_tamu) {
    if ($result_tamu->num_rows > 0) {
        while ($row = $result_tamu->fetch_assoc()) {
            $tamu_list[] = $row;
        }
    }
} else {
    $message = "Gagal memuat data dari database: " . $koneksi->error;
    $message_type = "danger";
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
        }
        .table tbody tr td {
            vertical-align: middle;
            color: #555;
            font-size: 0.95rem;
            padding: 1rem 0.75rem;
            border-bottom-color: #f0f2f5;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 6px;
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
        }
        .page-item.active .page-link {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-soft-success { background-color: rgba(46, 204, 113, 0.1); color: #2ecc71; border: none; transition: all 0.2s; }
        .btn-soft-success:hover { background-color: #2ecc71; color: #fff; }
        .btn-soft-primary { background-color: rgba(52, 152, 219, 0.1); color: #3498db; border: none; transition: all 0.2s; }
        .btn-soft-primary:hover { background-color: #3498db; color: #fff; }
        .btn-soft-warning { background-color: rgba(241, 196, 15, 0.1); color: #f1c40f; border: none; transition: all 0.2s; }
        .btn-soft-warning:hover { background-color: #f1c40f; color: #fff; }
        .btn-soft-danger { background-color: rgba(231, 76, 60, 0.1); color: #e74c3c; border: none; transition: all 0.2s; }
        .btn-soft-danger:hover { background-color: #e74c3c; color: #fff; }
        .action-buttons .btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; margin: 0 2px; }
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
                    <h1 class="h3 fw-bold text-dark mb-1"><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="text-muted mb-0">Kelola informasi dan riwayat kunjungan tamu.</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                    <a href="export_tamu_pdf.php" class="btn btn-danger text-white shadow-sm border-0 d-flex align-items-center" style="background: linear-gradient(135deg, #e74c3c, #c0392b); border-radius: 10px;">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Ekspor PDF
                    </a>
                    <a href="export_tamu.php" class="btn btn-primary text-white shadow-sm border-0 d-flex align-items-center" style="background: linear-gradient(135deg, #4e73df, #224abe); border-radius: 10px;">
                        <i class="bi bi-file-earmark-excel-fill me-2"></i> Ekspor ke Excel
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'info'; ?> alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'info-circle'; ?>-fill fs-4 me-3"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold text-secondary">Semua Tamu</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (!empty($tamu_list)): ?>
                    <div class="table-responsive">
                        <table id="tabelDataTamu" class="table table-hover align-middle w-100" style="border-collapse: separate; border-spacing: 0;">
                            <thead>
                                <tr>
                                    <th class="border-top-0 rounded-start-2">No.</th>
                                    <th class="border-top-0">Tanggal</th>
                                    <th class="border-top-0">Masuk</th>
                                    <th class="border-top-0">Keluar</th>
                                    <th class="border-top-0">Nama Tamu</th>
                                    <th class="border-top-0">Instansi</th>
                                    <th class="border-top-0">Bertemu</th>
                                    <th class="border-top-0 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $nomor = 1; foreach ($tamu_list as $tamu): ?>
                                <tr>
                                    <td class="ps-3"><?php echo $nomor++; ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-dark"><?php echo htmlspecialchars(date('d M Y', strtotime($tamu['tanggal_kunjungan']))); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary text-white border"><?php echo htmlspecialchars(substr($tamu['waktu_masuk'], 0, 5)); ?></span></td>
                                    <td>
                                        <?php if (!empty($tamu['waktu_keluar'])): ?>
                                            <span class="badge bg-secondary text-white border"><?php echo htmlspecialchars(substr($tamu['waktu_keluar'], 0, 5)); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Di Dalam</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem; background: linear-gradient(135deg, #667eea, #764ba2) !important;">
                                                <?php echo strtoupper(substr($tamu['nama_tamu'], 0, 1)); ?>
                                            </div>
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($tamu['nama_tamu']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($tamu['asal_instansi']); ?></td>
                                    <td><?php echo htmlspecialchars($tamu['bertemu_dengan']); ?></td>
                                    <td class="text-center action-buttons">
                                        
                                        <!-- Tombol Checkout (Hanya tampil jika belum keluar) -->
                                        <?php if (empty($tamu['waktu_keluar'])): ?>
                                            <button type="button" class="btn btn-soft-success" data-bs-toggle="tooltip" title="Tamu Keluar" onclick="confirmCheckout(<?php echo (int) $tamu['id']; ?>)">
                                                <i class="bi bi-box-arrow-right"></i>
                                            </button>
                                            <form id="checkoutForm-<?php echo (int) $tamu['id']; ?>" method="POST" class="d-none">
                                                <input type="hidden" name="action" value="checkout">
                                                <input type="hidden" name="id" value="<?php echo (int) $tamu['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_checkout_tamu_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            </form>
                                        <?php endif; ?>

                                        <a href="detail_tamu.php?id=<?php echo $tamu['id']; ?>" class="btn btn-soft-primary" data-bs-toggle="tooltip" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit_tamu.php?id=<?php echo $tamu['id']; ?>" class="btn btn-soft-warning" data-bs-toggle="tooltip" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-soft-danger" data-bs-toggle="tooltip" title="Hapus" onclick="confirmDelete(<?php echo (int) $tamu['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        
                                        <form id="deleteForm-<?php echo (int) $tamu['id']; ?>" method="POST" class="d-none">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $tamu['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_delete_tamu_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" alt="Empty Data" style="max-width: 200px; opacity: 0.8;">
                        <h6 class="mt-3 text-muted">Belum ada data kunjungan tamu.</h6>
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
            var table = $('#tabelDataTamu').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json",
                    "search": "",
                    "searchPlaceholder": "Cari tamu..." 
                },
                "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rtip',
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
                "pageLength": 10,
                "createdRow": function( row, data, dataIndex ) {
                    $(row).addClass('align-middle');
                }
            });
            
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });

        function confirmCheckout(id) {
            if (confirm('Tamu ini akan dicatat keluar. Lanjutkan?')) {
                document.getElementById('checkoutForm-' + id).submit();
            }
        }

        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data tamu ini? Data yang dihapus tidak dapat dikembalikan.')) {
                document.getElementById('deleteForm-' + id).submit();
            }
        }

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