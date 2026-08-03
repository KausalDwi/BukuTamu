<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php'; // Sesuaikan path jika berbeda

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak. Silakan login terlebih dahulu.");
}

$filename = "daftar_tamu_" . date('Ymd_His') . ".xls";
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Ambil data tamu
$sql_export_tamu = "SELECT 
                        id_tamu, 
                        tanggal_kunjungan, 
                        waktu_masuk, 
                        nama_tamu, 
                        asal_instansi, 
                        jabatan, 
                        no_telepon, 
                        email_tamu, 
                        bertemu_dengan, 
                        keperluan, 
                        catatan_tambahan, 
                        status_keluar, 
                        waktu_keluar,
                        foto_tamu,
                        tanda_tangan,
                        created_at
                    FROM tb_tamu 
                    ORDER BY tanggal_kunjungan DESC, waktu_masuk DESC";

$result_export = $koneksi->query($sql_export_tamu);
if ($result_export === false) {
    error_log("Gagal query ekspor data tamu: " . $koneksi->error);
    exit("Terjadi kesalahan saat mengambil data tamu.");
}

// Bangun HTML tabel agar Excel menampilkan format, warna, dan styling
echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><style>
    body { font-family: Arial, Helvetica, sans-serif; }
    .export-title { text-align: center; font-size: 20px; font-weight: bold; color: #0E5CAD; padding: 12px 0; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #b7c9e2; padding: 8px 10px; vertical-align: top; }
    th { background-color: #0E5CAD; color: #ffffff; font-weight: bold; text-align: center; }
    tr:nth-child(even) td { background-color: #f4f7fb; }
    .sub-info { font-size: 12px; color: #555; margin-bottom: 10px; }
    .text-center { text-align: center; }
    .status-masuk { color: #198754; font-weight: bold; }
    .status-keluar { color: #dc3545; font-weight: bold; }
</style></head><body>';

echo '<div class="export-title">Daftar Kunjungan Tamu</div>';
echo '<div class="sub-info text-center">Diekspor pada: ' . date('d-m-Y H:i:s') . '</div>';
echo '<table><thead><tr>
        <th>ID Tamu</th>
        <th>Tanggal Kunjungan</th>
        <th>Waktu Masuk</th>
        <th>Nama Tamu</th>
        <th>Asal Instansi</th>
        <th>Jabatan</th>
        <th>No. Telepon</th>
        <th>Email Tamu</th>
        <th>Bertemu Dengan</th>
        <th>Keperluan</th>
        <th>Catatan Tambahan</th>
        <th>Status Keluar</th>
        <th>Waktu Keluar</th>
        <th>Foto Tamu</th>
        <th>Tanda Tangan</th>
        <th>Dicatat Pada</th>
    </tr></thead><tbody>';

while ($row = $result_export->fetch_assoc()) {
    $tanggalKunjungan = $row['tanggal_kunjungan'] ? date('d-m-Y', strtotime($row['tanggal_kunjungan'])) : '';
    $waktuMasuk = $row['waktu_masuk'] ? date('H:i:s', strtotime($row['waktu_masuk'])) : '';
    $waktuKeluar = $row['waktu_keluar'] ? date('H:i:s', strtotime($row['waktu_keluar'])) : '';
    $createdAt = $row['created_at'] ? date('d-m-Y H:i:s', strtotime($row['created_at'])) : '';
    $statusClass = ($row['status_keluar'] === 'Keluar') ? 'status-keluar' : 'status-masuk';

    echo '<tr>';
    echo '<td class="text-center">' . htmlspecialchars($row['id_tamu']) . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($tanggalKunjungan) . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($waktuMasuk) . '</td>';
    echo '<td>' . htmlspecialchars($row['nama_tamu']) . '</td>';
    echo '<td>' . htmlspecialchars($row['asal_instansi']) . '</td>';
    echo '<td>' . htmlspecialchars($row['jabatan']) . '</td>';
    echo '<td>' . htmlspecialchars($row['no_telepon']) . '</td>';
    echo '<td>' . htmlspecialchars($row['email_tamu']) . '</td>';
    echo '<td>' . htmlspecialchars($row['bertemu_dengan']) . '</td>';
    echo '<td>' . nl2br(htmlspecialchars($row['keperluan'])) . '</td>';
    echo '<td>' . nl2br(htmlspecialchars($row['catatan_tambahan'])) . '</td>';
    echo '<td class="' . $statusClass . '">' . htmlspecialchars($row['status_keluar']) . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($waktuKeluar ?: '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['foto_tamu'] ?: '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['tanda_tangan'] ?: '-') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($createdAt) . '</td>';
    echo '</tr>';
}

echo '</tbody></table></body></html>';

$result_export->free();
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $koneksi->close();
}
exit;
?>
