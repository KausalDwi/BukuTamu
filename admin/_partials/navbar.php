<?php
// File: admin/_partials/navbar.php
// Pastikan session sudah dimulai di halaman yang memanggil partial ini
$admin_nama_display = htmlspecialchars($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin'));
?>
<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top" id="topNavbar">
    <div class="container-fluid px-4">
        <!-- Logo atau Brand (Opsional jika ingin ditampilkan di navbar selain sidebar) -->
        <span class="d-md-none fw-bold text-primary fs-5 me-auto">Buku Tamu</span>
        
        <button class="btn btn-link d-md-none me-3 text-dark" type="button" id="sidebarToggleBtn">
            <i class="bi bi-list fs-3"></i>
        </button>

        <!-- Search Bar (Opsional, untuk tampilan profesional) -->
        <div class="d-none d-md-flex align-items-center">
             <span class="text-muted small"><?php echo date('l, d F Y'); ?></span>
        </div>

        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
            <!-- Notifikasi (Dummy) -->
            <li class="nav-item me-3">
                <a class="nav-link text-secondary position-relative" href="#">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-25 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </a>
            </li>
            
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                        <?php echo strtoupper(substr($admin_nama_display, 0, 1)); ?>
                    </div>
                    <span class="fw-medium text-dark d-none d-lg-inline"><?php echo $admin_nama_display; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg animate__animated animate__fadeIn" aria-labelledby="adminDropdown" style="min-width: 200px;">
                    <li class="px-3 py-2 border-bottom">
                         <p class="mb-0 fw-bold text-dark"><?php echo $admin_nama_display; ?></p>
                         <p class="mb-0 small text-muted">Administrator</p>
                    </li>
                    <li><a class="dropdown-item py-2 mt-2" href="profil_saya.php"><i class="bi bi-person-badge me-2 text-primary"></i>Profil Saya</a></li>
                    <li><a class="dropdown-item py-2" href="#"><i class="bi bi-gear me-2 text-secondary"></i>Pengaturan</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<style>
/* Navbar Modern Overrides */
#topNavbar {
    height: 70px; /* Tinggi navbar fixed */
    z-index: 1030; /* Di atas sidebar mobile */
}
@media (min-width: 768px) {
    #topNavbar {
        padding-left: var(--sidebar-width); /* Offset width sidebar */
        transition: padding-left 0.3s ease-in-out;
    }
}
</style>
