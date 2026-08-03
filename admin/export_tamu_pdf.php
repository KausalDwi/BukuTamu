<?php
session_start();

require_once __DIR__ . '/../koneksi/koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak. Silakan login terlebih dahulu.");
}

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
                        created_at
                    FROM tb_tamu 
                    ORDER BY tanggal_kunjungan DESC, waktu_masuk DESC";

$result_export = $koneksi->query($sql_export_tamu);
if ($result_export === false) {
    error_log("Gagal query ekspor data tamu (PDF): " . $koneksi->error);
    exit("Terjadi kesalahan saat mengambil data tamu.");
}

$export_time = date('d-m-Y H:i:s');
$rows = [];
while ($row = $result_export->fetch_assoc()) {
    $rows[] = $row;
}
$result_export->free();
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $koneksi->close();
}

$html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ekspor PDF - Data Tamu</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Poppins", Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .export-header {
            border-bottom: 2px solid #0E5CAD;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .table-title {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            margin: 10px 0 6px;
        }
        .export-title {
            font-size: 18px;
            font-weight: 700;
            color: #0E5CAD;
        }
        .export-subtitle {
            font-size: 11px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        thead th {
            background: #0E5CAD;
            color: #fff;
            padding: 6px;
            text-align: left;
            border: 1px solid #0E5CAD;
            font-weight: 600;
            white-space: nowrap;
        }
        tbody td {
            border: 1px solid #d7e3f2;
            padding: 6px;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td {
            background: #f4f7fb;
        }
        .text-center { text-align: center; }
        .status-masuk { color: #198754; font-weight: 600; }
        .status-keluar { color: #dc3545; font-weight: 600; }
    </style>
</head>
<body>
    <div class="export-header">
        <div class="export-title">Data Kunjungan Tamu</div>
        <div class="export-subtitle">Diekspor pada: ' . htmlspecialchars($export_time) . '</div>
    </div>
    <div class="table-title">Tabel Data Kunjungan Tamu</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">No</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Tanggal</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Waktu</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Nama</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Instansi</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Jabatan</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Telepon</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Email</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Bertemu</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Keperluan</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Status</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
foreach ($rows as $row) {
    $tanggal = $row['tanggal_kunjungan'] ? date('d-m-Y', strtotime($row['tanggal_kunjungan'])) : '';
    $waktu = $row['waktu_masuk'] ? substr($row['waktu_masuk'], 0, 5) : '';
    $statusClass = ($row['status_keluar'] === 'Keluar') ? 'status-keluar' : 'status-masuk';

    $html .= '<tr>
        <td class="text-center">' . $no++ . '</td>
        <td>' . htmlspecialchars($tanggal) . '</td>
        <td class="text-center">' . htmlspecialchars($waktu) . '</td>
        <td>' . htmlspecialchars($row['nama_tamu']) . '</td>
        <td>' . htmlspecialchars($row['asal_instansi']) . '</td>
        <td>' . htmlspecialchars($row['jabatan']) . '</td>
        <td>' . htmlspecialchars($row['no_telepon']) . '</td>
        <td>' . htmlspecialchars($row['email_tamu']) . '</td>
        <td>' . htmlspecialchars($row['bertemu_dengan']) . '</td>
        <td>' . htmlspecialchars($row['keperluan']) . '</td>
        <td class="' . $statusClass . '">' . htmlspecialchars($row['status_keluar']) . '</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

try {
    $mpdf = new Mpdf([
        'format' => 'A4-L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 12,
        'margin_bottom' => 12
    ]);
    $mpdf->WriteHTML($html);

    $filename = 'daftar_tamu_' . date('Ymd_His') . '.pdf';
    $mpdf->Output($filename, 'D');
} catch (\Throwable $e) {
    error_log("Gagal membuat PDF: " . $e->getMessage());
    exit("Gagal membuat file PDF. Silakan coba lagi.");
}
?>
