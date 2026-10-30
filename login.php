<?php
session_start();
include 'config.php'; // koneksi ke database

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = md5($_POST['password'] ?? ''); // hashing md5 sesuai tabel kamu

    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Simpan semua data penting ke session
        $_SESSION['user_id']  = $user['user_id'];   // PK di tabel users
        $_SESSION['username'] = $user['username'];  // username
        $_SESSION['role']     = $user['role'];      // admin / kasir

        // Arahkan sesuai role
        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
            exit();
        } elseif ($user['role'] === 'kasir') {
            header('Location: kasir/kasir.php');
            exit();
        } else {
            $error = "Role tidak dikenali.";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login | Waroeng Alya</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/login.css?v=122">
</head>
<body>
  <div class="login-box">
    <div class="icon"><i class="fa-solid fa-store"></i></div>
    <h2>Waroeng Alya</h2>
    <p>Kasir Digital</p>

    <?php if ($error): ?>
      <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <div class="icon-input">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="username" placeholder="Masukkan username" required>
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="icon-input">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="password" placeholder="Masukkan password" required>
          <i class="fa-regular fa-eye toggle-password" onclick="togglePassword()"></i>
        </div>
      </div>

      <button type="submit" class="btn-login">Masuk</button>
    </form>
  </div>

  <script>
    function togglePassword() {
      const pass = document.getElementById("password");
      const toggle = document.querySelector(".toggle-password");

      if (pass.type === "password") {
        pass.type = "text";
        toggle.classList.remove("fa-eye");
        toggle.classList.add("fa-eye-slash");
      } else {
        pass.type = "password";
        toggle.classList.remove("fa-eye-slash");
        toggle.classList.add("fa-eye");
      }
    }
  </script>
</body>
</html>