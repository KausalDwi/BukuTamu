<?php
session_start();
require_once __DIR__ . '/../koneksi/koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak. Silakan login terlebih dahulu.");
}

// 1. JIKA ADA ID: CETAK DETAIL SATU TAMU
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM tb_tamu WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        exit("Data tamu tidak ditemukan.");
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak Detail Tamu - <?php echo htmlspecialchars($data['nama_tamu']); ?></title>
        <style>
            body { font-family: 'Arial', sans-serif; padding: 20px; color: #333; }
            .print-container { max-width: 700px; margin: 0 auto; border: 2px solid #0E5CAD; padding: 30px; border-radius: 10px; }
            .header { text-align: center; border-bottom: 2px solid #0E5CAD; padding-bottom: 15px; margin-bottom: 20px; }
            .header h2 { margin: 0; color: #0E5CAD; font-size: 24px; text-transform: uppercase; }
            .header p { margin: 5px 0 0; color: #555; }
            .detail-table { width: 100%; border-collapse: collapse; }
            .detail-table th, .detail-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
            .detail-table th { width: 35%; background-color: #f4f7fb; color: #333; }
            .ttd-box { margin-top: 40px; text-align: right; font-size: 14px; }
            
            /* Sembunyikan elemen yang tidak perlu saat di-print */
            @media print {
                body { padding: 0; }
                .print-container { border: none; padding: 0; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="print-container">
            <div class="header">
                <h2>BUKU TAMU DIGITAL</h2>
                <p>Dinas Komunikasi dan Informatika</p>
            </div>
            
            <h4 style="text-align: center; margin-bottom: 20px;">BUKTI KUNJUNGAN TAMU</h4>
            
            <table class="detail-table">
                <tr><th>ID Kunjungan</th><td><strong>#<?php echo str_pad($data['id'], 4, '0', STR_PAD_LEFT); ?></strong></td></tr>
                <tr><th>Nama Lengkap</th><td><?php echo htmlspecialchars($data['nama_tamu']); ?></td></tr>
                <tr><th>Asal Instansi</th><td><?php echo htmlspecialchars($data['asal_instansi'] ?: 'Umum/Pribadi'); ?></td></tr>
                <tr><th>Jabatan</th><td><?php echo htmlspecialchars($data['jabatan'] ?: '-'); ?></td></tr>
                <tr><th>Waktu Kedatangan</th><td><?php echo date('d M Y', strtotime($data['tanggal_kunjungan'])); ?> (<?php echo substr($data['waktu_masuk'], 0, 5); ?> WIB)</td></tr>
                <tr><th>Bertemu Dengan</th><td><?php echo htmlspecialchars($data['bertemu_dengan']); ?></td></tr>
                <tr><th>Keperluan</th><td><?php echo htmlspecialchars($data['keperluan']); ?></td></tr>
                <tr><th>Status Kunjungan</th><td><?php echo htmlspecialchars($data['status_keluar']); ?></td></tr>
            </table>
            
            <div class="ttd-box">
                <p>Dicetak pada: <?php echo date('d-m-Y H:i'); ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 2. JIKA TIDAK ADA ID: CETAK SEMUA DATA (TABEL)
$sql_export_tamu = "SELECT * FROM tb_tamu ORDER BY tanggal_kunjungan DESC, waktu_masuk DESC";
$result_export = $koneksi->query($sql_export_tamu);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Semua Data Tamu</title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 20px; font-size: 11px; color: #333; }
        h2 { text-align: center; color: #0E5CAD; margin-bottom: 5px; }
        p.subtitle { text-align: center; color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: left; }
        th { background-color: #0E5CAD; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body onload="window.print()">
    <h2>LAPORAN DATA KUNJUNGAN TAMU</h2>
    <p class="subtitle">Waktu Cetak: <?php echo date('d-m-Y H:i:s'); ?></p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Nama Tamu</th>
                <th>Instansi</th>
                <th>Bertemu</th>
                <th>Keperluan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($row = $result_export->fetch_assoc()) {
                $tanggal = date('d-m-Y', strtotime($row['tanggal_kunjungan']));
                $waktu = substr($row['waktu_masuk'], 0, 5);
                echo "<tr>
                    <td>{$no}</td>
                    <td>{$tanggal}</td>
                    <td>{$waktu}</td>
                    <td>" . htmlspecialchars($row['nama_tamu']) . "</td>
                    <td>" . htmlspecialchars($row['asal_instansi']) . "</td>
                    <td>" . htmlspecialchars($row['bertemu_dengan']) . "</td>
                    <td>" . htmlspecialchars($row['keperluan']) . "</td>
                    <td>" . htmlspecialchars($row['status_keluar']) . "</td>
                </tr>";
                $no++;
            }
            ?>
        </tbody>
    </table>
</body>
</html>