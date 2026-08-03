<?php
declare(strict_types=1);

$koneksiPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi' . DIRECTORY_SEPARATOR . 'koneksi.php';
if (file_exists($koneksiPath)) {
    require_once $koneksiPath;
}

$profile = [
    'nama_perusahaan' => 'Diskominfo',
    'foto' => 'default-logo.png',
    'deskripsi_singkat' => 'Buku Tamu Digital',
    'visi' => '',
    'misi' => ''
];

if (isset($koneksi) && $koneksi instanceof mysqli) {
    $result = $koneksi->query("SELECT * FROM tb_profile LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $profile = array_merge($profile, $result->fetch_assoc());
    }
    $koneksi->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Visi & Misi | <?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Diskominfo') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --primary-color: #0E5CAD;
      --light-bg: #ffffff;
      --card-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      --rounded-large: 30px;
    }

    body {
      background-color: var(--light-bg);
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .header {
      background-color: var(--light-bg);
      padding: 1.5rem 2rem;
      display: flex;
      align-items: center;
      box-shadow: var(--card-shadow);
      gap: 1rem;
      flex-wrap: wrap;
    }

    .header img {
      height: 60px;
      width: 60px;
      object-fit: contain;
    }

    .header h4 {
      margin: 0;
      font-weight: bold;
      font-size: 1.2rem;
      color: var(--primary-color);
    }

    .main-container {
      max-width: 900px;
      margin: 3rem auto;
      padding: 0 1rem;
    }

    .card-box {
      background-color: #ffffff;
      border-radius: var(--rounded-large);
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: var(--card-shadow);
    }

    .card-box h3 {
      color: var(--primary-color);
      font-weight: bold;
    }

    .card-box p {
      font-size: 1.1rem;
      line-height: 1.6;
      margin: 0;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <img src="admin/images/<?= htmlspecialchars($profile['foto'] ?? 'default-logo.png') ?>" alt="Logo">
    <div>
      <h4><?= htmlspecialchars($profile['nama_perusahaan'] ?? 'Diskominfo') ?></h4>
      <small><?= htmlspecialchars($profile['deskripsi_singkat'] ?? 'Buku Tamu Digital') ?></small>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-container">
    <!-- Visi -->
    <div class="card-box">
      <h3>Visi</h3>
      <p><?= nl2br(htmlspecialchars($profile['visi'] ?? '')) ?></p>
    </div>

    <!-- Misi -->
    <div class="card-box">
      <h3>Misi</h3>
      <p><?= nl2br(htmlspecialchars($profile['misi'] ?? '')) ?></p>
    </div>
  </div>

</body>
</html>
