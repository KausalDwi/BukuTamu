<?php
session_start();

// Panggil file koneksi (sesuaikan path relatif dari folder admin ke folder root)
$koneksiPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'koneksi' . DIRECTORY_SEPARATOR . 'koneksi.php';
if (file_exists($koneksiPath)) {
    require_once $koneksiPath;
} else {
    die("File koneksi tidak ditemukan.");
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password wajib diisi!";
    } else {
        // Query menggunakan kolom username dan password_hash
        $sql = "SELECT * FROM tb_admin WHERE username = ?";
        if ($stmt = $koneksi->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Cek password menggunakan MD5 (sesuai inputan database kita)
                if (md5($password) === $row['password_hash']) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $row['username'];
                    $_SESSION['admin_id'] = $row['id'];
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error_message = "Password yang Anda masukkan salah!";
                }
            } else {
                $error_message = "Username tidak ditemukan!";
            }
            $stmt->close();
        } else {
            $error_message = "Oops! Gagal menyiapkan statement. Silakan coba lagi nanti.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Buku Tamu Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #7993F1 0%, #3D4099 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 1.25rem;
            padding: 2.5rem;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }
        .form-control {
            padding: 0.85rem 1.1rem;
            border-radius: 0.5rem;
        }
        .btn-primary {
            background: #0E5CAD;
            border: none;
            padding: 0.85rem;
            font-weight: 600;
            border-radius: 0.5rem;
        }
        .btn-primary:hover {
            background: #093c78;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex p-3 mb-2">
                <i class="bi bi-shield-lock-fill fs-3"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Admin Login</h3>
            <p class="text-muted small">Masuk untuk mengelola Buku Tamu Digital</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger d-flex align-items-center small py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" name="username" placeholder="Masukkan username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control bg-light border-start-0" name="password" placeholder="Masukkan password" required>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary shadow-sm">Masuk Sekarang <i class="bi bi-arrow-right ms-1"></i></button>
            </div>

            <div class="text-center">
                <a href="../index.php" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Utama</a>
            </div>
        </form>
    </div>
</body>
</html>