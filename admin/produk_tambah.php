<?php
session_start();
require_once '../includes/db.php';

// Ambil data dari form
$nama = $_POST['nama'] ?? '';
$harga = $_POST['harga'] ?? 0;
$stok = $_POST['stok'] ?? 0;
$kategori = $_POST['kategori'] ?? '';

// Handle upload gambar
$gambar = '';
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
    $gambarTmp = $_FILES['gambar']['tmp_name'];
    $gambarName = $_FILES['gambar']['name'];
    $gambarPath = '../img/' . basename($gambarName); // tentukan lokasi folder untuk gambar
    move_uploaded_file($gambarTmp, $gambarPath);
    $gambar = $gambarPath;
} else {
    $gambar = 'assets/img/default.png'; // jika tidak ada gambar, set default
}

// Simpan data produk ke database
$stmt = $conn->prepare("INSERT INTO produk (nama, harga, stok, kategori, gambar, status) VALUES (?, ?, ?, ?, ?, ?)");
$status = 1; // status aktif
$stmt->bind_param("sdissi", $nama, $harga, $stok, $kategori, $gambar, $status);
$stmt->execute();
$stmt->close();

// Redirect kembali ke halaman produk
header("Location: produk.php");
exit;
?>