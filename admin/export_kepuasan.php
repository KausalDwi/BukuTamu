<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Sesuaikan path jika berbeda

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak. Silakan login terlebih dahulu.");
}

// Nama file CSV yang akan di-download
$filename = "daftar_survei_kepuasan_" . date('Ymd_His') . ".csv";

// Set header HTTP untuk memicu download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache'); // HTTP/1.0
header('Expires: 0'); // Proxies

// Buka output stream PHP untuk menulis CSV
$output = fopen('php://output', 'w');
if ($output === false) {
    error_log("Gagal membuka php://output untuk ekspor CSV survei.");
    exit("Terjadi kesalahan saat mencoba membuat file ekspor.");
}

// Tulis baris header (nama kolom) untuk CSV
// Sesuaikan dengan kolom yang ingin Anda ekspor dari tb_kepuasan
fputcsv($output, [
    'ID Survei',
    'ID Tamu Terkait',
    'Nama Tamu Terkait (Jika Ada)',
    'Nama Responden (Manual)',
    'Tanggal Survei',
    'Waktu Survei',
    'Nilai Pelayanan (1-5)',
    'Nilai Fasilitas (1-5)',
    'Nilai Keramahan (1-5)',
    'Nilai Kecepatan (1-5)',
    'Saran & Masukan',
    'Waktu Submit (Created At)'
]);

// Query untuk mengambil semua data survei kepuasan
// Menggunakan LEFT JOIN untuk mendapatkan nama tamu jika id_tamu_fk terisi
$sql_export_survei = "SELECT 
                        ks.id_kepuasan, 
                        ks.id_tamu_fk, 
                        t.nama_tamu AS nama_tamu_terkait,
                        ks.nama_responden,
                        ks.tanggal_survei, 
                        ks.waktu_survei, 
                        ks.nilai_pelayanan, 
                        ks.nilai_fasilitas, 
                        ks.nilai_keramahan, 
                        ks.nilai_kecepatan, 
                        ks.saran_masukan,
                        ks.created_at
                    FROM tb_kepuasan ks
                    LEFT JOIN tb_tamu t ON ks.id_tamu_fk = t.id_tamu
                    ORDER BY ks.tanggal_survei DESC, ks.waktu_survei DESC";

$result_export = $koneksi->query($sql_export_survei);

if ($result_export) {
    if ($result_export->num_rows > 0) {
        // Loop melalui setiap baris data dan tulis ke CSV
        while ($row = $result_export->fetch_assoc()) {
            // Urutan data harus sesuai dengan urutan header
            fputcsv($output, [
                $row['id_kepuasan'],
                $row['id_tamu_fk'],
                $row['nama_tamu_terkait'], // Akan berisi nama tamu jika ada, atau NULL
                $row['nama_responden'],
                $row['tanggal_survei'],
                $row['waktu_survei'],
                $row['nilai_pelayanan'],
                $row['nilai_fasilitas'],
                $row['nilai_keramahan'],
                $row['nilai_kecepatan'],
                $row['saran_masukan'],
                $row['created_at']
            ]);
        }
    }
    $result_export->free();
} else {
    error_log("Gagal query ekspor data survei kepuasan: " . $koneksi->error);
    // fputcsv($output, ['Error: Gagal mengambil data survei dari database.']); // Opsional
}

// Tutup output stream dan koneksi database
fclose($output);
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $koneksi->close();
}
exit; // Pastikan tidak ada output lain setelah ini
?>