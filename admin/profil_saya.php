<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Profil Saya";
$admin_id_saya = $_SESSION['admin_id']; // ID admin yang sedang login

$errors = [];
$success_message = '';

// Ambil data admin saat ini
$current_admin_data = null;
$sql_get_mydata = "SELECT nama_lengkap, username, email FROM tb_admin WHERE id_admin = ?";
if ($stmt_mydata = $koneksi->prepare($sql_get_mydata)) {
    $stmt_mydata->bind_param("i", $admin_id_saya);
    $stmt_mydata->execute();
    $result_mydata = $stmt_mydata->get_result();
    if ($result_mydata->num_rows === 1) {
        $current_admin_data = $result_mydata->fetch_assoc();
    } else {
        // Seharusnya tidak terjadi jika sesi valid
        session_destroy();
        header("Location: login.php?message=Sesi tidak valid, silakan login kembali.");
        exit;
    }
    $stmt_mydata->close();
} else {
    die("Gagal menyiapkan data profil."); // Error fatal
}

$nama_lengkap = $current_admin_data['nama_lengkap'];
$username = $current_admin_data['username']; // Username tidak diedit di sini
$email = $current_admin_data['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Cek apakah ini submit untuk detail atau password
    if (isset($_POST['submit_detail'])) {
        $nama_lengkap_new = trim($_POST['nama_lengkap']);
        $email_new = trim($_POST['email']);

        if (empty($nama_lengkap_new)) {
            $errors[] = "Nama lengkap tidak boleh kosong.";
        }
        if (!empty($email_new) && !filter_var($email_new, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid.";
        }

        // Cek apakah email baru (jika diubah & tidak kosong) sudah digunakan oleh admin lain
        if (empty($errors) && !empty($email_new) && $email_new !== $current_admin_data['email']) {
            $sql_check_email = "SELECT id_admin FROM tb_admin WHERE email = ? AND id_admin != ?";
            if ($stmt_check_email = $koneksi->prepare($sql_check_email)) {
                $stmt_check_email->bind_param("si", $email_new, $admin_id_saya);
                $stmt_check_email->execute();
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows > 0) {
                    $errors[] = "Email '$email_new' sudah digunakan oleh admin lain.";
                }
                $stmt_check_email->close();
            }
        }


        if (empty($errors)) {
            $sql_update_detail = "UPDATE tb_admin SET nama_lengkap = ?, email = ? WHERE id_admin = ?";
            if ($stmt_update = $koneksi->prepare($sql_update_detail)) {
                $stmt_update->bind_param("ssi", $nama_lengkap_new, $email_new, $admin_id_saya);
                if ($stmt_update->execute()) {
                    $_SESSION['admin_nama_lengkap'] = $nama_lengkap_new; // Update session
                    $success_message = "Detail profil berhasil diperbarui.";
                    // Re-fetch data untuk menampilkan yang terbaru di form
                    $nama_lengkap = $nama_lengkap_new;
                    $email = $email_new;
                } else {
                    $errors[] = "Gagal memperbarui detail profil: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $errors[] = "Gagal menyiapkan statement update detail.";
            }
        }

    } elseif (isset($_POST['submit_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password_baru = $_POST['konfirmasi_password_baru'];

        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password_baru)) {
            $errors[] = "Semua field password wajib diisi untuk mengubah password.";
        } else {
            // Ambil hash password saat ini dari database
            $sql_get_pass = "SELECT password_hash FROM tb_admin WHERE id_admin = ?";
            if ($stmt_get_pass = $koneksi->prepare($sql_get_pass)) {
                $stmt_get_pass->bind_param("i", $admin_id_saya);
                $stmt_get_pass->execute();
                $result_pass = $stmt_get_pass->get_result();
                $admin_pass_data = $result_pass->fetch_assoc();
                $stmt_get_pass->close();

                if ($admin_pass_data && password_verify($password_lama, $admin_pass_data['password_hash'])) {
                    // Password lama cocok
                    if ($password_baru !== $konfirmasi_password_baru) {
                        $errors[] = "Password baru dan konfirmasi password baru tidak cocok.";
                    } elseif (strlen($password_baru) < 6) {
                        $errors[] = "Password baru minimal 6 karakter.";
                    } else {
                        // Semua valid, hash password baru dan update
                        $password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                        $sql_update_pass = "UPDATE tb_admin SET password_hash = ? WHERE id_admin = ?";
                        if ($stmt_update_pass = $koneksi->prepare($sql_update_pass)) {
                            $stmt_update_pass->bind_param("si", $password_hash_baru, $admin_id_saya);
                            if ($stmt_update_pass->execute()) {
                                $success_message = "Password berhasil diubah. Silakan login kembali jika diperlukan.";
                                // Opsional: Hancurkan sesi dan paksa login ulang untuk keamanan
                                // unset($_SESSION['admin_logged_in']);
                                // session_destroy();
                                // header("Location: login.php?message=Password berhasil diubah, silakan login ulang.");
                                // exit;
                            } else {
                                $errors[] = "Gagal mengubah password: " . $stmt_update_pass->error;
                            }
                            $stmt_update_pass->close();
                        } else {
                            $errors[] = "Gagal menyiapkan statement ubah password.";
                        }
                    }
                } else {
                    $errors[] = "Password lama yang Anda masukkan salah.";
                }
            } else {
                 $errors[] = "Gagal memverifikasi password lama.";
            }
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
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h4 class="alert-heading"><i class="bi bi-exclamation-octagon-fill"></i> Oops! Ada kesalahan:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Edit Detail Profil</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username_display" value="<?php echo htmlspecialchars($username); ?>" readonly disabled>
                                    <div class="form-text">Username tidak dapat diubah.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama_lengkap); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                                <button type="submit" name="submit_detail" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan Detail Profil</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-key-fill me-2"></i>Ubah Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label for="password_lama" class="form-label">Password Lama <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password_baru" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                                    <div class="form-text">Minimal 6 karakter.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="konfirmasi_password_baru" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="konfirmasi_password_baru" name="konfirmasi_password_baru" required>
                                </div>
                                <button type="submit" name="submit_password" class="btn btn-warning"><i class="bi bi-shield-lock-fill"></i> Ubah Password</button>
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