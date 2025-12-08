<?php
// Admin Edit Page
$u = current_user();
if(!$u || $u['is_admin']!=1):
?>
  <div class="products-section">
    <div style="text-align: center; padding: 3rem 1rem;">
      <div style="font-size: 4rem; margin-bottom: 1rem;">🚫</div>
      <h3 style="color: var(--red); margin-bottom: 1rem;">Akses Ditolak</h3>
      <p style="color: var(--gray-500); margin-bottom: 2rem;">Anda tidak memiliki izin untuk mengakses halaman ini</p>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin" class="btn btn-primary">⚙️ Kembali ke Admin</a>
    </div>
  </div>
<?php
else:
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $p = $res->fetch_assoc();
  if(!$p):
?>
    <div class="products-section">
      <div style="text-align: center; padding: 3rem 1rem;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">❌</div>
        <h3 style="color: var(--red); margin-bottom: 1rem;">Produk Tidak Ditemukan</h3>
        <p style="color: var(--gray-500); margin-bottom: 2rem;">Produk yang Anda cari tidak ditemukan atau sudah dihapus</p>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin" class="btn btn-primary">⚙️ Kembali ke Admin</a>
      </div>
    </div>
  <?php else: ?>
    <div class="products-section">
      <h2>✏️ Edit Produk #<?php echo $p['id']; ?></h2>
      
      <div style="max-width: 600px; margin: 2rem auto;">
        <form method="post" style="background: var(--gray-50); padding: 2rem; border-radius: 0.75rem; border: 1px solid var(--gray-200);">
          <input type="hidden" name="form" value="admin_edit">
          <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
          
          <div class="form-group">
            <label for="name">📦 Nama Produk</label>
            <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($p['name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="description">📝 Deskripsi</label>
            <textarea id="description" name="description" class="form-input" rows="4"><?php echo htmlspecialchars($p['description']); ?></textarea>
          </div>

          <div class="form-group">
            <label for="price">💰 Harga (Rp)</label>
            <input type="number" id="price" name="price" step="0.01" class="form-input" value="<?php echo $p['price']; ?>" required>
          </div>

          <div class="form-group">
            <label for="stock">📊 Stok</label>
            <input type="number" id="stock" name="stock" class="form-input" value="<?php echo $p['stock']; ?>" required>
          </div>

          <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Update Produk</button>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin" class="btn btn-secondary">❌ Batal</a>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
