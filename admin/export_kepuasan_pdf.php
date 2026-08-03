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

// Ambil data survei
$sql_export = "SELECT 
                    id_kepuasan,
                    nama_responden,
                    tanggal_survei,
                    waktu_survei,
                    nilai_pelayanan,
                    nilai_fasilitas,
                    nilai_keramahan,
                    nilai_kecepatan,
                    saran_masukan
               FROM tb_kepuasan
               ORDER BY tanggal_survei DESC, waktu_survei DESC";

$result_export = $koneksi->query($sql_export);
if ($result_export === false) {
    error_log("Gagal query ekspor data kepuasan (PDF): " . $koneksi->error);
    exit("Terjadi kesalahan saat mengambil data survei.");
}

$rows = [];
while ($row = $result_export->fetch_assoc()) {
    $rows[] = $row;
}
$result_export->free();
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $koneksi->close();
}

$export_time = date('d-m-Y H:i:s');

$html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ekspor PDF - Indeks Kepuasan</title>
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
    </style>
</head>
<body>
    <div class="export-header">
        <div class="export-title">Indeks Kepuasan</div>
        <div class="export-subtitle">Diekspor pada: ' . htmlspecialchars($export_time) . '</div>
    </div>
    <div class="table-title">Tabel Indeks Kepuasan</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">No</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Tanggal</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Waktu</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Responden</th>
                <th class="text-center" style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Pelayanan</th>
                <th class="text-center" style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Fasilitas</th>
                <th class="text-center" style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Keramahan</th>
                <th class="text-center" style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Kecepatan</th>
                <th style="background:#0E5CAD;color:#ffffff;border:1px solid #0E5CAD;font-weight:600;padding:6px;text-align:left;white-space:nowrap;">Saran</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
foreach ($rows as $row) {
    $tanggal = $row['tanggal_survei'] ? date('d-m-Y', strtotime($row['tanggal_survei'])) : '';
    $waktu = $row['waktu_survei'] ? substr($row['waktu_survei'], 0, 5) : '';
    $responden = $row['nama_responden'] ?: 'Anonim';

    $html .= '<tr>
        <td class="text-center">' . $no++ . '</td>
        <td>' . htmlspecialchars($tanggal) . '</td>
        <td class="text-center">' . htmlspecialchars($waktu) . '</td>
        <td>' . htmlspecialchars($responden) . '</td>
        <td class="text-center">' . htmlspecialchars($row['nilai_pelayanan']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['nilai_fasilitas']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['nilai_keramahan']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['nilai_kecepatan']) . '</td>
        <td>' . htmlspecialchars($row['saran_masukan']) . '</td>
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

    $filename = 'indeks_kepuasan_' . date('Ymd_His') . '.pdf';
    $mpdf->Output($filename, 'D');
} catch (\Throwable $e) {
    error_log("Gagal membuat PDF kepuasan: " . $e->getMessage());
    exit("Gagal membuat file PDF. Silakan coba lagi.");
}
?>
