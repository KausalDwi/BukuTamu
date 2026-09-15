<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; 

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Manajemen Pegawai";

// Proses Ubah Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pegawai'])) {
    $id_pegawai = (int)$_POST['id_pegawai'];
    $kolom = $_POST['kolom']; // 'status_hadir' atau 'terima_tamu'
    $nilai_baru = $_POST['nilai_baru'];

    if (in_array($kolom, ['status_hadir', 'terima_tamu'])) {
        $sql_update = "UPDATE pegawai SET $kolom = ? WHERE id_pegawai = ?";
        if ($stmt = $koneksi->prepare($sql_update)) {
            $stmt->bind_param("si", $nilai_baru, $id_pegawai);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Refresh halaman agar perubahan terlihat
    header("Location: manajemen_pegawai.php");
    exit;
}

// Ambil data pegawai beserta nama divisinya
$pegawai_list = [];
$sql_pegawai = "SELECT p.*, d.nama_divisi FROM pegawai p JOIN divisi d ON p.id_divisi = d.id_divisi ORDER BY d.nama_divisi, p.nama_pegawai";
$result = $koneksi->query($sql_pegawai);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pegawai_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
</head>
<body>
    <?php
    if (file_exists(__DIR__ . '/_partials/navbar.php')) { include_once __DIR__ . '/_partials/navbar.php'; }
    if (file_exists(__DIR__ . '/_partials/sidebar.php')) { include_once __DIR__ . '/_partials/sidebar.php'; }
    ?>

    <main class="main-content">
        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom-0">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($page_title) ?></h1>
                    <p class="text-muted mb-0">Atur ketersediaan dan status kehadiran pegawai di kantor.</p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body px-4 pb-4 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>Nama Pegawai</th>
                                    <th>Divisi</th>
                                    <th class="text-center">Status Kehadiran</th>
                                    <th class="text-center">Terima Tamu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach($pegawai_list as $p): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($p['nama_pegawai']) ?></td>
                                    <td><?= htmlspecialchars($p['nama_divisi']) ?></td>
                                    
                                    <!-- Tombol Toggle Kehadiran -->
                                    <td class="text-center">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id_pegawai" value="<?= $p['id_pegawai'] ?>">
                                            <input type="hidden" name="kolom" value="status_hadir">
                                            <?php if($p['status_hadir'] === 'Hadir'): ?>
                                                <input type="hidden" name="nilai_baru" value="Tidak Hadir">
                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3"><i class="bi bi-building-check me-1"></i> Hadir</button>
                                            <?php else: ?>
                                                <input type="hidden" name="nilai_baru" value="Hadir">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-building-x me-1"></i> Tidak Hadir</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>

                                    <!-- Tombol Toggle Terima Tamu -->
                                    <td class="text-center">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id_pegawai" value="<?= $p['id_pegawai'] ?>">
                                            <input type="hidden" name="kolom" value="terima_tamu">
                                            <?php if($p['terima_tamu'] === 'Ya'): ?>
                                                <input type="hidden" name="nilai_baru" value="Tidak">
                                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Bisa</button>
                                            <?php else: ?>
                                                <input type="hidden" name="nilai_baru" value="Ya">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">Sibuk</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>