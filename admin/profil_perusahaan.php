<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Sesuaikan path jika berbeda

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Pengaturan Profil";
$message = '';
$message_type = ''; // 'success' atau 'danger'

// Path untuk menyimpan gambar yang diupload
$upload_dir = __DIR__ . '/images/'; // Pastikan direktori ini ada dan writable

// Fetch current profile data
$profile = null;
$sql_select = "SELECT * FROM tb_profile LIMIT 1";
$result = $koneksi->query($sql_select);
if ($result && $result->num_rows > 0) {
    $profile = $result->fetch_assoc();
} else {
    // Jika tidak ada profil, bisa set default atau berikan pesan error
    // Untuk saat ini, kita anggap profil sudah ada (diinsert saat pembuatan tabel)
    $message = "Data profil tidak ditemukan. Silakan hubungi administrator.";
    $message_type = "danger";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $profile) { // Hanya proses jika profil ada
    $id_profile = $profile['id_profile'];
    $nama_perusahaan = trim($_POST['nama_perusahaan']);
    $alamat = trim($_POST['alamat']);
    $telepon = trim($_POST['telepon']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $info_umum = trim($_POST['info_umum']);

    // Current image filenames
    $current_foto = $profile['foto'];
    $current_foto2 = $profile['foto2'];

    $new_foto_filename = $current_foto;
    $new_foto2_filename = $current_foto2;

    // Handle file upload untuk 'foto' (logo)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $foto_tmp_name = $_FILES['foto']['tmp_name'];
        $foto_name = basename($_FILES['foto']['name']);
        $foto_ext = strtolower(pathinfo($foto_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (in_array($foto_ext, $allowed_ext)) {
            // Buat nama file unik untuk mencegah penimpaan
            $new_foto_filename = "logo_" . uniqid() . "." . $foto_ext;
            if (move_uploaded_file($foto_tmp_name, $upload_dir . $new_foto_filename)) {
                // Hapus foto lama jika ada dan bukan default, dan nama file baru berbeda
                if ($current_foto && $current_foto != 'default-logo.png' && file_exists($upload_dir . $current_foto) && $current_foto != $new_foto_filename) {
                    unlink($upload_dir . $current_foto);
                }
            } else {
                $message = "Gagal mengupload file logo baru.";
                $message_type = "danger";
                $new_foto_filename = $current_foto; // Kembalikan ke nama lama jika gagal upload
            }
        } else {
            $message = "Format file logo tidak diizinkan. Hanya JPG, JPEG, PNG, GIF, WEBP, SVG.";
            $message_type = "danger";
        }
    }

    // Handle file upload untuk 'foto2' (ilustrasi)
    if (isset($_FILES['foto2']) && $_FILES['foto2']['error'] == UPLOAD_ERR_OK) {
        $foto2_tmp_name = $_FILES['foto2']['tmp_name'];
        $foto2_name = basename($_FILES['foto2']['name']);
        $foto2_ext = strtolower(pathinfo($foto2_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']; // Reuse allowed_ext

        if (in_array($foto2_ext, $allowed_ext)) { // Fixed variable usage from previous erroneous block
             $new_foto2_filename = "illust_" . uniqid() . "." . $foto2_ext;
            if (move_uploaded_file($foto2_tmp_name, $upload_dir . $new_foto2_filename)) {
                if ($current_foto2 && $current_foto2 != 'default-image.png' && file_exists($upload_dir . $current_foto2) && $current_foto2 != $new_foto2_filename) {
                    unlink($upload_dir . $current_foto2);
                }
            } else {
                $message = "Gagal mengupload file ilustrasi baru.";
                $message_type = "danger";
                $new_foto2_filename = $current_foto2;
            }
        } else {
             $message = "Format file ilustrasi tidak diizinkan.";
             $message_type = "danger";
        }
    }


    if ($message_type !== 'danger') { // Lanjutkan update jika tidak ada error upload sebelumnya
        $sql_update = "UPDATE tb_profile SET 
                        nama_perusahaan = ?, 
                        alamat = ?, 
                        telepon = ?, 
                        email = ?, 
                        website = ?, 
                        foto = ?, 
                        foto2 = ?, 
                        info_umum = ?
                      WHERE id_profile = ?";
        
        if ($stmt = $koneksi->prepare($sql_update)) {
            $stmt->bind_param("ssssssssi", 
                $nama_perusahaan, 
                $alamat, 
                $telepon, 
                $email, 
                $website, 
                $new_foto_filename, 
                $new_foto2_filename, 
                $info_umum,
                $id_profile
            );

            if ($stmt->execute()) {
                $message = "Profil perusahaan berhasil diperbarui.";
                $message_type = "success";
                // Re-fetch data untuk menampilkan yang terbaru di form
                $result = $koneksi->query($sql_select);
                if ($result && $result->num_rows > 0) {
                    $profile = $result->fetch_assoc();
                }
            } else {
                $message = "Gagal memperbarui profil perusahaan: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message = "Gagal menyiapkan statement update: " . $koneksi->error;
            $message_type = "danger";
        }
    }
}
$koneksi->close();
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
        /* Modern Card Header Override */
        .card-header h5 {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0;
        }
        
        /* Preview Image */
        .img-preview-container {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px dashed #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        .current-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            font-weight: 500;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
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
                    <p class="text-muted mb-0">Kelola informasi publik dan identitas instansi.</p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill fs-4 me-3"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($profile): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- Left Column: General Info -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header pt-4 px-4 bg-white border-bottom-0">
                                <h5>Informasi Utama</h5>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nama_perusahaan" class="form-label">Nama Instansi <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-building"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0 bg-light" id="nama_perusahaan" name="nama_perusahaan" value="<?php echo htmlspecialchars($profile['nama_perusahaan']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Resmi</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control border-start-0 ps-0 bg-light" id="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="alamat" class="form-label">Alamat Lengkap</label>
                                        <textarea class="form-control bg-light" id="alamat" name="alamat" rows="2"><?php echo htmlspecialchars($profile['alamat'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telepon" class="form-label">No. Telepon</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0 bg-light" id="telepon" name="telepon" value="<?php echo htmlspecialchars($profile['telepon'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="website" class="form-label">Website</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-globe"></i></span>
                                            <input type="url" class="form-control border-start-0 ps-0 bg-light" id="website" name="website" value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>" placeholder="https://">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="info_umum" class="form-label">Informasi Umum</label>
                                        <textarea class="form-control bg-light" id="info_umum" name="info_umum" rows="3" placeholder="Muncul di kartu informasi halaman depan..."><?php echo htmlspecialchars($profile['info_umum'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Images -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                             <div class="card-header pt-4 px-4 bg-white border-bottom-0">
                                <h5>Media & Branding</h5>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="mb-4">
                                    <label class="form-label d-block">Logo Instansi</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($profile['foto'])): ?>
                                            <div class="img-preview-container bg-white shadow-sm">
                                                <img src="images/<?php echo htmlspecialchars($profile['foto']); ?>" alt="Logo" class="current-image p-2">
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <input class="form-control form-control-sm mb-1" type="file" id="foto" name="foto" accept="image/*">
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">Format: JPG, PNG. Max 2MB.</small>
                                        </div>
                                    </div>
                                </div>
                                <hr class="border-light">
                                <div class="mb-3">
                                    <label class="form-label d-block">Ilustrasi Login/Welcome</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($profile['foto2'])): ?>
                                             <div class="img-preview-container bg-white shadow-sm">
                                                <img src="images/<?php echo htmlspecialchars($profile['foto2']); ?>" alt="Ilustrasi" class="current-image p-1">
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <input class="form-control form-control-sm mb-1" type="file" id="foto2" name="foto2" accept="image/*">
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">Untuk halaman depan.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-check-circle-fill me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Data profil perusahaan belum tersedia.
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close sidebar on mobile click outside (optional enhancement)
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
