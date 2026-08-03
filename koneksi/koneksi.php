<?php
/**
 * File Koneksi Database
 */
/**
 * Load Environment Variables
 */
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$namaHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$namaPengguna = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$kataSandi = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$namaDatabase = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'db_bukutamu_digital');

$koneksi = new mysqli($namaHost, $namaPengguna, $kataSandi, $namaDatabase);

if ($koneksi->connect_error) {
    error_log("Koneksi ke database gagal: " . $koneksi->connect_error);
    die("Maaf, terjadi masalah saat menghubungkan ke database. Silakan coba lagi nanti.");
}

if (!$koneksi->set_charset("utf8mb4")) {
    error_log("Gagal mengatur set karakter koneksi ke utf8mb4: " . $koneksi->error);
}
?>
