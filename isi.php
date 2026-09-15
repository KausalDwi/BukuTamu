<?php
// Aktifkan error reporting untuk debugging jika diperlukan
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan variabel $koneksi sudah ada
if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
    $koneksiPath = __DIR__ . DIRECTORY_SEPARATOR . "koneksi" . DIRECTORY_SEPARATOR . "koneksi.php";
    if (file_exists($koneksiPath)) {
        require_once $koneksiPath;
    } else {
        $_SESSION['gagal'] = "Koneksi database tidak tersedia untuk memproses formulir.";
    }
}

// === AMBIL DATA DIVISI & PEGAWAI UNTUK DROPDOWN ===
$divisi_list = [];
$pegawai_list = [];
if (isset($koneksi) && $koneksi instanceof mysqli) {
    // Ambil semua divisi
    $res_divisi = $koneksi->query("SELECT * FROM divisi ORDER BY nama_divisi ASC");
    if ($res_divisi) {
        while ($row = $res_divisi->fetch_assoc()) {
            $divisi_list[] = $row;
        }
    }
    
    // Ambil pegawai yang sedang HADIR dan BISA TERIMA TAMU
    $res_pegawai = $koneksi->query("SELECT id_pegawai, nama_pegawai, id_divisi FROM pegawai WHERE status_hadir = 'Hadir' AND terima_tamu = 'Ya' ORDER BY nama_pegawai ASC");
    if ($res_pegawai) {
        while ($row = $res_pegawai->fetch_assoc()) {
            $pegawai_list[] = $row;
        }
    }
}

// === AWAL BLOK LOGIKA FORM REGISTRASI TAMU ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nama_tamu'])) { 
    $nama_tamu = htmlspecialchars(trim($_POST['nama_tamu'] ?? ''));
    $asal_instansi = htmlspecialchars(trim($_POST['asal_instansi'] ?? ''));
    $jabatan = htmlspecialchars(trim($_POST['jabatan'] ?? ''));
    $no_telepon = htmlspecialchars(trim($_POST['no_telepon'] ?? ''));
    $email_tamu = htmlspecialchars(trim($_POST['email_tamu'] ?? ''));
    $bertemu_dengan = htmlspecialchars(trim($_POST['bertemu_dengan'] ?? ''));
    $keperluan = htmlspecialchars(trim($_POST['keperluan'] ?? ''));
    $catatan_tambahan = htmlspecialchars(trim($_POST['catatan_tambahan'] ?? ''));
    
    // Validasi Checkbox PDP
    if (!isset($_POST['persetujuan_pdp'])) {
        $_SESSION['gagal'] = "Anda harus menyetujui kebijakan Perlindungan Data Pribadi (PDP).";
    } else {
        $foto_tamu_filename = null;
        $foto_processing_error = false;
        
        // Proses Foto Base64
        if (!empty($_POST['foto_tamu_data'])) {
            $raw_foto_input = $_POST['foto_tamu_data'];
            if (preg_match('/^data:image\/(\w+);base64,/', $raw_foto_input, $match)) {
                $mime_extension = strtolower($match[1]);
                $mime_extension = $mime_extension === 'jpeg' ? 'jpg' : $mime_extension;
                $allowed_extensions = ['jpg', 'jpeg', 'png'];
                if (!in_array($mime_extension, $allowed_extensions, true)) {
                    $_SESSION['gagal'] = "Format foto tidak didukung. Gunakan JPG atau PNG.";
                    $foto_processing_error = true;
                } else {
                    $base64_data = substr($raw_foto_input, strpos($raw_foto_input, ',') + 1);
                    $base64_data = str_replace(' ', '+', $base64_data);
                    $image_binary = base64_decode($base64_data, true);
                    if ($image_binary === false) {
                        $_SESSION['gagal'] = "Foto tidak dapat diproses. Silakan coba lagi.";
                        $foto_processing_error = true;
                    } else {
                        $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tamu';
                        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
                            $_SESSION['gagal'] = "Folder penyimpanan foto tidak dapat dibuat.";
                            $foto_processing_error = true;
                        } else {
                            $htaccess_path = $upload_dir . DIRECTORY_SEPARATOR . '.htaccess';
                            if (!file_exists($htaccess_path)) {
                                file_put_contents($htaccess_path, "php_flag engine off");
                            }

                            $random_suffix = bin2hex(random_bytes(4));
                            $foto_tamu_filename = 'tamu_' . date('Ymd_His') . '_' . $random_suffix . '.' . $mime_extension;
                            $foto_path = $upload_dir . DIRECTORY_SEPARATOR . $foto_tamu_filename;
                            
                            if (file_put_contents($foto_path, $image_binary) === false) {
                                $_SESSION['gagal'] = "Foto gagal disimpan ke server.";
                                $foto_processing_error = true;
                                $foto_tamu_filename = null;
                            }
                        }
                    }
                }
            }
        }

        $tanggal_kunjungan = date("Y-m-d");
        $waktu_masuk = date("H:i:s");

        if (empty($nama_tamu) || empty($asal_instansi) || empty($jabatan) || empty($no_telepon) || empty($email_tamu) || empty($bertemu_dengan)) {
            $_SESSION['gagal'] = "Semua kolom wajib diisi kecuali keperluan.";
        } elseif ($foto_processing_error) {
            // Error foto sudah diset di atas
        } else {
            $sql_tamu = "INSERT INTO tb_tamu (tanggal_kunjungan, waktu_masuk, nama_tamu, asal_instansi, jabatan, no_telepon, email_tamu, bertemu_dengan, keperluan, catatan_tambahan, foto_tamu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt_tamu = $koneksi->prepare($sql_tamu)) {
                $stmt_tamu->bind_param("sssssssssss",
                    $tanggal_kunjungan, $waktu_masuk, $nama_tamu, $asal_instansi, $jabatan, $no_telepon, $email_tamu, $bertemu_dengan, $keperluan, $catatan_tambahan, $foto_tamu_filename
                );
                
                if ($stmt_tamu->execute()) {
                    $_SESSION['sukses'] = "Registrasi kunjungan berhasil disimpan. Terima kasih!";
                } else {
                    $_SESSION['gagal'] = "Gagal mengeksekusi data: " . $stmt_tamu->error;
                }
                $stmt_tamu->close();
            } else {
                $_SESSION['gagal'] = "Gagal menyiapkan statement SQL: " . $koneksi->error;
            }
        }
    }

    if (!empty($_SESSION['gagal'])) {
        $_SESSION['old_tamu'] = $_POST;
    } else {
        unset($_SESSION['old_tamu']);
    }

    echo "<script>window.location.href='index.php';</script>";
    exit;
}
// === AKHIR BLOK LOGIKA FORM REGISTRASI TAMU ===

// === AWAL BLOK LOGIKA FORM KEPUASAN (SPK) ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_kepuasan'])) { 
    if (isset($koneksi) && $koneksi instanceof mysqli) {
        $nama_responden = htmlspecialchars(trim($_POST['nama_responden'] ?? '')); 
        $id_tamu_fk = filter_input(INPUT_POST, 'id_tamu_fk', FILTER_VALIDATE_INT) ?: null;

        $nilai_pelayanan = filter_input(INPUT_POST, 'nilai_pelayanan', FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>5]]);
        $nilai_fasilitas = filter_input(INPUT_POST, 'nilai_fasilitas', FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>5]]);
        $nilai_keramahan = filter_input(INPUT_POST, 'nilai_keramahan', FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>5]]);
        $nilai_kecepatan = filter_input(INPUT_POST, 'nilai_kecepatan', FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>5]]);
        $saran_masukan = htmlspecialchars(trim($_POST['saran_masukan'] ?? ''));

        $tanggal_survei = date("Y-m-d");
        $waktu_survei = date("H:i:s");

        if ($nilai_pelayanan === false || $nilai_fasilitas === false || $nilai_keramahan === false || $nilai_kecepatan === false) {
            $_SESSION['gagal'] = "Semua penilaian wajib diisi (skala 1-5).";
        } else {
            $sql_kepuasan = "INSERT INTO tb_kepuasan (id_tamu_fk, nama_responden, tanggal_survei, waktu_survei, nilai_pelayanan, nilai_fasilitas, nilai_keramahan, nilai_kecepatan, saran_masukan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt_kepuasan = $koneksi->prepare($sql_kepuasan)) {
                $stmt_kepuasan->bind_param("isssiiiis", $id_tamu_fk, $nama_responden, $tanggal_survei, $waktu_survei, $nilai_pelayanan, $nilai_fasilitas, $nilai_keramahan, $nilai_kecepatan, $saran_masukan);
                if ($stmt_kepuasan->execute()) {
                    $_SESSION['sukses'] = "Survei kepuasan berhasil dikirim. Terima kasih!";
                } else {
                    $_SESSION['gagal'] = "Gagal menyimpan survei: " . $stmt_kepuasan->error;
                }
                $stmt_kepuasan->close();
            } else {
                $_SESSION['gagal'] = "Gagal menyiapkan statement survei: " . $koneksi->error;
            }
        }
    }
    echo "<script>window.location.href='index.php?page=spk';</script>";
    exit;
}
// === AKHIR BLOK LOGIKA FORM KEPUASAN ===

if (!isset($profile)) {
    $profile = ['nama_perusahaan' => 'Buku Tamu Digital', 'foto' => 'default-logo.png'];
}
$old_tamu = $_SESSION['old_tamu'] ?? [];
?>

<?php if (isset($_GET['page']) && $_GET['page'] === 'spk'): ?>
    <!-- TAMPILAN FORM KEPUASAN -->
    <div class="card card-tamu shadow-lg rounded-4 overflow-hidden border-0">
        <div class="card-header bg-white border-bottom pt-4 pb-3 text-center">
            <div class="d-inline-flex align-items-center justify-content-center gap-2 mb-2">
                <img src="admin/images/<?= htmlspecialchars($profile['foto'] ?? 'default-logo.png') ?>" alt="Logo" style="height: 40px; width: 40px; object-fit: contain;">
                <h5 class="mb-0 fw-bold text-dark text-uppercase"><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Buku Tamu') ?></h5>
            </div>
            <p class="small text-muted mb-0">Survei Kepuasan Pelayanan</p>
        </div>

        <div class="card-body p-4 p-md-5">
            <form id="formKepuasan" method="POST" action="index.php?page=spk">
<input type="hidden" name="submit_kepuasan" value="1"> 
                
    <?php
    if (isset($_SESSION['gagal'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> ' . $_SESSION['gagal'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        unset($_SESSION['gagal']); // Hapus pesan setelah ditampilkan
    }
    ?>
                    
                    <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Identitas Anda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted ps-3"><i class="bi bi-person-circle"></i></span>
                        <input type="text" class="form-control bg-light border-start-0 py-3" name="nama_responden" placeholder="Nama Anda (Opsional)">
                    </div>
                </div>


                <div class="row g-3 mb-4">
                     <?php 
                     $criteria = [
                        'nilai_pelayanan' => ['icon' => 'headset', 'label' => 'Kualitas Pelayanan'],
                        'nilai_fasilitas' => ['icon' => 'building-gear', 'label' => 'Fasilitas'],
                        'nilai_keramahan' => ['icon' => 'emoji-smile', 'label' => 'Keramahan Staf'],
                        'nilai_kecepatan' => ['icon' => 'lightning-charge', 'label' => 'Kecepatan']
                     ];
                     foreach($criteria as $name => $data): ?>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-white shadow-sm h-100">
                             <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle p-2 me-2 text-primary"><i class="bi bi-<?= $data['icon'] ?>"></i></div>
                                <h6 class="mb-0 fw-bold text-dark"><?= $data['label'] ?></h6>
                             </div>
                            <div class="d-flex justify-content-between px-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="form-check text-center mx-1">
                                    <input class="form-check-input float-none mb-1 shadow-none" type="radio" name="<?= $name ?>" id="<?= $name ?>_<?= $i ?>" value="<?= $i ?>" required>
                                    <label class="form-check-label d-block small text-muted" for="<?= $name ?>_<?= $i ?>"><?= $i ?></label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mb-5">
                    <label class="form-label small fw-bold text-uppercase text-muted">Saran & Masukan</label>
                    <textarea class="form-control bg-light py-3" name="saran_masukan" rows="3" placeholder="Tulis saran Anda..."></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" name="submit_tamu" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm" style="background: var(--primary-gradient); border: none;">
                        <i class="bi bi-send-fill me-2"></i> Kirim Kunjungan
                    </button>
                    <a href="index.php" class="btn btn-link text-muted mt-3 text-decoration-none small text-center"><i class="bi bi-arrow-left me-1"></i> Kembali ke Form Tamu</a>
                </div>
            </form>
        </div>
    </div>

<?php elseif (isset($_GET['page']) && $_GET['page'] === 'ketersediaan'): ?>
    
    <!-- TAMPILAN GRID KETERSEDIAAN PEGAWAI -->
    <div class="card card-tamu shadow-lg rounded-4 overflow-hidden border-0 bg-transparent">
        <div class="card-header bg-white border-bottom pt-4 pb-3 text-center rounded-top-4 shadow-sm mb-4">
            <h5 class="mb-0 fw-bold text-dark text-uppercase">Informasi Kehadiran Pegawai</h5>
            <p class="small text-muted mb-0">Cek status pegawai sebelum mengisi buku tamu</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
            <?php
            if (isset($koneksi) && $koneksi instanceof mysqli) {
                $q_pegawai = $koneksi->query("SELECT p.*, d.nama_divisi FROM pegawai p JOIN divisi d ON p.id_divisi = d.id_divisi ORDER BY d.nama_divisi, p.nama_pegawai");
                
                if ($q_pegawai && $q_pegawai->num_rows > 0) {
                    while($p = $q_pegawai->fetch_assoc()):
                        $is_hadir = ($p['status_hadir'] === 'Hadir');
                        $is_bisa_tamu = ($p['terima_tamu'] === 'Ya');
                        
                        $border_color = $is_hadir ? 'border-success' : 'border-secondary';
                        $bg_color = $is_hadir ? 'bg-white' : 'bg-light';
                        $text_status = $is_hadir ? 'Ada di Kantor' : 'Tidak di Tempat';
                        $icon_status = $is_hadir ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>' : '<i class="bi bi-x-circle-fill text-secondary fs-5"></i>';
                    ?>
                    <div class="col">
                        <div class="card h-100 <?= $border_color ?> <?= $bg_color ?> shadow-sm" style="border-width: 2px;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="card-title fw-bold mb-0"><?= htmlspecialchars($p['nama_pegawai']) ?></h6>
                                    <?= $icon_status ?>
                                </div>
                                <p class="card-text small text-muted mb-3"><i class="bi bi-diagram-3 me-1"></i> <?= htmlspecialchars($p['nama_divisi']) ?></p>
                                
                                <?php if ($is_hadir && $is_bisa_tamu): ?>
                                    <span class="badge bg-primary rounded-pill w-100 py-2">Bisa Menerima Tamu</span>
                                <?php elseif ($is_hadir && !$is_bisa_tamu): ?>
                                    <span class="badge bg-danger rounded-pill w-100 py-2">Sedang Sibuk / Rapat</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill w-100 py-2">Tidak Tersedia</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php 
                    endwhile;
                } else {
                    echo '<div class="col-12"><div class="alert alert-info border-0 shadow-sm text-center">Belum ada data pegawai.</div></div>';
                }
            }
            ?>
        </div>

        <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm text-white" style="background: var(--gradient-main); border: none;">
            
                <i class="bi bi-pencil-square me-2"></i> Isi Buku Tamu Sekarang
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- TAMPILAN FORM REGISTRASI TAMU -->
    <div class="card card-tamu shadow-lg rounded-4 overflow-hidden border-0">
        <div class="card-header bg-white border-bottom pt-4 pb-3 text-center">
            <div class="d-inline-flex align-items-center justify-content-center gap-2 mb-2">
                <img src="admin/images/<?= htmlspecialchars($profile['foto'] ?? 'default-logo.png') ?>" alt="Logo" style="height: 40px; width: 40px; object-fit: contain;">
                <h5 class="mb-0 fw-bold text-dark text-uppercase"><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Buku Tamu') ?></h5>
            </div>
            <p class="small text-muted mb-0">Silakan isi data diri Anda untuk keperluan kunjungan.</p>
        </div>

        <div class="card-body p-4 p-md-5">
            <form id="formRegistrasiTamu" method="POST" action="index.php" class="needs-validation" novalidate>
                
                <div class="mb-4">
                    <h6 class="text-uppercase text-primary fw-bold small mb-3 border-bottom pb-2">Data Diri</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-person-vcard fs-5"></i></span>
                                <input type="text" class="form-control bg-light border-0 py-3" id="nama_tamu" name="nama_tamu" placeholder="Nama Lengkap Anda" value="<?= htmlspecialchars($old_tamu['nama_tamu'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-building fs-5"></i></span>
                                <input type="text" class="form-control bg-light border-0 py-3" id="asal_instansi" name="asal_instansi" placeholder="Asal Instansi/Umum" value="<?= htmlspecialchars($old_tamu['asal_instansi'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-person-badge fs-5"></i></span>
                                <input type="text" class="form-control bg-light border-0 py-3" id="jabatan" name="jabatan" placeholder="Jabatan" value="<?= htmlspecialchars($old_tamu['jabatan'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-whatsapp fs-5"></i></span>
                                <input type="tel" class="form-control bg-light border-0 py-3" id="no_telepon" name="no_telepon" placeholder="No. WA / HP" value="<?= htmlspecialchars($old_tamu['no_telepon'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-envelope fs-5"></i></span>
                                <input type="email" class="form-control bg-light border-0 py-3" id="email_tamu" name="email_tamu" placeholder="Email" value="<?= htmlspecialchars($old_tamu['email_tamu'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                     <h6 class="text-uppercase text-primary fw-bold small mb-3 border-bottom pb-2">Detail Kunjungan</h6>
                     <div class="row g-3">
                        
                        <!-- Pilihan Divisi -->
                        <div class="col-md-6">
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-diagram-3 fs-5"></i></span>
                                <select class="form-select bg-light border-0 py-3" id="pilih_divisi" required>
                                    <option value="" disabled selected>Pilih Divisi Tujuan...</option>
                                    <?php foreach($divisi_list as $div): ?>
                                        <option value="<?= $div['id_divisi'] ?>"><?= htmlspecialchars($div['nama_divisi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Pilihan Pegawai yang Available -->
                        <div class="col-md-6">
                             <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-person-check fs-5"></i></span>
                                <select class="form-select bg-light border-0 py-3" id="bertemu_dengan" name="bertemu_dengan" required disabled>
                                    <option value="" disabled selected>Pilih Pegawai...</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                             <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0 ps-3 text-muted pt-3"><i class="bi bi-card-text fs-5"></i></span>
                                <textarea class="form-control bg-light border-0 py-3" id="keperluan" name="keperluan" rows="3" placeholder="Keperluan Kunjungan..."><?= htmlspecialchars($old_tamu['keperluan'] ?? '') ?></textarea>
                            </div>
                        </div>
                     </div>
                </div>

                <!-- Section Camera Widget -->
                <div class="mb-4">
                    <h6 class="text-uppercase text-primary fw-bold small mb-3 border-bottom pb-2">Foto Identitas</h6>
                    <div class="camera-widget bg-light rounded-4 p-3 border border-2 border-dashed text-center">
                        <div class="camera-display position-relative overflow-hidden rounded-4 shadow-sm mb-3 bg-white" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                            <div id="kameraPlaceholder" class="text-center w-100 py-5">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                                    <i class="bi bi-camera-fill fs-1 text-primary"></i>
                                </div>
                                <h6 class="fw-bold text-dark">Ambil Foto Selfie</h6>
                                <p class="small text-muted mb-0">Verifikasi identitas kunjungan.</p>
                            </div>
                            <video id="kameraTamuPreview" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0 d-none" autoplay playsinline style="transform: scaleX(-1);"></video>
                            <img id="kameraTamuSnapshot" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0 d-none" alt="Snapshot">
                        </div>

                        <div class="camera-controls d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-medium" id="btnMulaiKamera"><i class="bi bi-camera-video me-2"></i>Aktifkan Kamera</button>
                            <button type="button" class="btn btn-danger rounded-circle shadow-lg p-0 d-none align-items-center justify-content-center" id="btnAmbilFoto" style="width: 60px; height: 60px; border: 4px solid white;"><i class="bi bi-camera fs-4"></i></button>
                            <button type="button" class="btn btn-secondary rounded-pill px-4 fw-medium d-none" id="btnUlangFoto"><i class="bi bi-arrow-counterclockwise me-2"></i>Ulang</button>
                        </div>
                        <input type="hidden" name="foto_tamu_data" id="fotoTamuData">
                        <small class="d-block text-muted mt-2" id="kameraStatus"></small>
                    </div>
                </div>

                <!-- Checkbox PDP -->
                <div class="form-check mb-4 p-3 border rounded-3 shadow-sm" style="background-color: #f8f9fa;">
                    <input class="form-check-input ms-1 mt-1" type="checkbox" name="persetujuan_pdp" id="pdpCheck" required>
                    <label class="form-check-label ms-2 small text-muted" for="pdpCheck">
                        <strong class="text-dark">Persetujuan Perlindungan Data Pribadi (PDP)</strong><br>
                        Saya menyetujui bahwa data diri beserta foto yang saya berikan akan disimpan dan diproses oleh Diskominfo semata-mata untuk keperluan pendataan dan keamanan tamu sesuai dengan regulasi yang berlaku.
                    </label>
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3 fw-bold shadow-lg text-white" style="background: var(--primary-color); border: none;">
                        <i class="bi bi-send-fill me-2"></i> SIMPAN BUKU TAMU <i class="bi bi-chevron-right ms-2 small"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Kamera & Dropdown Dinamis -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- LOGIKA DROPDOWN DINAMIS ---
            const dataPegawai = <?= json_encode($pegawai_list) ?>;
            const selectDivisi = document.getElementById('pilih_divisi');
            const selectPegawai = document.getElementById('bertemu_dengan');

            if(selectDivisi && selectPegawai) {
                selectDivisi.addEventListener('change', function() {
                    const idDivisiTerpilih = this.value;
                    
                    selectPegawai.innerHTML = '<option value="" disabled selected>Pilih Pegawai...</option>';
                    const pegawaiDifilter = dataPegawai.filter(p => p.id_divisi == idDivisiTerpilih);

                    if (pegawaiDifilter.length > 0) {
                        pegawaiDifilter.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.nama_pegawai; 
                            opt.textContent = p.nama_pegawai;
                            selectPegawai.appendChild(opt);
                        });
                        selectPegawai.disabled = false;
                    } else {
                        const opt = document.createElement('option');
                        opt.value = "";
                        opt.disabled = true;
                        opt.selected = true;
                        opt.textContent = "-- Tidak ada pegawai di kantor --";
                        selectPegawai.appendChild(opt);
                        selectPegawai.disabled = true;
                    }
                });
            }

            // --- LOGIKA KAMERA ---
            const startBtn = document.getElementById('btnMulaiKamera');
            const captureBtn = document.getElementById('btnAmbilFoto');
            const retakeBtn = document.getElementById('btnUlangFoto');
            const videoEl = document.getElementById('kameraTamuPreview');
            const snapshotImg = document.getElementById('kameraTamuSnapshot');
            const placeholder = document.getElementById('kameraPlaceholder');
            const statusEl = document.getElementById('kameraStatus');
            const hiddenInput = document.getElementById('fotoTamuData');

            let streamHandle = null;

            function setWidgetState(state) {
                if(!placeholder) return;
                placeholder.classList.add('d-none');
                videoEl.classList.add('d-none');
                snapshotImg.classList.add('d-none');
                startBtn.classList.add('d-none');
                captureBtn.classList.add('d-none');
                captureBtn.classList.remove('d-flex');
                retakeBtn.classList.add('d-none');

                if (state === 'idle') {
                    placeholder.classList.remove('d-none');
                    startBtn.classList.remove('d-none');
                } else if (state === 'active') {
                    videoEl.classList.remove('d-none');
                    captureBtn.classList.remove('d-none');
                    captureBtn.classList.add('d-flex');
                } else if (state === 'captured') {
                    snapshotImg.classList.remove('d-none');
                    retakeBtn.classList.remove('d-none');
                }
            }

            if (startBtn) {
                startBtn.addEventListener('click', async () => {
                    try {
                        streamHandle = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                        if (videoEl) {
                            videoEl.srcObject = streamHandle;
                            await videoEl.play();
                            setWidgetState('active');
                            statusEl.innerText = "Kamera aktif.";
                        }
                    } catch (error) {
                        statusEl.innerText = "Gagal akses kamera: " + error.message;
                        statusEl.className = "d-block text-danger mt-2 fw-bold";
                    }
                });
            }

            if (captureBtn) {
                captureBtn.addEventListener('click', () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = videoEl.videoWidth;
                    canvas.height = videoEl.videoHeight;
                    canvas.getContext('2d').drawImage(videoEl, 0, 0);
                    
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    hiddenInput.value = dataUrl;
                    snapshotImg.src = dataUrl;
                    
                    if (streamHandle) {
                        streamHandle.getTracks().forEach(track => track.stop());
                    }
                    
                    setWidgetState('captured');
                    statusEl.innerText = "Foto berhasil diambil.";
                    statusEl.className = "d-block text-success mt-2 fw-bold";
                });
            }

            if (retakeBtn) {
                retakeBtn.addEventListener('click', () => {
                    hiddenInput.value = '';
                    setWidgetState('idle');
                    statusEl.innerText = "";
                });
            }
        });
        (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Harap lengkapi semua data sebelum menyimpan!',
                    confirmButtonColor: '#0E5CAD'
                });
            }
            form.classList.add('was-validated')
            }, false)
        })
        })()
    </script>
<?php endif; ?>