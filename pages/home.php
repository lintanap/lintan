<?php
// Home Page
$res = $mysqli->query("SELECT * FROM products ORDER BY id DESC");
$total_products = $mysqli->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $mysqli->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_users = $mysqli->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
?>

<!-- Hero Section -->
<div class="hero-section">
  <h1>Selamat Datang di Apotek Online</h1>
  <p>Dapatkan obat-obatan berkualitas dengan mudah dan aman. Layanan 24/7 untuk kesehatan Anda dan keluarga.</p>
  
  <div class="hero-stats">
    <div class="stat-item">
      <div class="stat-number"><?php echo $total_products; ?></div>
      <div class="stat-label">Produk Tersedia</div>
    </div>
    <div class="stat-item">
      <div class="stat-number"><?php echo $total_orders; ?></div>
      <div class="stat-label">Pesanan Berhasil</div>
    </div>
    <div class="stat-item">
      <div class="stat-number"><?php echo $total_users; ?></div>
      <div class="stat-label">Pelanggan Puas</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">24/7</div>
      <div class="stat-label">Layanan</div>
    </div>
  </div>
</div>

<!-- Products Section -->
<div class="products-section">
  <h2>Produk Obat Terbaru</h2>
  <div class="products-grid">
    <?php while($p=$res->fetch_assoc()): ?>
      <div class="product-card">
        <h3 class="product-name"><?php echo esc($p['name']); ?></h3>
        <p class="product-description"><?php echo esc($p['description']); ?></p>
        <p class="product-price">Rp <?php echo number_format($p['price'],0,',','.'); ?></p>
        <p class="product-stock">📦 Stok: <?php echo intval($p['stock']); ?> unit</p>
        <form method="post">
          <input type="hidden" name="form" value="add_cart">
          <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
          <div class="product-actions">
            <input type="number" name="qty" value="1" min="1" max="<?php echo $p['stock']; ?>" class="quantity-input">
            <button class="btn btn-primary" type="submit">🛒 Tambah ke Keranjang</button>
            <a class="quick-add-btn" href="<?php echo $_SERVER['PHP_SELF']; ?>?do=add&id=<?php echo $p['id']; ?>" title="Tambah 1 ke keranjang">+</a>
          </div>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
</div>
