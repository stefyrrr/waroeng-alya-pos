<?php
// admin/dashboard.php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: ../login.php'); // Jika tidak, arahkan ke halaman login
    exit();
}
require_once '../includes/db.php';

$pendapatan = 0;
$totalTransaksi = 0;
$totalProduk = 0;
$stokMenipis = 0;
$transaksi = [];

// Query untuk total pendapatan
$res = $conn->query("SELECT SUM(total) AS pendapatan FROM transaksi WHERE status = 'selesai'");
$pendapatan = $res->fetch_assoc()['pendapatan'] ?? 0;

// Query untuk total transaksi
$res = $conn->query("SELECT COUNT(*) AS total FROM transaksi WHERE status = 'selesai'");
$totalTransaksi = $res->fetch_assoc()['total'] ?? 0;

// Query untuk total produk yang aktif
$res = $conn->query("SELECT COUNT(*) AS total FROM produk WHERE status = 'aktif'");
$totalProduk = $res->fetch_assoc()['total'] ?? 0;

// Query untuk produk yang stoknya kurang dari 10
$res = $conn->query("SELECT COUNT(*) AS total FROM produk WHERE stok < 10");
$stokMenipis = $res->fetch_assoc()['total'] ?? 0;

// Query untuk transaksi terbaru
$res = $conn->query("SELECT t.kode_transaksi, t.tanggal, t.total, t.metode_pembayaran, u.username AS kasir
                     FROM transaksi t
                     JOIN users u ON t.kasir_id = u.user_id
                     WHERE u.role = 'kasir'  -- Menambahkan filter untuk kasir jika diperlukan
                     ORDER BY t.tanggal DESC");

while ($row = $res->fetch_assoc()) {
    $transaksi[] = [
        'id' => $row['kode_transaksi'],
        'waktu' => date('j/n/Y, H.i.s', strtotime($row['tanggal'])),
        'kasir' => $row['kasir'],  // Nama kasir dari tabel users
        'total' => $row['total'],
        'metode' => $row['metode_pembayaran']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Waroeng Alya</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=127">
</head>
<body>
  <div class="header">
    <div class="header-left">
      <i class='bx bxs-store' style="font-size: 26px;"></i>
      <span class="brand-name">Waroeng Alya</span>
    </div>
    <div class="header-right">
      <span class="admin-name">Halo, <?php echo $_SESSION['username']; ?></span>
      <a href="../logout.php" class="logout">Keluar</a>
    </div>
  </div>

  <nav class="navbar-nav">
    <a href="dashboard.php" class="active"><i class='bx bx-bar-chart-alt-2'></i> Dashboard</a>
    <a href="produk.php"><i class='bx bx-restaurant'></i> Produk</a>
    <a href="laporan.php"><i class='bx bx-line-chart'></i> Laporan</a>
    <a href="pengguna.php"><i class='bx bx-user'></i> Pengguna</a>
  </nav>

  <div class="container">
    <div class="cards">
      <div class="card">
        <i class='bx bx-dollar icon-card'></i>
        <p>Total Pendapatan</p>
        <h3>Rp <?= number_format($pendapatan, 0, ',', '.') ?></h3>
        <p>Total keseluruhan</p>
      </div>
      <div class="card">
        <i class='bx bx-cart icon-card'></i>
        <p>Total Transaksi</p>
        <h3><?= $totalTransaksi ?></h3>
        <p>Transaksi tercatat</p>
      </div>
      <div class="card">
        <i class='bx bx-restaurant icon-card'></i>
        <p>Total Produk</p>
        <h3><?= $totalProduk ?></h3>
        <p>Produk aktif</p>
      </div>
      <div class="card">
        <i class='bx bx-trash icon-card'></i>
        <p>Stok Menipis</p>
        <h3><?= $stokMenipis ?></h3>
        <p>Produk stok < 10</p>
      </div>
    </div>

    <div class="list-transaksi">
      <div class="section-title">Transaksi Terbaru</div>
      <div class="trx-scroll">
      <?php foreach ($transaksi as $trx): ?>
        <div class="trx-item">
          <div class="trx-left">
            <span class="id">#<?= $trx['id'] ?></span>
            <div><?= $trx['waktu'] ?><br>Kasir: <?= $trx['kasir'] ?></div>
          </div>
          <div class="trx-right">
            <div class="total">Rp <?= number_format($trx['total'], 0, ',', '.') ?></div>
            <div class="metode"><?= $trx['metode'] ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
