<?php
session_start();

// Hancurkan semua variabel session
$_SESSION = array();

// Hancurkan session
if (session_destroy()) {
    // Redirect ke halaman login setelah logout berhasil
    header("Location: login.php?status=logout_success");
    exit;
} else {
    // Jika gagal destroy, bisa tampilkan error atau redirect dengan status gagal
    header("Location: login.php?status=logout_failed");
    exit;
}
?>