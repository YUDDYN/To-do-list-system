<?php
include("connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
  <div class="form">
    <form action="" method="post">
      <h2>Registration</h2>
      <p class="msg"></p>
      <div class="form-group">
        <input type="text" name="name" placeholder="Enter your name" class="form-control" required></div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Enter your email" class="form-control" required></div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Enter your password" class="form-control" required></div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Comfirm your password" class="form-control" required></div>
    </form>
  </div>
</body>
</html>