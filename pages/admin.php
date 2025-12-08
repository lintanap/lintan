<?php
// Admin Page
$u = current_user();
if(!$u || $u['is_admin']!=1):
?>
  <div class="products-section">
    <div style="text-align: center; padding: 3rem 1rem;">
      <div style="font-size: 4rem; margin-bottom: 1rem;">🚫</div>
      <h3 style="color: var(--red); margin-bottom: 1rem;">Akses Ditolak</h3>
      <p style="color: var(--gray-500); margin-bottom: 2rem;">Anda tidak memiliki izin untuk mengakses halaman admin</p>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">Kembali ke Beranda</a>
    </div>
  </div>
<?php
else:
  $res = $mysqli->query("SELECT * FROM products ORDER BY id DESC");
?>
  <div class="products-section">
    <h2>Panel Admin - Kelola Produk</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-top: 2rem;">
      <!-- Add Product Form -->
      <div>
        <h3 style="color: var(--gray-700); margin-bottom: 1rem; font-size: 1.25rem;">Tambah Produk Baru</h3>
        <form method="post" style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--gray-200);">
        <input type="hidden" name="form" value="admin_add">
          
          <div class="form-group">
            <label for="name">Nama Produk</label>
            <input type="text" id="name" name="name" class="form-input" placeholder="Masukkan nama produk" required>
          </div>
          
          <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" class="form-input" rows="3" placeholder="Deskripsi produk"></textarea>
          </div>
          
          <div class="form-group">
            <label for="price">Harga (Rp)</label>
            <input type="number" id="price" name="price" step="0.01" class="form-input" placeholder="0.00" required>
          </div>
          
          <div class="form-group">
            <label for="stock">Stok</label>
            <input type="number" id="stock" name="stock" class="form-input" placeholder="0" required>
          </div>
          
          <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Produk</button>
      </form>
    </div>
      
      <!-- Products List -->
      <div>
        <h3 style="color: var(--gray-700); margin-bottom: 1rem; font-size: 1.25rem;">Daftar Produk</h3>
        <div class="table-container">
      <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Produk</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Aksi</th>
              </tr>
            </thead>
        <tbody>
          <?php while($p=$res->fetch_assoc()): ?>
            <tr>
                  <td style="font-weight: 600; color: var(--primary);">#<?php echo $p['id']; ?></td>
                  <td>
                    <div>
                      <div style="font-weight: 600; color: var(--gray-900);"><?php echo esc($p['name']); ?></div>
                      <div style="font-size: 0.875rem; color: var(--gray-500);"><?php echo esc(substr($p['description'], 0, 50)); ?><?php echo strlen($p['description']) > 50 ? '...' : ''; ?></div>
                    </div>
                  </td>
                  <td style="font-weight: 600; color: var(--primary);">Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                  <td>
                    <span style="background: <?php echo $p['stock'] > 10 ? 'var(--green)' : ($p['stock'] > 0 ? 'var(--orange)' : 'var(--red)'); ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 600;">
                      <?php echo $p['stock']; ?> unit
                    </span>
                  </td>
                  <td>
                    <div style="display: flex; gap: 0.5rem;">
                      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin_edit&id=<?php echo $p['id']; ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?do=delete_product&id=<?php echo $p['id']; ?>" onclick="return confirm('Yakin ingin menghapus produk ini?')" class="btn btn-small" style="background: var(--red); color: white;">🗑️ Hapus</a>
                    </div>
                </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
