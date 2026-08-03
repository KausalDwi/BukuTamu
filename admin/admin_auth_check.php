<?php
// File: admin/admin_auth_check.php

if (session_status() === PHP_SESSION_NONE) { // Mulai sesi hanya jika belum ada yang aktif
    session_start();
}

$timeout_duration = 600; // 10 menit dalam detik (10 * 60)
$logout_reason = '';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Belum login, redirect ke halaman login
    header("Location: login.php");
    exit;
} else {
    // Sudah login, cek timeout karena tidak aktif
    if (isset($_SESSION['admin_last_activity'])) {
        $inactive_time = time() - $_SESSION['admin_last_activity'];
        if ($inactive_time > $timeout_duration) {
            // Waktu tidak aktif sudah melebihi batas
            $logout_reason = '?status=session_expired'; // Tambahkan alasan ke URL login
            session_unset();     // Hapus semua variabel sesi
            session_destroy();   // Hancurkan sesi
            header("Location: login.php" . $logout_reason);
            exit;
        }
    }
    // Jika tidak timeout, update waktu aktivitas terakhir
    $_SESSION['admin_last_activity'] = time();
}

// Untuk "Harus Login Ulang Setelah Close Halaman":
// PHP secara default menggunakan session cookies yang akan hilang saat browser ditutup.
// Pastikan tidak ada pengaturan cookie session yang membuatnya permanen.
// Pengaturan session.cookie_lifetime = 0 di php.ini adalah default yang benar.
// Tidak ada kode tambahan yang biasanya diperlukan di sini untuk itu,
// karena timeout di atas akan menangani tab yang dibiarkan terbuka tanpa aktivitas.
?>