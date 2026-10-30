<?php
session_start();
require_once '../includes/db.php';

// Ambil filter dari URL jika ada
$kategori = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE status = 1";
if ($kategori) {
    $kategori = $conn->real_escape_string($kategori);
    $where .= " AND kategori = '$kategori'";
}
if ($search) {
    $search = $conn->real_escape_string($search);
    $where .= " AND nama LIKE '%$search%'";
}

// Ambil data produk dengan filter yang diterapkan
$result = $conn->query("SELECT produk_id, nama, harga, stok, status, gambar, kategori FROM produk $where ORDER BY nama ASC");
$produk = [];
while ($row = $result->fetch_assoc()) {
    $produk[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Produk - Waroeng Alya</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=124">
  <link rel="stylesheet" href="assets/css/produk.css?v=137">
</head>
<body>
 <div class="header">
    <div class="header-left">
      <i class='bx bxs-store' style="font-size: 26px;"></i>
      <span class="brand-name">Waroeng Alya</span>
    </div>
    <div class="header-right">
      <span class="admin-name">Halo, <?php echo $_SESSION['username']; ?> </span>
      <a href="../logout.php" class="logout">Keluar</a>
    </div>
  </div>

  <nav class="navbar-nav">
    <a href="dashboard.php"><i class='bx bx-bar-chart-alt-2'></i> Dashboard</a>
    <a href="produk.php" class="active"><i class='bx bx-restaurant'></i> Produk</a>
    <a href="laporan.php"><i class='bx bx-line-chart'></i> Laporan</a>
    <a href="pengguna.php"><i class='bx bx-user'></i> Pengguna</a>
  </nav>

  <div class="container">
    <div class="produk-header-bar">
        <div class="produk-header">
            <h1>Manajemen Produk</h1>
            <p>Kelola produk dan stok Waroeng Alya</p>
        </div>
      <button class="tambah-produk"><i class='bx bx-plus'></i> Tambah Produk</button>
    </div>

    <div class="produk-card-wrapper">
      <!-- Modal Tambah Produk -->
      <div class="modal" id="modalProduk">
        <div class="modal-content">
          <span class="close">&times;</span>
          <h2>Tambah Produk Baru</h2>
          <form action="produk_tambah.php" method="post" enctype="multipart/form-data">
            <label>Nama Produk</label>
            <input type="text" name="nama" required>

            <div class="form-row">
              <div>
                <label>Harga</label>
                <input type="number" name="harga" min="0" required>
              </div>
              <div>
                <label>Stok</label>
                <input type="number" name="stok" min="0" required>
              </div>
            </div>

            <label>Kategori</label>
            <select name="kategori" required>
              <option value="Ayam">Ayam</option>
              <option value="Nasi">Nasi</option>
              <option value="Cemilan">Cemilan</option>
              <option value="Minuman">Minuman</option>
              <option value="Berkuah">Berkuah</option>
            </select>

            <!-- Tambahkan input untuk upload gambar -->
            <label>Gambar Produk</label>
            <input type="file" name="gambar" accept="image/*">

            <div class="form-buttons">
              <button type="submit" class="btn-purple">Tambah Produk</button>
              <button type="button" class="btn-cancel" id="closeModal">Batal</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Modal Edit Produk -->
      <div class="modal" id="modalEditProduk">
        <div class="modal-content">
          <span class="close-edit">&times;</span>
          <h2>Edit Produk</h2>
          <form action="produk_edit.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="produk_id" id="edit-id">
            <label>Nama Produk</label>
            <input type="text" name="nama" id="edit-nama" required>

            <div class="form-row">
              <div>
                <label>Harga</label>
                <input type="number" name="harga" id="edit-harga" min="0" required>
              </div>
              <div>
                <label>Stok</label>
                <input type="number" name="stok" id="edit-stok" min="0" required>
              </div>
            </div>

            <label>Kategori</label>
            <select name="kategori" id="edit-kategori" required>
              <option value="Ayam">Ayam</option>
              <option value="Nasi">Nasi</option>
              <option value="Cemilan">Cemilan</option>
              <option value="Minuman">Minuman</option>
              <option value="Berkuah">Berkuah</option>
            </select>
                        <!-- Input untuk upload gambar -->
            <label>Gambar Produk</label>
            <input type="file" name="gambar" accept="image/*">
            <div class="form-buttons">
              <button type="submit" class="btn-purple">Simpan Perubahan</button>
              <button type="button" class="btn-cancel" id="closeEditModal">Batal</button>
            </div>
          </form>
        </div>
      </div>

      <form method="get" class="produk-filter-bar">
        <input type="text" name="search" class="search-box" placeholder="Cari produk..." value="<?= $_GET['search'] ?? '' ?>">
        <select name="kategori" class="filter-kategori" onchange="this.form.submit()">
          <option value="">Semua</option>
          <option value="Ayam" <?= ($_GET['kategori'] ?? '') === 'Ayam' ? 'selected' : '' ?>>Ayam</option>
          <option value="Nasi" <?= ($_GET['kategori'] ?? '') === 'Nasi' ? 'selected' : '' ?>>Nasi</option>
          <option value="Cemilan" <?= ($_GET['kategori'] ?? '') === 'Cemilan' ? 'selected' : '' ?>>Cemilan</option>
          <option value="Minuman" <?= ($_GET['kategori'] ?? '') === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
          <option value="Berkuah" <?= ($_GET['kategori'] ?? '') === 'Berkuah' ? 'selected' : '' ?>>Berkuah</option>
        </select>
      </form>

      <div class="produk-grid">
        <?php foreach ($produk as $p): ?>
          <div class="produk-card">
            <div class="produk-img">
              <?php if (!empty($p['gambar'])): ?>
                <img src="../img/<?= htmlspecialchars($p['gambar']) ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($p['nama']) ?>" class="produk-img-tag">
              <?php else: ?>
                <i class='bx bx-image' style="font-size: 36px; opacity: 0.4;"></i>
              <?php endif; ?>
            </div>
            <div class="produk-info">
              <h3><?= htmlspecialchars($p['nama']) ?></h3>
              <p><?= htmlspecialchars($p['deskripsi'] ?? '-') ?></p>
              <div class="harga">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
              <div class="produk-meta">
                <span class="stok">Stok: <?= max(0, $p['stok']) ?></span>
                <span class="kategori"><?= htmlspecialchars($p['kategori']) ?></span>
              </div>
            </div>
            <div class="produk-actions">
              <button class="edit" 
                data-id="<?= $p['produk_id'] ?>" 
                data-nama="<?= htmlspecialchars($p['nama']) ?>" 
                data-harga="<?= $p['harga'] ?>" 
                data-stok="<?= $p['stok'] ?>" 
                data-kategori="<?= $p['kategori'] ?>">
                <i class="bx bx-edit"></i>
              </button>
              <form method="get" action="produk_hapus.php" onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                <input type="hidden" name="id" value="<?= $p['produk_id'] ?>">
                <button type="submit" class="hapus"><i class="bx bx-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <script>
    const modal = document.getElementById("modalProduk");
    const openBtn = document.querySelector(".tambah-produk");
    const closeBtn = document.getElementById("closeModal");
    const closeX = document.querySelector(".modal .close");

    openBtn.onclick = () => modal.style.display = "block";
    closeBtn.onclick = () => modal.style.display = "none";
    closeX.onclick = () => modal.style.display = "none";
    window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; };

     // Modal Edit Produk
    const modalEdit = document.getElementById("modalEditProduk");
    const closeEdit = document.querySelector(".close-edit");
    const cancelEdit = document.getElementById("closeEditModal");

    document.querySelectorAll(".edit").forEach(btn => {
      btn.onclick = () => {
        // Isi data ke form edit
        document.getElementById("edit-id").value = btn.dataset.id;
        document.getElementById("edit-nama").value = btn.dataset.nama;
        document.getElementById("edit-harga").value = btn.dataset.harga;
        document.getElementById("edit-stok").value = btn.dataset.stok;
        document.getElementById("edit-kategori").value = btn.dataset.kategori;
        modalEdit.style.display = "block";
      };
    });

    closeEdit.onclick = () => modalEdit.style.display = "none";
    cancelEdit.onclick = () => modalEdit.style.display = "none";
    window.onclick = (e) => { if (e.target == modalEdit) modalEdit.style.display = "none"; };
  </script>

</body>
</html>