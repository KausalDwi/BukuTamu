<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Pastikan path koneksi benar
require_once __DIR__ . '/../koneksi/csrf.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Manajemen Admin";
// Ambil pesan dari session jika ada, lalu hapus agar tidak muncul lagi
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Siapkan token CSRF untuk aksi hapus
$csrf_delete_admin_token = csrf_generate_token('delete_admin');

// --- AWAL BLOK HANDLE AKSI HAPUS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    if (!csrf_validate_token($_POST['csrf_token'] ?? '', 'delete_admin')) {
        $_SESSION['message'] = "Permintaan tidak valid. Silakan coba lagi.";
        $_SESSION['message_type'] = "danger";
        header("Location: manajemen_admin.php");
        exit;
    }

    $id_admin_to_delete = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if ($id_admin_to_delete) {
        // Proteksi 1: Admin tidak bisa menghapus dirinya sendiri
        if ($id_admin_to_delete == $_SESSION['admin_id']) {
            $_SESSION['message'] = "Anda tidak dapat menghapus akun Anda sendiri.";
            $_SESSION['message_type'] = "danger";
        } else {
            // Proteksi 2: Superadmin terakhir tidak boleh dihapus (jika yang dihapus adalah superadmin)
            $can_delete = true; // Anggap bisa dihapus awalnya

            // Cek dulu role admin yang akan dihapus
            $role_admin_to_delete = '';
            $sql_get_role = "SELECT role FROM tb_admin WHERE id_admin = ?";
            if ($stmt_get_role = $koneksi->prepare($sql_get_role)) {
                $stmt_get_role->bind_param("i", $id_admin_to_delete);
                $stmt_get_role->execute();
                $result_role = $stmt_get_role->get_result();
                if ($result_role->num_rows === 1) {
                    $admin_details = $result_role->fetch_assoc();
                    $role_admin_to_delete = $admin_details['role'];
                }
                $stmt_get_role->close();
            }

            if ($role_admin_to_delete === 'superadmin') {
                // Hitung jumlah superadmin yang ada
                $sql_count_super = "SELECT COUNT(*) as total_super FROM tb_admin WHERE role = 'superadmin'";
                $result_count_super = $koneksi->query($sql_count_super);
                if ($result_count_super) {
                    $count_data = $result_count_super->fetch_assoc();
                    if ($count_data['total_super'] <= 1) {
                        // Jika hanya ada 1 atau kurang superadmin (dan yang mau dihapus adalah superadmin itu)
                        $_SESSION['message'] = "Tidak dapat menghapus. Harus ada minimal satu Super Admin yang tersisa.";
                        $_SESSION['message_type'] = "danger";
                        $can_delete = false;
                    }
                } else {
                     // Gagal query hitung superadmin, anggap tidak bisa dihapus untuk keamanan
                    $_SESSION['message'] = "Gagal memverifikasi jumlah Super Admin. Penghapusan dibatalkan.";
                    $_SESSION['message_type'] = "danger";
                    $can_delete = false;
                    error_log("Gagal query hitung superadmin: " . $koneksi->error);
                }
            }

            // Jika semua proteksi lolos, baru lakukan penghapusan
            if ($can_delete) {
                $sql_delete = "DELETE FROM tb_admin WHERE id_admin = ?";
                if ($stmt_delete = $koneksi->prepare($sql_delete)) {
                    $stmt_delete->bind_param("i", $id_admin_to_delete);
                    if ($stmt_delete->execute()) {
                        $_SESSION['message'] = "Pengguna admin berhasil dihapus.";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Gagal menghapus pengguna admin: " . $stmt_delete->error;
                        $_SESSION['message_type'] = "danger";
                        error_log("Admin Gagal Hapus Admin User: " . $stmt_delete->error);
                    }
                    $stmt_delete->close();
                } else {
                    $_SESSION['message'] = "Gagal menyiapkan statement hapus admin: " . $koneksi->error;
                    $_SESSION['message_type'] = "danger";
                    error_log("Admin Gagal Prepare Hapus Admin User: " . $koneksi->error);
                }
            }
        }
    } else {
        $_SESSION['message'] = "ID Admin tidak valid untuk dihapus.";
        $_SESSION['message_type'] = "danger";
    }
    // Redirect kembali ke halaman manajemen_admin.php untuk refresh dan menghilangkan parameter GET
    header("Location: manajemen_admin.php");
    exit;
}
// --- AKHIR BLOK HANDLE AKSI HAPUS ---


// Fetch semua data admin untuk ditampilkan di tabel
$admin_list = [];
$sql_select_admins = "SELECT id_admin, nama_lengkap, username, email, role, last_login, created_at FROM tb_admin ORDER BY id_admin ASC";
$result_admins = $koneksi->query($sql_select_admins);
if ($result_admins && $result_admins->num_rows > 0) {
    while ($row = $result_admins->fetch_assoc()) {
        $admin_list[] = $row;
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

        /* Soft Buttons */
        .btn-soft-primary { background-color: rgba(52, 152, 219, 0.1); color: #3498db; border: none; transition: all 0.2s; }
        .btn-soft-primary:hover { background-color: #3498db; color: #fff; }
        .btn-soft-warning { background-color: rgba(241, 196, 15, 0.1); color: #f1c40f; border: none; transition: all 0.2s; }
        .btn-soft-warning:hover { background-color: #f1c40f; color: #fff; }
        .btn-soft-danger { background-color: rgba(231, 76, 60, 0.1); color: #e74c3c; border: none; transition: all 0.2s; }
        .btn-soft-danger:hover { background-color: #e74c3c; color: #fff; }
        
        .action-buttons .btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; margin: 0 2px; }

        /* Role Badges */
        .badge.bg-primary-soft { background-color: rgba(78, 115, 223, 0.1); color: #4e73df; }
        .badge.bg-secondary-soft { background-color: rgba(133, 135, 150, 0.1); color: #858796; }
        .badge { font-weight: 500; padding: 0.5em 0.75em; border-radius: 6px; }

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
                    <h1 class="h3 fw-bold text-dark mb-1"><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="text-muted mb-0">Kelola akses dan hak pengguna sistem.</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="tambah_admin.php" class="btn btn-primary text-white shadow-sm border-0 d-flex align-items-center px-3 py-2" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-radius: 10px;">
                        <i class="bi bi-person-plus-fill me-2"></i> Tambah Admin
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>-fill fs-4 me-3"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom-0 pt-4 px-4">
                     <h5 class="mb-0 fw-bold text-secondary">Daftar Pengguna</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (!empty($admin_list)): ?>
                    <div class="table-responsive">
                        <table id="tabelDataAdmin" class="table table-hover w-100" style="border-collapse: separate; border-spacing: 0;">
                            <thead>
                                <tr>
                                    <th class="border-top-0 rounded-start-2">No</th>
                                    <th class="border-top-0 text-start">Pengguna</th>
                                    <th class="border-top-0 text-start">Email</th>
                                    <th class="border-top-0">Role</th>
                                    <th class="border-top-0">Status Login</th>
                                    <th class="border-top-0">Bergabung</th>
                                    <th class="border-top-0 rounded-end-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($admin_list as $admin): ?>
                                <tr>
                                    <td class="text-center fw-medium"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <span class="fw-bold"><?php echo strtoupper(substr($admin['username'], 0, 1)); ?></span>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($admin['nama_lengkap']); ?></div>
                                                <div class="small text-muted">@<?php echo htmlspecialchars($admin['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($admin['email'] ?: '-'); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $admin['role'] == 'superadmin' ? 'bg-primary-soft' : 'bg-secondary-soft'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($admin['role'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                         <?php if($admin['last_login']): ?>
                                            <span class="small text-muted"><i class="bi bi-clock me-1"></i><?php echo date('d M Y, H:i', strtotime($admin['last_login'])); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border">Belum Login</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-muted small"><?php echo htmlspecialchars(date('d M Y', strtotime($admin['created_at']))); ?></td>
                                    <td class="text-center action-buttons">
                                        <a href="edit_admin.php?id=<?php echo $admin['id_admin']; ?>" class="btn btn-soft-warning btn-sm" title="Edit Data">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <?php if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] != $admin['id_admin']): ?>
                                        <form method="POST" class="d-inline"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus admin ini? Tindakan ini tidak bisa diurungkan.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $admin['id_admin']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_delete_admin_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-soft-danger btn-sm" title="Hapus User">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <button class="btn btn-light text-muted btn-sm border" disabled title="Tidak dapat menghapus diri sendiri"><i class="bi bi-trash-fill"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                         <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" alt="Empty" style="max-width: 150px; opacity: 0.6;">
                        <p class="text-muted mt-3">Belum ada pengguna admin terdaftar.</p>
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
            $('#tabelDataAdmin').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json", "searchPlaceholder": "Cari admin..." },
                "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rtip',
                "columnDefs": [ { "orderable": false, "targets": 6 } ],
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
