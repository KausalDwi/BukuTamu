<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php';
require_once __DIR__ . '/../koneksi/csrf.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Manajemen Admin";
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$csrf_delete_admin_token = csrf_generate_token('delete_admin');

// Handle Hapus Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    if (csrf_validate_token($_POST['csrf_token'] ?? '', 'delete_admin')) {
        $id = (int)$_POST['id'];
        if ($id != $_SESSION['admin_id']) { // Tidak bisa hapus diri sendiri
            $koneksi->query("DELETE FROM tb_admin WHERE id = $id");
            $_SESSION['message'] = "Admin berhasil dihapus.";
            $_SESSION['message_type'] = "success";
        }
    }
    header("Location: manajemen_admin.php");
    exit;
}

// Fetch data admin (sekarang menampilkan semua kolom baru)
$admin_list = [];
$result = $koneksi->query("SELECT * FROM tb_admin ORDER BY id ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admin_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/admin-style.css" rel="stylesheet">
</head>
<body>
    <?php include_once __DIR__ . '/_partials/navbar.php'; include_once __DIR__ . '/_partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Manajemen Admin</h1>
                <a href="tambah_admin.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Admin</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Terakhir Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($admin_list as $admin): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($admin['nama_lengkap'] ?? '-'); ?></td>
                                <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['email'] ?? '-'); ?></td>
                                <td><span class="badge bg-<?php echo ($admin['role'] == 'superadmin') ? 'primary' : 'secondary'; ?>"><?php echo ucfirst($admin['role'] ?? 'admin'); ?></span></td>
                                <td><?php echo $admin['last_login'] ?? 'Belum pernah'; ?></td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_delete_admin_token; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>