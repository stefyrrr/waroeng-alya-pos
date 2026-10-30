<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once '../includes/db.php';
// BATALKAN STRUK (saat klik tombol ✖)
if (isset($_GET['batal_struk'])) {
    unset($_SESSION['struk_draft']);
    header("Location: kasir.php");
    exit;
}

// SIMPAN TRANSAKSI SAAT KONFIRMASI CETAK STRUK
if (isset($_POST['konfirmasi_struk']) && isset($_SESSION['struk_draft'])) {
    $s = $_SESSION['struk_draft'];
    $kode = $s['kode'];
    $tanggal = $s['tanggal_db'];
    $kasir = $s['kasir'];
    $kasir_id = $s['kasir_id'];
    $metode = $s['metode'];
    $total = $s['total'];
    $items = $s['items'];

    // Simpan ke transaksi
    $conn->query("INSERT INTO transaksi (kode_transaksi, tanggal, total, metode_pembayaran, kasir, kasir_id, status)
                  VALUES ('$kode', '$tanggal', $total, '$metode', '$kasir', $kasir_id, 'Selesai')");

    // Simpan detail & update stok
    foreach ($items as $item) {
        $id_produk = $item['id'];
        $jumlah = $item['jumlah'];
        $harga = $item['harga'];
        $subtotal = $harga * $jumlah;

        $stok_sekarang = $conn->query("SELECT stok FROM produk WHERE id = $id_produk")->fetch_assoc()['stok'];
        if ($stok_sekarang < $jumlah) {
            echo "<script>alert('Stok untuk produk {$item['nama']} tidak mencukupi!'); location.href='kasir.php';</script>";
            exit;
        }

        $conn->query("INSERT INTO transaksi_detail (kode_transaksi, id_produk, jumlah, harga_saat_transaksi, subtotal)
                      VALUES ('$kode', $id_produk, $jumlah, $harga, $subtotal)");
        $conn->query("UPDATE produk SET stok = stok - $jumlah WHERE id = $id_produk");
    }

    unset($_SESSION['cart']);
    unset($_SESSION['struk_draft']);
    echo "<script>
            window.onload = function() {
              window.print();
              window.location.href = 'kasir.php';
            };
          </script>";

}
// --- FILTER KATEGORI (jika kamu ingin aktifkan tombol filter per kategori)
$kategori = $_GET['kategori'] ?? '';
// --- AMBIL PRODUK DARI DATABASE
$where = ($kategori && $kategori !== 'Semua') ? "WHERE kategori = '$kategori'" : "WHERE status = 1";
$produkQuery = $conn->query("SELECT * FROM produk $where ORDER BY nama ASC");
// --- TAMBAH KE KERANJANG
if (isset($_POST['tambah_keranjang'])) {
    $id_produk = $_POST['id_produk'];
    $produk = $conn->query("SELECT * FROM produk WHERE produk_id = $id_produk")->fetch_assoc();
    if ($produk && $produk['stok'] > 0) {
        $item = [
            'id' => $produk['produk_id'],
            'nama' => $produk['nama'],
            'harga' => $produk['harga'],
            'jumlah' => 1
        ];
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        $found = false;
        foreach ($_SESSION['cart'] as &$c) {
            if ($c['id'] == $item['id']) {
                $c['jumlah'] += 1;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = $item;
        }
    }
}
// --- BERSIHKAN KERANJANG
if (isset($_POST['bersihkan'])) {
    unset($_SESSION['cart']);
}
// --- BAYAR (simpan draft saja)
if (isset($_POST['bayar']) && !empty($_SESSION['cart'])) {
    $kode = 'TRX' . time();
    $tanggal = date('Y-m-d H:i:s');
    $kasir = $_SESSION['username'];
    $kasir_id = $_SESSION['user_id'] ?? 0;
    $metode = $_POST['metode_pembayaran'] ?? 'Tunai';
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['harga'] * $item['jumlah'];
    }
    $_SESSION['struk_draft'] = [
        'kode' => $kode,
        'tanggal' => date('d/m/Y, H.i.s'),
        'tanggal_db' => $tanggal,
        'kasir' => $kasir,
        'kasir_id' => $kasir_id,
        'metode' => $metode,
        'items' => $_SESSION['cart'],
        'total' => $total
    ];
    echo "<script>location.href='kasir.php?struk=1';</script>";
    exit;
}

// --- RINGKASAN HARI INI
$today = date('Y-m-d');
$summary = $conn->query("SELECT COUNT(*) AS total_trx, SUM(total) AS total_jual FROM transaksi WHERE DATE(tanggal) = '$today'");
$data = $summary->fetch_assoc();
$total_trx = $data['total_trx'] ?? 0;
$total_jual = $data['total_jual'] ?? 0;
// Tambah jumlah produk di cart
if (isset($_POST['tambah'])) {
  $i = $_POST['index'];
  $_SESSION['cart'][$i]['jumlah']++;
}

// Kurangi jumlah produk di cart
if (isset($_POST['kurangi'])) {
  $i = $_POST['index'];
  $_SESSION['cart'][$i]['jumlah']--;
  if ($_SESSION['cart'][$i]['jumlah'] <= 0) {
    unset($_SESSION['cart'][$i]);
    $_SESSION['cart'] = array_values($_SESSION['cart']); // reset index
  }
}

// Hapus item dari cart
if (isset($_POST['hapus'])) {
  $i = $_POST['index'];
  unset($_SESSION['cart'][$i]);
  $_SESSION['cart'] = array_values($_SESSION['cart']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Kasir - Waroeng Alya</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css?v=12" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/heanav.css?v=123">
  <link rel="stylesheet" href="assets/css/kasir.css?v=139">
</head>
<body>
  <?php if (isset($_GET['struk']) && isset($_SESSION['struk_draft'])): $s = $_SESSION['struk_draft']; ?>
  <div class="modal" style="display:block;" id="modalStruk">
    <div class="modal-content">
      <i class="bx bx-x close-btn" onclick="tutupStruk()"></i>
      <h3 style="color:#7e22ce;"><i class="bx bx-receipt"></i> Struk Pembayaran</h3>
      <div style="text-align:center;">
        <h4 style="margin:0; color:#6b21a8;">WAROENG ALYA</h4>
        <p style="font-size:13px; margin:4px 0 10px;">Kantin Universitas Negeri Jakarta</p>
      </div>
      <hr>
      <div style="font-size:14px; margin-bottom:12px; line-height:1.6;">
        <div style="display: flex; justify-content: space-between;">
          <span>No. Transaksi:</span>
          <span><strong>#<?= $s['kode'] ?></strong></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span>Tanggal:</span>
          <span><?= $s['tanggal'] ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span>Kasir:</span>
          <span><?= $s['kasir'] ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span>Pembayaran:</span>
          <span><?= $s['metode'] ?></span>
        </div>
        <?php if ($s['metode'] === 'QRIS'): ?>
          <div style="text-align: center; margin-top: 14px;">
            <p style="font-size:13px; margin-bottom: 8px;">Silakan scan QR berikut:</p>
            <img src="assets/img/qris.png" alt="QRIS Code" style="max-width:90px; width:100%; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
          </div>
        <?php endif; ?>
      </div>
      <hr>
      <div style="margin:10px 0; font-size: 12px">
        <?php foreach ($s['items'] as $it): ?>
          <div>
            <?= $it['nama'] ?> <span style="float:right">Rp <?= number_format($it['harga'] * $it['jumlah'], 0, ',', '.') ?></span><br>
            <small>Rp <?= number_format($it['harga'], 0, ',', '.') ?> × <?= $it['jumlah'] ?></small>
          </div>
        <?php endforeach; ?>
      </div>
      <hr>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px;">
        <span style="font-weight:bold; color:#7e22ce;">TOTAL:</span>
        <span style="font-weight:bold; color:#7e22ce;">Rp <?= number_format($s['total'], 0, ',', '.') ?></span>
      </div>
      <p style="font-size:13px; text-align:center;">Terima kasih atas kunjungan Anda!<br>Selamat menikmati hidangan Waroeng Alya</p>
      <a href="cetak_struk.php" target="_blank" class="btn-pay" style="margin-top:14px; display:inline-block;">Cetak Struk (PDF)</a>
    </div>
  </div>
  <?php endif; ?>

  <div class="header">
    <div class="header-left">
      <i class='bx bxs-store' style="font-size: 26px;"></i>
      <span class="brand-name">Waroeng Alya</span>
    </div>
    <div class="header-right">
      <span class="kasir-name">Kasir: <?php echo $_SESSION['username']; ?></span>
      <a href="../logout.php" class="logout">Keluar</a>
    </div>
  </div>

  <nav class="navbar-nav">
    <a href="kasir.php" class="active"><i class='bx bx-cart'></i> Kasir</a>
    <a href="laporan.php"><i class='bx bx-line-chart'></i> Laporan Penjualan</a>
  </nav>

  <div class="container">
  <!-- KIRI: MENU -->
  <div class="modal" id="modalPembayaran">
    <div class="modal-content">
      <i class="bx bx-x close-btn" onclick="tutupModal()"></i>
      <h3 style="color:#7e22ce; margin-bottom: 8px;">Proses Pembayaran</h3>
      <p style="margin-bottom: 20px;">Total: <strong>Rp <span id="totalBayarText">0</span></strong></p>
      <form method="post">
        <input type="hidden" name="metode_pembayaran" id="metodePembayaran">
        <button type="submit" name="bayar" class="btn-pilih tunai" onclick="pilihMetode('Tunai')">Tunai</button>
        <button type="submit" name="bayar" class="btn-pilih qris" onclick="pilihMetode('QRIS')">QRIS</button>
      </form>
    </div>
  </div>
  <div class="menu-section">
      <div class="menu-content">
        <h2>Menu Waroeng Alya</h2>
        <div class="menu-header">
        <form method="get" class="menu-filter-form">
          <input type="text" name="search" placeholder="Cari menu...">
            <button type="submit" name="kategori" value="Semua">Semua</button>
            <button type="submit" name="kategori" value="Ayam">Ayam</button>
            <button type="submit" name="kategori" value="Nasi">Nasi</button>
            <button type="submit" name="kategori" value="Cemilan">Cemilan</button>
            <button type="submit" name="kategori" value="Berkuah">Berkuah</button>
        </form>
      </div>

        <div class="menu-grid">
          <?php while ($p = $produkQuery->fetch_assoc()): ?>
            <div class="menu-card">
              <div class="menu-img">
              <?php if (!empty($p['gambar'])): ?>
                <img src="../img/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" class="menu-img-tag">
              <?php else: ?>
                <i class='bx bx-image' style="font-size: 48px; opacity: 0.4; margin-bottom: 10px;"></i>
              <?php endif; ?>
              </div>
              <h4><?= $p['nama'] ?></h4>
              <div class="price">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
              <div class="stok-action">
              <div class="stok-badge">Stok: <?= max(0, $p['stok']) ?></div>
              <form method="post">
                <input type="hidden" name="id_produk" value="<?= $p['produk_id'] ?>">
                <button type="submit" name="tambah_keranjang" class="add-btn"><i class="bx bx-plus"></i></button>
              </form>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <!-- KANAN: PESANAN -->
    <div class="order-section">
      <div class="order-box">
        <h2><i class="bx bx-cart"></i> Pesanan (<?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>)</h2>
        <div class="order-list">
            <?php
            $total = 0;
            if (!empty($_SESSION['cart'])):
              foreach ($_SESSION['cart'] as $i => $item):
                $subtotal = $item['harga'] * $item['jumlah'];
                $total += $subtotal;
            ?>
              <div class="cart-item">
                <div class="cart-item-info">
                  <strong style="color:#7e22ce"><?= $item['nama'] ?></strong><br>
                  <small>Rp <?= number_format($item['harga'], 0, ',', '.') ?> × <?= $item['jumlah'] ?></small>
                </div>
                <div class="cart-item-controls">
                  <form method="post" style="display:flex; align-items:center; gap:5px;">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <button type="submit" name="kurangi" class="btn-minus">−</button>
                    <span><?= $item['jumlah'] ?></span>
                    <button type="submit" name="tambah" class="btn-plus">+</button>
                    <button type="submit" name="hapus" class="btn-hapus"><i class='bx bx-trash'></i></button>
                  </form>
                </div>
              </div>
            <?php endforeach; else: ?>
            <p>Belum ada pesanan</p>
          <?php endif; ?>
        </div>
        <hr class="total-divider">
        <div class="order-total">
          <span class="label-total">Total:</span>
          <span class="value-total">Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
        <form method="post" class="btn-group">
          <button type="submit" name="bersihkan" class="btn-clear" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>Bersihkan</button>
          <button type="button" onclick="bukaModalPembayaran()" class="btn-pay" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>Bayar</button>
        </form>
      </div>

      <div class="summary-box">
        <h4>Ringkasan Hari Ini</h4>
        <div class="summary-row">
          <span>Total Transaksi:</span>
          <span><?= $total_trx ?></span>
        </div>
        <div class="summary-row">
          <span>Total Penjualan:</span>
          <span>Rp <?= number_format($total_jual, 0, ',', '.') ?></span>
        </div>
      </div>
    </div>
  </div>
  <script>
  function bukaModalPembayaran() {
    const totalText = document.querySelector('.value-total').innerText.replace(/[^\d]/g, '');
      document.getElementById("totalBayarText").innerText = new Intl.NumberFormat('id-ID').format(totalText);
      document.getElementById("modalPembayaran").style.display = "block";
    }

    function tutupModal() {
      document.getElementById("modalPembayaran").style.display = "none";
    }

    function pilihMetode(metode) {
      document.getElementById("metodePembayaran").value = metode;
    }

    // agar modal bisa ditutup jika klik luar
    window.onclick = function(e) {
      const modal = document.getElementById("modalPembayaran");
      if (e.target == modal) modal.style.display = "none";
    }

    // Jika tombol cetak ditekan, langsung cetak area modal struk
    function printStruk() {
      const struk = document.getElementById('modalStruk').innerHTML;
      const original = document.body.innerHTML;
      document.body.innerHTML = struk;
      window.print();
      document.body.innerHTML = original;
      location.href = 'kasir.php'; // Kembali ke halaman kasir
    }
     function tutupStruk() {
      window.location.href = 'kasir.php'; // kembali tanpa ?struk=1
    }

    // Simpan scroll position sebelum submit form
      document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", () => {
          sessionStorage.setItem("scrollPosition", window.scrollY);
        });
      });

    // Saat halaman selesai diload, scroll langsung ke posisi sebelumnya
      document.addEventListener("DOMContentLoaded", () => {
        const scrollY = sessionStorage.getItem("scrollPosition");
        if (scrollY !== null) {
          window.scrollTo(0, parseInt(scrollY));
          sessionStorage.removeItem("scrollPosition");
        }
      });
  </script>
</body>
</html>