<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Apotek Online</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="header">
  <div class="wrap">
    <div class="logo-container">
      <img src="apotek.png" alt="Logo Apotek Online" class="logo">
      <h1>Apotek Maju Sehat</h1>
    </div>
    <nav>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Beranda</a>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=cart" class="cart-link">🛒 Keranjang (<?php echo array_sum($_SESSION['cart']); ?>)</a>
      <?php if(is_logged_in()): $u=current_user(); ?>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=orders">Pesanan</a>
        <?php if($u && $u['is_admin']==1): ?>
          <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin">Admin</a>
        <?php endif; ?>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?do=logout">Logout (<?php echo esc($u['name']); ?>)</a>
      <?php else: ?>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=login">Login</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=register">Register</a>
      <?php endif; ?> 
    </nav>
  </div>
</header>

<main class="container">
  <?php if(!empty($errors)): foreach($errors as $e): ?>
    <div class="alert err"><?php echo esc($e); ?></div>
  <?php endforeach; endif; ?>
  <?php if($info): ?><div class="alert info"><?php echo esc($info); ?></div><?php endif; ?>
