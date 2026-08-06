<?php
// File: admin/_partials/sidebar.php
// Menentukan halaman aktif untuk styling link di sidebar
$current_page = basename($_SERVER['PHP_SELF']);
$base_url_admin = "."; // Path relatif ke halaman admin dari dalam folder admin
?>
<div class="sidebar d-flex flex-column" id="adminSidebar">
    <div class="sidebar-brand d-flex align-items-center">
        <a href="<?php echo $base_url_admin; ?>/index.php" class="brand-link d-flex align-items-center text-decoration-none">
            <span class="brand-icon me-2">
                <i class="bi bi-journal-album"></i>
            </span>
            <span class="brand-text">
                <span class="brand-title">Admin Panel</span>
                <small class="brand-subtitle d-block text-uppercase">Buku Tamu</small>
            </span>
        </a>
    </div>

    <div class="sidebar-section-title">Menu</div>
    <ul class="nav nav-pills flex-column gap-1 mb-3">
        <li class="nav-item">
            <a href="<?php echo $base_url_admin; ?>/index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" aria-current="page">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $base_url_admin; ?>/profil_perusahaan.php" class="nav-link <?php echo ($current_page == 'profil_perusahaan.php') ? 'active' : ''; ?>">
                <i class="bi bi-buildings-fill"></i>
                <span>Profil Perusahaan</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $base_url_admin; ?>/data_tamu.php" class="nav-link <?php echo ($current_page == 'data_tamu.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>
                <span>Data Tamu</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="manajemen_pegawai.php">
                <i class="bi bi-person-lines-fill"></i>
                <span>Manajemen Pegawai</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $base_url_admin; ?>/data_kepuasan.php" class="nav-link <?php echo ($current_page == 'data_kepuasan.php') ? 'active' : ''; ?>">
                <i class="bi bi-patch-check-fill"></i>
                <span>Data Kepuasan</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $base_url_admin; ?>/manajemen_admin.php" class="nav-link <?php echo ($current_page == 'manajemen_admin.php' || $current_page == 'tambah_admin.php') ? 'active' : ''; ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Manajemen Admin</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer mt-auto pt-3">
        <a href="../index.php" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right me-2"></i>
            <span>Ke Halaman Utama</span>
        </a>
    </div>
</div>

