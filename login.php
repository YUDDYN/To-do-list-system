<?php
session_start();
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $stmt   = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user   = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['id_user']  = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        header('Location: ../crud/index.php');
        exit();
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
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
      <p>Masuk ke akun kamu</p>
    </div> <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= $error ?></div>
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
      <button type="submit" class="btn-submit">Login</button>
    </form>

    <div class="auth-footer">
      Belum punya akun? <a href="register.php">Daftar di sini</a>
    </div>

  </div> </body>
</html>