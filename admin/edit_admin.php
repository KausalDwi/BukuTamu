<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Edit Pengguna Admin";
$errors = [];
$admin_to_edit = null;
$id_admin_to_edit = null;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_admin_to_edit = $_GET['id'];
    $sql_get_admin = "SELECT id_admin, nama_lengkap, username, email, role FROM tb_admin WHERE id_admin = ?";
    if ($stmt_get = $koneksi->prepare($sql_get_admin)) {
        $stmt_get->bind_param("i", $id_admin_to_edit);
        $stmt_get->execute();
        $result_admin = $stmt_get->get_result();
        if ($result_admin->num_rows === 1) {
            $admin_to_edit = $result_admin->fetch_assoc();
        } else {
            $_SESSION['message'] = "Admin tidak ditemukan.";
            $_SESSION['message_type'] = "danger";
            header("Location: manajemen_admin.php");
            exit;
        }
        $stmt_get->close();
    } else {
        // Error prepare
        $_SESSION['message'] = "Gagal menyiapkan data admin untuk diedit.";
        $_SESSION['message_type'] = "danger";
        header("Location: manajemen_admin.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID Admin tidak valid atau tidak disediakan.";
    $_SESSION['message_type'] = "danger";
    header("Location: manajemen_admin.php");
    exit;
}


// Inisialisasi variabel form dengan data admin yang ada
$nama_lengkap = $admin_to_edit['nama_lengkap'];
$username = $admin_to_edit['username'];
$email = $admin_to_edit['email'];
$role = $admin_to_edit['role'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $new_username = trim($_POST['username']); // Username baru, bisa sama atau beda
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $konfirmasi_new_password = $_POST['konfirmasi_new_password'];
    $new_role = $_POST['role'];

    // Validasi
    if (empty($nama_lengkap)) $errors[] = "Nama lengkap wajib diisi.";
    if (empty($new_username)) $errors[] = "Username wajib diisi.";
    if (!empty($new_password) && $new_password !== $konfirmasi_new_password) $errors[] = "Password baru dan konfirmasi password tidak cocok.";
    if (!empty($new_password) && strlen($new_password) < 6) $errors[] = "Password baru minimal 6 karakter.";
    if (!in_array($new_role, ['admin', 'superadmin'])) $errors[] = "Role tidak valid.";

    // Cek apakah username baru (jika diubah) sudah ada & bukan username lama dari user ini
    if (empty($errors) && $new_username !== $admin_to_edit['username']) {
        $sql_check_username = "SELECT id_admin FROM tb_admin WHERE username = ?";
        if ($stmt_check = $koneksi->prepare($sql_check_username)) {
            $stmt_check->bind_param("s", $new_username);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Username '$new_username' sudah digunakan. Silakan pilih username lain.";
            }
            $stmt_check->close();
        }
    }

    // Tidak boleh mengubah role diri sendiri dari superadmin ke admin jika dia satu-satunya superadmin
    if ($id_admin_to_edit == $_SESSION['admin_id'] && $admin_to_edit['role'] == 'superadmin' && $new_role == 'admin') {
        $sql_count_superadmin = "SELECT COUNT(*) as total_superadmin FROM tb_admin WHERE role = 'superadmin'";
        $res_count = $koneksi->query($sql_count_superadmin);
        $row_count = $res_count->fetch_assoc();
        if ($row_count['total_superadmin'] <= 1) {
            $errors[] = "Tidak dapat mengubah role. Harus ada minimal satu Super Admin.";
        }
    }


    if (empty($errors)) {
        $params = [];
        $types = "";
        $sql_update = "UPDATE tb_admin SET nama_lengkap = ?, username = ?, email = ?, role = ?";
        $params[] = $nama_lengkap; $types .= "s";
        $params[] = $new_username; $types .= "s";
        $params[] = $email;        $types .= "s";
        $params[] = $new_role;     $types .= "s";

        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update .= ", password_hash = ?";
            $params[] = $password_hash; $types .= "s";
        }

        $sql_update .= " WHERE id_admin = ?";
        $params[] = $id_admin_to_edit; $types .= "i";

        if ($stmt_update = $koneksi->prepare($sql_update)) {
            $stmt_update->bind_param($types, ...$params); // Spread operator untuk bind parameter
            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Data admin berhasil diperbarui.";
                $_SESSION['message_type'] = "success";
                // Jika admin mengedit dirinya sendiri, update data session
                if ($id_admin_to_edit == $_SESSION['admin_id']) {
                    $_SESSION['admin_username'] = $new_username;
                    $_SESSION['admin_nama_lengkap'] = $nama_lengkap;
                    $_SESSION['admin_role'] = $new_role;
                }
                header("Location: manajemen_admin.php");
                exit;
            } else {
                $errors[] = "Gagal memperbarui admin: " . $stmt_update->error;
                error_log("Gagal update admin: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            $errors[] = "Gagal menyiapkan statement update admin: " . $koneksi->error;
            error_log("Gagal prepare update admin: " . $koneksi->error);
        }
    }
    // Jika ada error, variabel $username, $email, $role akan berisi data POST terbaru untuk diisi kembali ke form
    $username = $new_username; // Agar form menampilkan username yang baru diinput jika ada error
    $role = $new_role;
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
    <style>
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
                            <li class="breadcrumb-item"><a href="manajemen_admin.php" class="text-decoration-none text-muted">Manajemen Admin</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Pengguna</li>
                        </ol>
                    </nav>
                    <h1 class="h3 fw-bold text-dark mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="manajemen_admin.php" class="btn btn-light shadow-sm border text-muted">
                        <i class="bi bi-arrow-left me-2"></i> Kembali
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
             <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-octagon-fill fs-4 me-3 mt-1"></i>
                    <div>
                         <h6 class="alert-heading fw-bold mb-1">Gagal Menyimpan Data</h6>
                         <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($admin_to_edit): ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header pt-4 px-4">
                            <h5 class="mb-0">Formulir Edit Data</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $id_admin_to_edit; ?>">
                                
                                <div class="row g-3 mb-4">
                                     <div class="col-12">
                                        <h6 class="text-muted text-uppercase small fw-bold mb-3 border-bottom pb-2">Informasi Akun</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama_lengkap); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="email" class="form-label">Alamat Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                         <label for="role" class="form-label">Role Akses <span class="text-danger">*</span></label>
                                         <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Admin Biasa</option>
                                                <?php if ($_SESSION['admin_role'] == 'superadmin' || $admin_to_edit['role'] == 'superadmin'): ?>
                                                <option value="superadmin" <?php echo ($role === 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <h6 class="text-muted text-uppercase small fw-bold mb-3 border-bottom pb-2">Keamanan (Ubah Password)</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Biarkan kosong jika tetap">
                                        </div>
                                        <div class="form-text small">Minimal 6 karakter.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="konfirmasi_new_password" class="form-label">Konfirmasi Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-check-lg"></i></span>
                                            <input type="password" class="form-control" id="konfirmasi_new_password" name="konfirmasi_new_password" placeholder="Ulangi password baru">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 pt-3">
                                    <button type="button" class="btn btn-light border px-4" onclick="history.back()">Batal</button>
                                    <button type="submit" class="btn btn-primary px-4 fw-medium text-white shadow-sm" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border:none;">
                                        <i class="bi bi-save-fill me-2"></i> Simpan Perubahan
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
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