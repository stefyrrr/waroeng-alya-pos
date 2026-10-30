<?php
session_start();
require_once '../includes/db.php';

// Hapus user jika ada ID yang dikirimkan
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);

    // Jangan izinkan admin menghapus dirinya sendiri
    if ($_SESSION['username'] !== 'admin' || $_SESSION['username'] !== $conn->query("SELECT username FROM users WHERE user_id=$id_hapus")->fetch_assoc()['username']) {
        $conn->query("DELETE FROM users WHERE user_id = $id_hapus");
    }

    header("Location: pengguna.php");
    exit;
}

$result = $conn->query("SELECT user_id, username, role FROM users ORDER BY username ASC");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;

}

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['tambah_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Cek apakah username sudah ada
    $cek = $conn->query("SELECT * FROM users WHERE username = '$username'");
    if ($cek->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: pengguna.php"); // reload untuk menghindari form resubmit
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengguna - Waroeng Alya</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=124">
  <link rel="stylesheet" href="assets/css/pengguna.css?v=127">
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
    <a href="laporan.php"><i class='bx bx-line-chart'></i> Laporan</a>
    <a href="pengguna.php" class="active"><i class='bx bx-user'></i> Pengguna</a>
  </nav>
        <h1>Manajemen Pengguna</h1>
        <p>Kelola akses pengguna sistem</p>

        <div class="user-container">
            <h2>Daftar Pengguna</h2>
        <div class="user-grid">
         <?php
         foreach ($users as $user) {
          echo '<div class="user-card">';
          echo '<div class="user-icon ' . strtolower($user['role']) . '"><i class="bx bx-user"></i></div>';
          echo '<div class="user-info">';
          echo '<div class="user-name">' . ucfirst($user['role']) . '</div>';
          echo '<div class="user-username">' . $user['username'] . '</div>';
          echo '<span class="role-badge ' . strtolower($user['role']) . '-role">' . ucfirst($user['role']) .'</span>';
          echo '<a href="pengguna.php?hapus=' . $user['user_id'] . '" onclick="return confirm(\'Yakin ingin menghapus pengguna ini?\')" class="hapus-btn">Hapus</a>';
          echo '</div>';
          echo '</div>';
         }
         ?>
    </div>
    </div>
         
         <div class="form-tambah-user">
          <h2>Tambah Pengguna Baru</h2>
          <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
              <option value="">Pilih Peran</option>
              <option value="admin">Admin</option>
              <option value="kasir">Kasir</option>
            </select>
            <button type="submit" name="tambah_user">Tambah Pengguna</button>
          </form>
        </div>
</body>
</html>