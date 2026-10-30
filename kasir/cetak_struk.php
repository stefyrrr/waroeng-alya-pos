<?php
require_once '../vendor/autoload.php';
require_once '../includes/db.php';
use Dompdf\Dompdf;

session_start();
// Cek apakah sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['struk_draft'])) {
    die("Struk tidak tersedia.");
}

$s = $_SESSION['struk_draft'];

// Ambil info kasir dari session login
$kasirId   = $_SESSION['user_id'] ?? 0;    // default 0 kalau belum ada
$kasirNama = $_SESSION['username'] ?? 'Unknown';

// Simpan transaksi ke database jika belum ada
$cek = $conn->query("SELECT 1 FROM transaksi WHERE kode_transaksi = '{$s['kode']}'");
if ($cek->num_rows === 0) {
    // Buat query insert transaksi
    $sql = "INSERT INTO transaksi 
            (kode_transaksi, tanggal, total, metode_pembayaran, kasir, kasir_id, status)
            VALUES ('{$s['kode']}', '{$s['tanggal_db']}', {$s['total']}, '{$s['metode']}', '$kasirNama', $kasirId, 'selesai')";
    
    // Debug kalau error
    if (!$conn->query($sql)) {
        die("Error insert transaksi: " . $conn->error . "<br>SQL: " . $sql);
    }

    // Ambil transaksi_id baru
    $trxId = $conn->insert_id;

    // Simpan ke tabel transaksi_detail dan update stok
    foreach ($s['items'] as $item) {
        $id_produk = $item['id']; // harus sesuai dengan produk.produk_id
        $jumlah    = $item['jumlah'];
        $harga     = $item['harga'];
        $subtotal  = $harga * $jumlah;

        $stokQ = $conn->query("SELECT stok FROM produk WHERE produk_id = $id_produk");
        if ($stokQ && $stokQ->num_rows > 0) {
            $stok_sekarang = $stokQ->fetch_assoc()['stok'];
            if ($stok_sekarang >= $jumlah) {
                $conn->query("INSERT INTO transaksi_detail (transaksi_id, id_produk, jumlah, harga_saat_transaksi, subtotal)
                              VALUES ($trxId, $id_produk, $jumlah, $harga, $subtotal)");
                $conn->query("UPDATE produk SET stok = stok - $jumlah WHERE produk_id = $id_produk");
            }
        }
    }

    // Bersihkan sesi setelah simpan
    unset($_SESSION['cart']);
    unset($_SESSION['struk_draft']);
}

// Mulai HTML
ob_start();
?>

<!-- HTML STRUK -->
<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 14px; margin: 0; padding: 0; color: #000; }
    .struk { width: 260px; margin: auto; padding: 20px; border: 1px dashed #ccc; }
    .title { color: #7e22ce; font-size: 18px; font-weight: bold; text-align: center; }
    .subtitle { text-align: center; font-size: 15px; font-weight: bold; color: #6b21a8; margin: 4px 0; }
    .desc { text-align: center; font-size: 13px; margin-bottom: 10px; }
    .line { border-top: 1px solid #ccc; margin: 10px 0; }
    .item { margin-bottom: 8px; }
    .item small { font-size: 11px; color: #666; display: block; }
    .total { display: flex; justify-content: space-between; font-weight: bold; color: #7e22ce; margin-top: 12px; }
    .footer { text-align: center; font-size: 12px; margin-top: 20px; }
    .qr { text-align: center; margin: 12px 0; }
    .qr img { width: 90px; border-radius: 8px; }
</style>

<div class="struk">
    <div class="title">Struk Pembayaran</div>
    <div class="subtitle">WAROENG ALYA</div>
    <div class="desc">Kantin Universitas Negeri Jakarta</div>

    <div class="line"></div>

    <table style="width:100%; font-size: 13px; margin-bottom: 10px;">
        <tr><td>No. Transaksi:</td><td style="text-align:right;"><strong>#<?= $s['kode'] ?></strong></td></tr>
        <tr><td>Tanggal:</td><td style="text-align:right;"><?= $s['tanggal'] ?></td></tr>
        <tr><td>Kasir:</td><td style="text-align:right;"><?= $kasirNama ?></td></tr>
        <tr><td>Pembayaran:</td><td style="text-align:right;"><?= $s['metode'] ?></td></tr>
    </table>

    <?php if ($s['metode'] === 'QRIS'): ?>
        <div class="qr">
            <p style="font-size:13px;">Silakan scan QR berikut:</p>
            <img src="http://localhost/kasiralya/kasir/assets/img/qris.png" alt="QRIS">
        </div>
    <?php endif; ?>

    <div class="line"></div>

    <?php foreach ($s['items'] as $it): ?>
        <div class="item">
            <?= $it['nama'] ?> <span style="float:right;">Rp <?= number_format($it['harga'] * $it['jumlah'], 0, ',', '.') ?></span><br>
            <small>Rp <?= number_format($it['harga'], 0, ',', '.') ?> × <?= $it['jumlah'] ?></small>
        </div>
    <?php endforeach; ?>

    <div class="line"></div>
    
    <table style="width:100%; font-size: 14px; margin-top: 12px; font-weight: bold; color: #7e22ce;">
        <tr>
            <td>TOTAL:</td>
            <td style="text-align:right;">Rp <?= number_format($s['total'], 0, ',', '.') ?></td>
        </tr>
    </table>

    <div class="footer">
        Terima kasih atas kunjungan Anda!<br>
        Selamat menikmati hidangan Waroeng Alya
    </div>
</div>

<?php
$html = ob_get_clean();

// Proses ke PDF
$dompdf = new Dompdf();
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->loadHtml($html);
$dompdf->setPaper('A6', 'portrait');
$dompdf->render();
$dompdf->stream("struk_{$s['kode']}.pdf", ["Attachment" => false]);
exit;