<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Beranda | Buku Tamu Diskominfo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --bright-gradient-start: rgb(121, 147, 241);
      --bright-gradient-end: rgb(61, 64, 153);
      --bright-gradient-alt-start: #FF7A70;
      --bright-gradient-alt-end: #FD5E53;

      --primary-color: #0E5CAD;
      --accent-color: #FF7A70;

      --neutral-lightest: #F8F9FA;
      --neutral-lighter: #E9ECEF;
      --neutral-light: #DEE2E6;
      --neutral-medium: #CED4DA;
      --neutral-dark: #495057;
      --neutral-darker: #343A40;
      --neutral-darkest: #212529;

      --gradient-main: linear-gradient(135deg, var(--bright-gradient-start) 0%, var(--bright-gradient-end) 100%);
      --gradient-accent: linear-gradient(135deg, var(--bright-gradient-alt-start) 0%, var(--bright-gradient-alt-end) 100%);
      --gradient-border: linear-gradient(90deg, transparent, var(--primary-color), transparent);

      --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.08);
      --shadow-medium: 8px 25px rgba(0, 0, 0, 0.1);

      --rounded-large: 30px;
    }

    body {
      background-color: var(--neutral-lightest);
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

    @keyframes fadeSlideUp {
      0% {
        opacity: 0;
        transform: translateY(30px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .main-card {
      background: var(--gradient-main);
      border-radius: var(--rounded-large);
      padding: 3rem 2rem;
      box-shadow: var(--shadow-medium);
      margin: 3rem auto;
      max-width: 900px;
      text-align: center;
      color: white;
      background-image:
        radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
        var(--gradient-main);
      background-size: 20px 20px, cover;

      /* Tetap beri animasi masuk untuk bagian utama */
      opacity: 0;
      animation: fadeSlideUp 0.8s ease-out forwards;
    }

    .main-card h1 {
      color: white;
      font-size: 2.4rem;
      font-weight: bold;
    }

    .main-card p {
      color: #f1f1f1;
    }

    .menu-buttons {
      margin-top: 2rem;
    }

    .menu-card {
      position: relative;
      background-color: #F8F9FA;
      border-radius: 25px;
      padding: 1.5rem;
      margin: 1rem;
      box-shadow: var(--shadow-soft);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      text-decoration: none;
      color: #333;
      display: block;
      overflow: hidden;
    }

    .menu-card:hover {
      transform: scale(1.05);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .menu-card.clicked {
      transform: scale(0.95);
      box-shadow: inset 0 3px 10px rgba(0, 0, 0, 0.2);
    }

    .btn-icon {
      font-size: 2rem;
      color: var(--primary-color);
    }

    .btn-label {
      display: block;
      margin-top: 0.5rem;
      font-size: 1.1rem;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <img src="https://th.bing.com/th/id/R.b3c392405f43d7d0f0118df09886323d?rik=N192KsYE7NjJHg&riu=http%3a%2f%2fwww.intellisys.co.id%2fimages%2fclients%2fkomimfo.png&ehk=xM28gnz87ZOpnmM8YyNmoNVXL8cRAP2d0KpqVFVlrq0%3d&risl=&pid=ImgRaw&r=0" alt="Diskominfo Logo">
    <div>
      <h4>Dinas Komunikasi Informatika Statistik dan Persandian</h4>
      <small>Buku Tamu Dinas Komunikasi Infromatika Statistik dan Lahat</small>
    </div>
  </div>

  <!-- Main Content Card -->
  <div class="main-card">
    <h1>Dinas Komunikasi Informatika Statistik dan Persandian</h1>
    <p class="lead">
      Dinas Komunikasi dan Informasi Kabupaten Lahat merupakan unsur pelaksana Pemerintah Kabupaten Lahat dan mempunyai tugas pokok melaksanakan tugas pembantuan dibidang Komunikasi dan Informatika, Bidang Persandian dan Bidang Statistik, sesuai dengan Peraturan Bupati Lahat Nomor 11 Tahun 2023 tentang Organisasi dan Tata Kerja Di Lingkungan Dinas Komunikasi, Informatika, Statistik dan Persandian Kabupaten Lahat tentang Nomenklatur, Susunan Organisasi dan Uraian tugas masing-masing Jabatan Struktural dilingkungan Dinas Komunikasi dan Informatika Kabupaten Lahat.
    </p>

    <div class="row justify-content-center menu-buttons">
      <div class="col-md-4">
        <a href="visimisi.php" class="menu-card">
          <i class="bi bi-bullseye btn-icon"></i>
          <span class="btn-label">Visi & Misi</span>
        </a>
      </div>
      <div class="col-md-4">
        <a href="organisasi.php" class="menu-card">
          <i class="bi bi-diagram-3-fill btn-icon"></i>
          <span class="btn-label">Struktur Organisasi</span>
        </a>
      </div>
      <div class="col-md-4">
        <a href="index.php" class="menu-card">
          <i class="bi bi-box-arrow-in-right btn-icon"></i>
          <span class="btn-label">Masuk ke Buku Tamu</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Klik animasi + redirect -->
  <script>
    document.querySelectorAll('.menu-card').forEach(function(card) {
      card.addEventListener('click', function(e) {
        e.preventDefault();
        const target = e.currentTarget;
        target.classList.add('clicked');

        setTimeout(function () {
          window.location.href = target.getAttribute('href');
        }, 200); // delay 200ms sebelum redirect
      });
    });
  </script>

</body>
</html>
