<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['produk_id'];
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $kategori = $_POST['kategori'];

    // Menangani upload gambar
    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        // Mengambil gambar baru dan meng-uploadnya
        $gambarTmp = $_FILES['gambar']['tmp_name'];
        $gambarName = uniqid() . '_' . $_FILES['gambar']['name'];
        $gambarPath = "../img/" . basename($gambarName);// menentukan folder penyimpanan
        move_uploaded_file($gambarTmp, $gambarPath);
        $gambar = $gambarName;
    } else {
        // Jika tidak ada gambar baru, menggunakan gambar lama
        // Ambil data gambar lama dari DB
        $result = $conn->query("SELECT gambar FROM produk WHERE produk_id = $id");
        $row = $result->fetch_assoc();
        $gambar = $row['gambar'];
    }

    // Update data produk
    $stmt = $conn->prepare("UPDATE produk SET nama=?, harga=?, stok=?, kategori=?, gambar=? WHERE produk_id=?");
    $stmt->bind_param("sdsssi", $nama, $harga, $stok, $kategori, $gambar, $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: produk.php");
exit();
?>
