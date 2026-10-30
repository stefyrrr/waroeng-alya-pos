<?php
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Matikan FK check
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    // Hapus transaksi detail dulu
    $conn->query("DELETE FROM transaksi_detail WHERE id_produk = $id");

    // Lalu hapus produk
    $conn->query("DELETE FROM produk WHERE id = $id");

    // Nyalakan FK check lagi
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
}

header("Location: produk.php");
exit;
