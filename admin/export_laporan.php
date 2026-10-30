<?php
require_once '../includes/db.php';

// Ambil parameter
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
}

// Header file CSV agar bisa dibuka Excel
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Waroeng_Alya.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "Tanggal\tKode Transaksi\tKasir\tProduk (xJumlah)\tTotal\tMetode Pembayaran\n";

// Ambil data transaksi
$query = $conn->query("SELECT * FROM transaksi $where ORDER BY tanggal DESC");
while ($row = $query->fetch_assoc()) {
    $kode = $row['kode_transaksi'];
    $kasir = $row['kasir'];
    $tanggal = date('d/m/Y', strtotime($row['tanggal']));
    $total = number_format($row['total'], 0, ',', '.');
    $metode = $row['metode_pembayaran'];

    // Ambil detail item
    $items = [];
    $detail = $conn->query("SELECT td.jumlah, td.harga_saat_transaksi, p.nama FROM transaksi_detail td JOIN produk p ON td.id_produk = p.id WHERE td.kode_transaksi = '$kode'");
    while ($d = $detail->fetch_assoc()) {
        $items[] = $d['nama'] . " x" . $d['jumlah'];
    }
    $produk_list = implode(", ", $items);

    echo "$tanggal\t$kode\t$kasir\t$produk_list\t$total\t$metode\n";
}
?>