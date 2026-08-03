<?php
/**
 * File untuk Generate Password Hash
 *
 * Cara Penggunaan:
 * 1. Ganti nilai variabel $passwordPilihanAnda dengan password asli yang Anda inginkan.
 * 2. Simpan file ini di root direktori proyek Anda (misal: htdocs/bukutamu2/generate_hash.php).
 * 3. Akses file ini melalui browser (misal: http://localhost/bukutamu2/generate_hash.php).
 * 4. Salin HASH yang ditampilkan.
 * 5. Masukkan HASH tersebut ke kolom 'password_hash' di tabel 'tb_admin' untuk user yang sesuai melalui phpMyAdmin.
 * 6. Setelah selesai, disarankan untuk menghapus file ini dari server Anda.
 */

// Langkah 1: GANTI PASSWORD DI BAWAH INI DENGAN PASSWORD ASLI YANG ANDA INGINKAN
$passwordPilihanAnda = 'coba123lagi'; // CONTOH: 'passbaru789' atau 'RahasiaSuperAmanMiliku!'

// Pastikan Anda benar-benar mengganti 'PASSWORD_BARU_ANDA_DISINI' sebelum menjalankan skrip ini.

// Membuat hash dari password
$hashedPassword = password_hash($passwordPilihanAnda, PASSWORD_DEFAULT);

// Menampilkan hasilnya
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Password Hash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #5a5a5a; }
        p { line-height: 1.6; }
        strong { color: #000; }
        textarea { width: 98%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 1em; }
        .warning { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Generate Admin Password Hash</h1>
        <hr>
        <p><strong>Password Asli yang Anda set di skrip ini (JANGAN BAGIKAN INI):</strong><br>
           <code><?php echo htmlspecialchars($passwordPilihanAnda); ?></code></p>
        
        <?php if ($passwordPilihanAnda === 'PASSWORD_BARU_ANDA_DISINI'): ?>
            <p class="warning">PERINGATAN: Anda belum mengganti nilai default '$passwordPilihanAnda'. Harap edit file ini dan ganti dengan password yang Anda inginkan sebelum menggunakan hash di bawah ini!</p>
        <?php endif; ?>

        <p><strong>Password Hash (Salin seluruh teks di bawah ini dan simpan ke kolom <code>password_hash</code> di database <code>tb_admin</code>):</strong></p>
        <textarea rows="4" readonly onclick="this.select();"><?php echo htmlspecialchars($hashedPassword); ?></textarea>
        
        <hr>
        <p><strong>Langkah Selanjutnya:</strong></p>
        <ol>
            <li>Salin seluruh isi dari kotak teks di atas.</li>
            <li>Buka phpMyAdmin, pilih database Anda, lalu tabel <code>tb_admin</code>.</li>
            <li>Edit baris user admin yang ingin Anda ubah passwordnya (atau sisipkan user baru).</li>
            <li>Tempel (paste) hash ini ke kolom <code>password_hash</code>.</li>
            <li>Simpan perubahan di phpMyAdmin.</li>
            <li>Coba login menggunakan username yang sesuai dan password asli (<code><?php echo htmlspecialchars($passwordPilihanAnda); ?></code>).</li>
            <li><strong class="warning">Setelah selesai, sangat disarankan untuk menghapus file <code>generate_hash.php</code> ini dari server Anda demi keamanan.</strong></li>
        </ol>
    </div>
</body>
</html>