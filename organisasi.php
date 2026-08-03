<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Struktur Organisasi | Diskominfo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --bright-gradient-start: rgb(121, 147, 241);
      --bright-gradient-end: rgb(61, 64, 153);
      --primary-color: #0E5CAD;
      --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
      color: #333;
    }

    /* Header Kominfo */
    .header {
      background-color: white;
      padding: 1.5rem 2rem;
      display: flex;
      align-items: center;
      box-shadow: var(--shadow-soft);
    }

    .header img {
      height: 60px;
      margin-right: 1rem;
    }

    .header h4 {
      margin: 0;
      font-weight: bold;
      font-size: 1.2rem;
      color: var(--primary-color);
    }

    .header small {
      color: #555;
    }

    .content {
      max-width: 900px;
      margin: 2rem auto;
      padding: 2rem;
      background-color: white;
      border-radius: 20px;
      box-shadow: var(--shadow-soft);
    }

    .goto-btn {
      display: inline-block;
      margin-top: 1.5rem;
      background: linear-gradient(135deg, var(--bright-gradient-start), var(--bright-gradient-end));
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      transition: background 0.3s;
    }

    .goto-btn:hover {
      opacity: 0.9;
    }

    .struktur-section {
      text-align: center;
      margin-top: 4rem;
    }

    .struktur-section img {
      max-width: 100%;
      border-radius: 15px;
      box-shadow: var(--shadow-soft);
    }
  </style>
</head>
<body>

  <!-- Header Diskominfo -->
  <div class="header">
    <img src="https://th.bing.com/th/id/R.b3c392405f43d7d0f0118df09886323d?rik=N192KsYE7NjJHg&riu=http%3a%2f%2fwww.intellisys.co.id%2fimages%2fclients%2fkomimfo.png&ehk=xM28gnz87ZOpnmM8YyNmoNVXL8cRAP2d0KpqVFVlrq0%3d&risl=&pid=ImgRaw&r=0" alt="Diskominfo Logo">
    <div>
      <h4>Dinas Komunikasi Informatika Statistik dan Persandian</h4>
      <small>Buku Tamu Dinas Komunikasi Informatika Statistik dan Persandian Lahat</small>
    </div>
  </div>

  <!-- Deskripsi Organisasi -->
  <div class="content">
    <h4>Penjelasan Struktur Organisasi</h4>
    <p>
      Struktur organisasi Dinas Komunikasi Informatika Statistik dan Persandian Kabupaten Lahat dirancang untuk mendukung pelaksanaan tugas dan fungsi dinas secara efisien dan efektif. Struktur ini mencakup kepala dinas, sekretariat, dan berbagai bidang yang menangani urusan komunikasi, informatika, statistik, dan persandian. Pembagian ini bertujuan untuk memperjelas peran dan tanggung jawab masing-masing bagian demi tercapainya pelayanan publik yang optimal.
    </p>

    <!-- Tombol ke struktur -->
    <a href="#struktur" class="goto-btn">
      <i class="bi bi-diagram-3-fill me-1"></i> Lihat Struktur Organisasi
    </a>
  </div>

  <!-- Gambar Struktur Organisasi -->
  <div class="struktur-section" id="struktur">
    <h4>Bagan Struktur Organisasi</h4>
    <img src="https://diskominfo.lahatkab.go.id/wp-content/uploads/2025/02/STRUKTUR-ORGANISASI-DISKOMINFOSP-copy-1.jpg" alt="Struktur Organisasi Diskominfo">
  </div>

</body>
</html>
