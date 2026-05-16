<?php
session_start();
require_once '../config.php';

$error  = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $konfirm  = $_POST['konfirmasi'];

    if ($password !== $konfirm) {
        $error = 'Password dan konfirmasi tidak cocok.';
    } else {
        $cek = $conn->prepare("SELECT id_user FROM users WHERE username = ?");
        $cek->bind_param('s', $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Username sudah dipakai, coba yang lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
            $stmt->bind_param('ss', $username, $hash);
            $stmt->execute();
            $sukses = 'Akun berhasil dibuat!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar</title>
  <link rel="stylesheet" href="../design/global.css">
  <link rel="stylesheet" href="../design/auth.css">
</head>
<body>
  <div class="auth-card">

  <div class="auth-logo">
      <div class="auth-logo-icon">
        <img src="../asset/logo.jpg" alt="Logo" width="42" height="42" style="border-radius: 20px;">
      </div>
      <h1>Grow Plan</h1>
      <p>Buat akun baru</p>
    </div>

    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <?php if ($sukses): ?>
      <div class="alert-success">✅ <?= $sukses ?> <a href="login.php">Login sekarang</a></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
      </div>
      <div class="form-group">
        <label>Konfirmasi Password</label>
        <input type="password" name="konfirmasi" placeholder="Ulangi password" required>
      </div>
      <button type="submit" class="btn-submit">Daftar</button>
    </form>

    <div class="auth-footer">
      Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>

  </div>
</body>
</html>