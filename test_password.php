<?php
echo "<h1>Tes Fungsi Password PHP</h1>";

// 1. Tentukan password asli yang ingin Anda tes.
//    Anda bisa mengganti ini dengan password yang Anda coba gunakan untuk admin.
//    Misalnya, mari kita gunakan 'coba123lagi'
$passwordAsliSaya = 'coba123lagi';
echo "<p>Password Asli yang Ditetapkan di Skrip: <strong>" . htmlspecialchars($passwordAsliSaya) . "</strong></p>";

// 2. Generate hash untuk password tersebut
$hashYangDihasilkan = password_hash($passwordAsliSaya, PASSWORD_DEFAULT);
echo "<p>Hash yang Dihasilkan oleh password_hash(): <br><code>" . htmlspecialchars($hashYangDihasilkan) . "</code></p>";

// 3. Verifikasi password asli dengan hash yang BARU SAJA dihasilkan
$verifikasiBerhasil = password_verify($passwordAsliSaya, $hashYangDihasilkan);

echo "<p>Hasil dari password_verify() dengan password asli dan hash yang baru saja dibuat di atas: ";
if ($verifikasiBerhasil) {
    echo "<strong style='color:green;'>BERHASIL (TRUE) - Fungsi password bekerja dengan benar!</strong></p>";
    echo "<p>Ini berarti jika Anda menyimpan hash di atas ke database dan menggunakan password asli '" . htmlspecialchars($passwordAsliSaya) . "' saat login, seharusnya berhasil.</p>";
} else {
    echo "<strong style='color:red;'>GAGAL (FALSE) - Ada masalah fundamental dengan fungsi password di PHP Anda.</strong></p>";
    echo "<p>Ini sangat jarang terjadi. Pastikan versi PHP Anda mendukung fungsi ini (PHP 5.5+).</p>";
}

echo "<hr>";
echo "<h2>Tes dengan Hash dari Database Anda Saat Ini</h2>";
// Ambil hash yang ADA DI DATABASE Anda untuk user 'admin' dari screenshot terakhir Anda
// $2y$10$bK/r.FvKgkQxWJrMuTH.UeXpYbV.n.jUu/49yIq7e7YgBQTWnLAzC (ini untuk password 'admin123')
$hashDariDatabaseAnda = '$2y$10$bK/r.FvKgkQxWJrMuTH.UeXpYbV.n.jUu/49yIq7e7YgBQTWnLAzC';
$passwordYangSeharusnyaCocokDenganHashDiAtas = 'admin123';

echo "<p>Mencoba memverifikasi password: <strong>'" . htmlspecialchars($passwordYangSeharusnyaCocokDenganHashDiAtas) . "'</strong></p>";
echo "<p>Dengan HASH yang ada di database Anda saat ini (untuk user 'admin'): <br><code>" . htmlspecialchars($hashDariDatabaseAnda) . "</code></p>";

$verifikasiDenganHashDatabase = password_verify($passwordYangSeharusnyaCocokDenganHashDiAtas, $hashDariDatabaseAnda);

echo "<p>Hasil dari password_verify() dengan password '" . htmlspecialchars($passwordYangSeharusnyaCocokDenganHashDiAtas) . "' dan HASH DARI DATABASE ANDA: ";
if ($verifikasiDenganHashDatabase) {
    echo "<strong style='color:green;'>BERHASIL (TRUE)</strong></p>";
    echo "<p>Ini berarti jika Anda login dengan username 'admin' dan password '" . htmlspecialchars($passwordYangSeharusnyaCocokDenganHashDiAtas) . "', seharusnya BERHASIL.</p>";
    echo "<p>Jika tetap gagal di form login, masalahnya mungkin ada pada: <br>
          - Bagaimana password diambil dari form (mungkin ada spasi, dll, meskipun sudah ada trim()). <br>
          - Bagaimana hash diambil dari database di skrip login.php ($db_password_hash). <br>
          - Kesalahan ketik password di form login.
         </p>";
} else {
    echo "<strong style='color:red;'>GAGAL (FALSE)</strong></p>";
    echo "<p>Ini ANEH jika hash dan passwordnya benar. Ini bisa berarti hash di database Anda mungkin tidak persis sama (misalnya ada karakter tersembunyi) atau ada masalah yang sangat langka dengan konfigurasi PHP Anda.</p>";
}

echo "<hr>";
echo "<p><strong>Versi PHP Anda:</strong> " . phpversion() . "</p>";
if (function_exists('password_hash') && function_exists('password_verify')) {
    echo "<p style='color:green;'>Fungsi password_hash() dan password_verify() TERSEDIA di versi PHP Anda.</p>";
} else {
    echo "<p style='color:red;'>Fungsi password_hash() dan/atau password_verify() TIDAK TERSEDIA di versi PHP Anda. Anda memerlukan PHP 5.5 atau lebih baru.</p>";
}
?>