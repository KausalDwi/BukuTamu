<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Tambah Admin Baru";
$errors = [];
$nama_lengkap = '';
$username = '';
$email = '';
$role = 'admin'; // Default role

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $role = $_POST['role'];

    // Validasi
    if (empty($nama_lengkap)) $errors[] = "Nama lengkap wajib diisi.";
    if (empty($username)) $errors[] = "Username wajib diisi.";
    if (empty($password)) $errors[] = "Password wajib diisi.";
    if ($password !== $konfirmasi_password) $errors[] = "Password dan konfirmasi password tidak cocok.";
    if (strlen($password) < 6 && !empty($password)) $errors[] = "Password minimal 6 karakter.";
    if (!in_array($role, ['admin', 'superadmin'])) $errors[] = "Role tidak valid.";

    // Cek apakah username sudah ada
    if (empty($errors) && !empty($username)) {
        $sql_check_username = "SELECT id_admin FROM tb_admin WHERE username = ?";
        if ($stmt_check = $koneksi->prepare($sql_check_username)) {
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Username sudha digunakan. Silakan pilih username lain.";
            }
            $stmt_check->close();
        }
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql_insert = "INSERT INTO tb_admin (nama_lengkap, username, password_hash, email, role) VALUES (?, ?, ?, ?, ?)";
        if ($stmt_insert = $koneksi->prepare($sql_insert)) {
            $stmt_insert->bind_param("sssss", $nama_lengkap, $username, $password_hash, $email, $role);
            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Admin baru berhasil ditambahkan.";
                $_SESSION['message_type'] = "success";
                header("Location: manajemen_admin.php");
                exit;
            } else {
                $errors[] = "Gagal menambahkan admin: " . $stmt_insert->error;
                error_log("Gagal insert admin: " . $stmt_insert->error);
            }
            $stmt_insert->close();
        } else {
            $errors[] = "Gagal menyiapkan statement insert: " . $koneksi->error;
            error_log("Gagal prepare insert admin: " . $koneksi->error);
        }
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
                            <li class="breadcrumb-item active" aria-current="page">Tambah Admin</li>
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

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header pt-4 px-4">
                            <h5 class="mb-0">Formulir Registrasi Admin</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                
                                <div class="row g-3 mb-4">
                                     <div class="col-12">
                                        <h6 class="text-muted text-uppercase small fw-bold mb-3 border-bottom pb-2">Identitas & Akses</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama_lengkap); ?>" placeholder="Contoh: John Doe" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Untuk login" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="email" class="form-label">Alamat Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="email@example.com (Opsional)">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                         <label for="role" class="form-label">Role Akses <span class="text-danger">*</span></label>
                                         <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Admin Biasa</option>
                                                <option value="superadmin" <?php echo ($role === 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <h6 class="text-muted text-uppercase small fw-bold mb-3 border-bottom pb-2">Keamanan</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <div class="form-text small">Minimal 6 karakter.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-check-lg"></i></span>
                                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2 pt-3">
                                    <button type="button" class="btn btn-light border px-4" onclick="history.back()">Batal</button>
                                    <button type="submit" class="btn btn-primary px-4 fw-medium text-white shadow-sm" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border:none;">
                                        <i class="bi bi-person-plus-fill me-2"></i> Tambah Admin
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
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