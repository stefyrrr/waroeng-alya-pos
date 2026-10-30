<?php
session_start();
require_once '../includes/db.php';

// Ambil filter jenis dan tanggal
$jenis = $_GET['jenis'] ?? 'rentang';
$tgl_awal = $_GET['tanggal_awal'] ?? '';
$tgl_akhir = $_GET['tanggal_akhir'] ?? '';

$where = '';
if ($tgl_awal && $tgl_akhir) {
    switch ($jenis) {
        case 'per_minggu':
            $where = "WHERE YEARWEEK(tanggal, 1) BETWEEN YEARWEEK('$tgl_awal', 1) AND YEARWEEK('$tgl_akhir', 1)";
            break;
        case 'per_bulan':
            $where = "WHERE DATE_FORMAT(tanggal, '%Y-%m') BETWEEN DATE_FORMAT('$tgl_awal', '%Y-%m') AND DATE_FORMAT('$tgl_akhir', '%Y-%m')";
            break;
        case 'per_tahun':
            $where = "WHERE YEAR(tanggal) BETWEEN YEAR('$tgl_awal') AND YEAR('$tgl_akhir')";
            break;
        default:
            $where = "WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'";
            break;
    }
} else {
    $where = ''; // jika tanggal kosong, tampilkan semua
}

// Statistik
$pendapatanQ = $conn->query("SELECT SUM(total) AS total FROM transaksi $where");
$pendapatan = $pendapatanQ->fetch_assoc()['total'] ?? 0;

$jumlahQ = $conn->query("SELECT COUNT(*) AS total FROM transaksi $where");
$total_transaksi = $jumlahQ->fetch_assoc()['total'] ?? 0;

$rata_rata = $total_transaksi > 0 ? ($pendapatan / $total_transaksi) : 0;

$produkTerlarisQ = $conn->query("
  SELECT p.nama, SUM(td.jumlah) AS total_terjual, MAX(td.harga_saat_transaksi) AS harga
  FROM transaksi_detail td
  JOIN produk p ON td.id_produk = p.produk_id
  JOIN transaksi t ON td.transaksi_id = t.transaksi_id
  $where
  GROUP BY td.id_produk
  ORDER BY total_terjual DESC
  LIMIT 5
");

$metodeQ = $conn->query("
  SELECT metode_pembayaran, COUNT(*) AS jumlah, SUM(total) AS total
  FROM transaksi
  $where
  GROUP BY metode_pembayaran
");

// Ambil transaksi
$queryTransaksi = $conn->query("SELECT * FROM transaksi $where ORDER BY tanggal DESC");
$transaksi = [];
while ($row = $queryTransaksi->fetch_assoc()) {
    $kode = $row['kode_transaksi'];
    $detail = $conn->query("
        SELECT td.jumlah, td.harga_saat_transaksi, p.nama
        FROM transaksi_detail td
        JOIN produk p ON td.id_produk = p.produk_id
        JOIN transaksi t ON td.transaksi_id = t.transaksi_id
        WHERE t.kode_transaksi = '$kode'
    ");
    $items = [];
    while ($d = $detail->fetch_assoc()) {
        $items[] = [
            'nama' => $d['nama'],
            'jumlah' => $d['jumlah'],
            'harga' => $d['harga_saat_transaksi']
        ];
    }

    $transaksi[] = [
        'id' => $row['kode_transaksi'],
        'tanggal' => date('d/m/Y, H:i:s', strtotime($row['tanggal'])),
        'kasir' => $row['kasir'],
        'items' => $items,
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
  <title>Laporan - Waroeng Alya</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=124">
  <link rel="stylesheet" href="assets/css/transaksi.css?v=134">
  <?php
  $pendapatanQ = $conn->query("SELECT SUM(total) AS total FROM transaksi");
  $pendapatan = $pendapatanQ->fetch_assoc()['total'] ?? 0;

  $jumlahQ = $conn->query("SELECT COUNT(*) AS total FROM transaksi");
  $total_transaksi = $jumlahQ->fetch_assoc()['total'] ?? 0;

  $rata_rata = $total_transaksi > 0 ? ($pendapatan / $total_transaksi) : 0;
  $produkTerlarisQ = $conn->query("
    SELECT p.nama, SUM(td.jumlah) AS total_terjual, MAX(td.harga_saat_transaksi) AS harga
    FROM transaksi_detail td
    JOIN produk p ON td.id_produk = p.produk_id
    JOIN transaksi t ON td.transaksi_id = t.transaksi_id
    GROUP BY td.id_produk
    ORDER BY total_terjual DESC
    LIMIT 5
  ");

  $metodeQ = $conn->query("
    SELECT metode_pembayaran, COUNT(*) AS jumlah, SUM(total) AS total
    FROM transaksi
    GROUP BY metode_pembayaran
  ");
  ?>
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
    <a href="dashboard.php"><i class='bx bx-bar-chart-alt-2'></i> Dashboard</a>
    <a href="produk.php"><i class='bx bx-restaurant'></i> Produk</a>
    <a href="laporan.php" class="active"><i class='bx bx-line-chart'></i> Laporan</a>
    <a href="pengguna.php"><i class='bx bx-user'></i> Pengguna</a>
  </nav>
        <h1>Laporan penjualan</h1>
         <p>Analisis dan laporan penjualan Waroeng Alya</p>
    <!-- Filter Laporan -->
<div class="card filter-card">
  <h2><i class='bx bx-filter'></i> Filter Laporan</h2>
  <form method="GET" class="form-grid">
    <div class="form-group">
      <label for="jenis">Jenis Laporan</label>
      <select name="jenis" id="jenis">
        <option value="per_minggu" <?= ($jenis === 'per_minggu') ? 'selected' : '' ?>>Per Minggu</option>
        <option value="per_bulan" <?= ($jenis === 'per_bulan') ? 'selected' : '' ?>>Per Bulan</option>
        <option value="per_tahun" <?= ($jenis === 'per_tahun') ? 'selected' : '' ?>>Per Tahun</option>
      </select>
    </div>
    <div class="form-group">
      <label for="tanggal_awal">Tanggal Mulai</label>
      <input type="date" name="tanggal_awal" id="tanggal_awal" value="<?= $tgl_awal ?>">
    </div>
    <div class="form-group">
      <label for="tanggal_akhir">Tanggal Selesai</label>
      <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?= $tgl_akhir ?>">
    </div>
    <div class="form-group">
      <label>&nbsp;</label>
      <button type="button" onclick="exportLaporan()" class="btn-export"><i class='bx bx-download'></i> Export Excel</button>
    </div>
  </form>
</div>

<!-- Card Statistik -->
<div class="stat-row">
  <div class="stat-card">
    <div class="stat-title">Total Pendapatan</div>
    <div class="stat-value">Rp <?= number_format($pendapatan, 0, ',', '.') ?></div>
    <div class="stat-sub">Periode yang dipilih</div>
  </div>
  <div class="stat-card">
    <div class="stat-title">Total Transaksi</div>
    <div class="stat-value"><?= $total_transaksi ?></div>
    <div class="stat-sub">Transaksi tercatat</div>
  </div>
  <div class="stat-card">
    <div class="stat-title">Rata-rata Transaksi</div>
    <div class="stat-value">Rp <?= number_format($rata_rata, 0, ',', '.') ?></div>
    <div class="stat-sub">Per transaksi</div>
  </div>
</div>

   <div class="row-container">
    <div class="box">
      <h3>Produk Terlaris</h3>
      <p>5 produk dengan penjualan tertinggi</p>
      <?php while($p = $produkTerlarisQ->fetch_assoc()): ?>
        <div class="produk-item">
          <div class="produk-info">
            <div class="produk-nama"><strong><?= $p['nama'] ?></strong></div>
            <div class="produk-sub"> <?= $p['total_terjual'] ?> terjual</div>
          </div>
          <div class="produk-harga">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
        </div>
      <?php endwhile; ?>
    </div>

    <div class="box">
      <h3>Metode Pembayaran</h3>
      <p>Statistik metode pembayaran</p>
      <?php while($m = $metodeQ->fetch_assoc()): ?>
        <div class="produk-item">
          <div class="produk-info">
            <div class="produk-nama"><strong><?= $m['metode_pembayaran'] ?></strong></div>
            <div class="produk-sub"><?= $m['jumlah'] ?> transaksi</div>
          </div>
          <div class="produk-harga">Rp <?= number_format($m['total'], 0, ',', '.') ?></div>
        </div>
      <?php endwhile; ?>
    </div>
   </div>

    <div class="container">
        <h2>Daftar Transaksi</h2>
        <div class="transaksi-scroll-wrapper">
        <?php foreach ($transaksi as $trx): ?>
            <div class="transaksi-card">
                <div style="display: flex; justify-content: space-between;">
                    <h2>#<?= $trx['id'] ?></h2>
                    <span class="metode"><?= $trx['metode'] ?></span>
                </div>
                <small><?= $trx['tanggal'] ?> <br>Kasir: <?= $trx['kasir'] ?></small>
                <div class="items">
                <?php foreach ($trx['items'] as $item): ?>
                    <div class="item-row">
                    <div class="item-nama"><?= $item['nama'] ?> x<?= $item['jumlah'] ?></div>
                    <div class="item-harga">Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
                <hr>
                <div class="total-row">
                    <strong>Total:</strong>
                    <strong style="color: green;">Rp <?= number_format($trx['total'], 0, ',', '.') ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <script>
    function exportLaporan() {
      const jenis = document.getElementById('jenis').value;
      const awal = document.getElementById('tanggal_awal').value;
      const akhir = document.getElementById('tanggal_akhir').value;

      const url = `export_laporan.php?jenis=${jenis}&tanggal_awal=${awal}&tanggal_akhir=${akhir}`;
      window.open(url, '_blank');
    }
    </script>
 </body>
</html>
