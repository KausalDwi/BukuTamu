<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Sesuaikan path jika berbeda

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Update Waktu Keluar";
$tamu_info = null; // Untuk menampilkan info tamu
$id_tamu_to_edit = null;
$errors = [];
$success_message = '';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_tamu_to_edit = $_GET['id'];
    // Fetch data tamu yang akan diupdate waktu keluarnya
    $sql_get_tamu = "SELECT id_tamu, nama_tamu, tanggal_kunjungan, waktu_masuk, keperluan, status_keluar, waktu_keluar 
                     FROM tb_tamu 
                     WHERE id_tamu = ?";
    if ($stmt_get = $koneksi->prepare($sql_get_tamu)) {
        $stmt_get->bind_param("i", $id_tamu_to_edit);
        $stmt_get->execute();
        $result_tamu = $stmt_get->get_result();
        if ($result_tamu->num_rows === 1) {
            $tamu_info = $result_tamu->fetch_assoc();
        } else {
            $_SESSION['message'] = "Data tamu tidak ditemukan.";
            $_SESSION['message_type'] = "danger";
            header("Location: data_tamu.php");
            exit;
        }
        $stmt_get->close();
    } else {
        $_SESSION['message'] = "Gagal menyiapkan data tamu.";
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

// Inisialisasi waktu keluar dengan data yang ada atau kosongkan
$waktu_keluar_form = $tamu_info['waktu_keluar'] ?: '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $waktu_keluar_input = trim($_POST['waktu_keluar']);
    $status_keluar_baru = 'Keluar'; // Selalu set 'Keluar' saat form ini disubmit

    // Jika waktu keluar tidak diisi oleh admin, gunakan waktu saat ini
    if (empty($waktu_keluar_input)) {
        $waktu_keluar_final = date("H:i:s");
    } else {
        // Validasi format waktu jika diisi manual
        if (!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/", $waktu_keluar_input)) {
             $errors[] = "Format waktu keluar tidak valid. Gunakan HH:MM atau HH:MM:SS.";
        } else {
            $waktu_keluar_final = $waktu_keluar_input;
        }
    }

    if (empty($errors)) {
        $sql_update = "UPDATE tb_tamu SET status_keluar = ?, waktu_keluar = ? WHERE id_tamu = ?";
        
        if ($stmt_update = $koneksi->prepare($sql_update)) {
            $stmt_update->bind_param("ssi", 
                $status_keluar_baru, 
                $waktu_keluar_final, 
                $id_tamu_to_edit
            );

            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Waktu keluar tamu '" . htmlspecialchars($tamu_info['nama_tamu']) . "' berhasil diperbarui.";
                $_SESSION['message_type'] = "success";
                header("Location: data_tamu.php"); // Kembali ke daftar tamu
                exit;
            } else {
                $errors[] = "Gagal memperbarui waktu keluar tamu: " . $stmt_update->error;
                error_log("Gagal update waktu keluar tb_tamu: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            $errors[] = "Gagal menyiapkan statement update waktu keluar: " . $koneksi->error;
            error_log("Gagal prepare update waktu keluar tb_tamu: " . $koneksi->error);
        }
    }
    // Jika ada error, $waktu_keluar_form akan berisi data POST terbaru untuk diisi kembali ke form
    $waktu_keluar_form = $waktu_keluar_input; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ($tamu_info ? ' - ' . htmlspecialchars($tamu_info['nama_tamu']) : ''); ?> - Admin</title>
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
                            <li class="breadcrumb-item"><a href="data_tamu.php" class="text-decoration-none text-muted">Data Tamu</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Update Status</li>
                        </ol>
                    </nav>
                    <h1 class="h3 fw-bold text-dark mb-0">Update Waktu Keluar</h1>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="data_tamu.php" class="btn btn-light shadow-sm border text-muted">
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

            <?php if ($tamu_info): ?>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header pt-4 px-4 text-center border-bottom-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-clock-history fs-3"></i>
                            </div>
                            <h5 class="mb-1 text-dark fw-bold">Update Waktu Keluar</h5>
                            <p class="text-muted small mb-0">Atur waktu selesai kunjungan untuk tamu ini.</p>
                        </div>
                        <div class="card-body px-4 pb-5">
                            <div class="bg-light rounded-3 p-3 mb-4 row mx-0">
                                <div class="col-6 mb-2">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size:0.75rem;">Nama Tamu</small>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($tamu_info['nama_tamu']); ?></div>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size:0.75rem;">Waktu Masuk</small>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($tamu_info['waktu_masuk']); ?></div>
                                </div>
                                <div class="col-12 border-top pt-2 mt-1">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size:0.75rem;">Status Saat Ini</small>
                                    <div class="mt-1">
                                        <span class="badge <?php echo $tamu_info['status_keluar'] == 'Masuk' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars($tamu_info['status_keluar']); ?>
                                        </span>
                                        <?php if($tamu_info['waktu_keluar']): ?>
                                            <span class="small text-muted ms-2">(<?php echo htmlspecialchars($tamu_info['waktu_keluar']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $id_tamu_to_edit; ?>">
                                <div class="mb-4">
                                    <label for="waktu_keluar" class="form-label">Waktu Keluar</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                        <input type="time" class="form-control form-control-lg" id="waktu_keluar" name="waktu_keluar" value="<?php echo htmlspecialchars($waktu_keluar_form); ?>">
                                    </div>
                                    <div class="form-text mt-2 text-warning small">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Kosongkan untuk menggunakan waktu saat ini secara otomatis.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg shadow-sm fw-medium" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border:none;">
                                        Simpan Status Keluar
                                    </button>
                                    <button type="button" class="btn btn-light btn-lg border" onclick="history.back()">Batal</button>
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