<?php
// Cart Page
$summary = cart_summary();
?>

<div class="products-section">
  <h2>🛒 Keranjang Belanja</h2>
  <?php if(empty($summary['items'])): ?>
    <div style="text-align: center; padding: 3rem 1rem;">
      <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
      <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Keranjang Anda Kosong</h3>
      <p style="color: var(--gray-500); margin-bottom: 2rem;">Mulai belanja untuk menambahkan produk ke keranjang</p>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">🏠 Mulai Belanja</a>
    </div>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="form" value="update_cart">
      <div class="table-container">
      <table class="table">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Harga</th>
              <th>Jumlah</th>
              <th>Subtotal</th>
            </tr>
          </thead>
        <tbody>
          <?php foreach($summary['items'] as $it): $p=$it['product']; ?>
            <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">💊</div>
                    <div>
                      <div style="font-weight: 600; color: var(--gray-900);"><?php echo esc($p['name']); ?></div>
                      <div style="font-size: 0.875rem; color: var(--gray-500);">Stok: <?php echo $p['stock']; ?> unit</div>
                    </div>
                  </div>
                </td>
                <td style="font-weight: 600; color: var(--primary);">Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                <td>
                  <input type="number" name="qty[<?php echo $p['id']; ?>]" value="<?php echo $it['qty']; ?>" min="0" max="<?php echo $p['stock']; ?>" class="quantity-input">
                </td>
                <td style="font-weight: 600; color: var(--green);">Rp <?php echo number_format($it['subtotal'],0,',','.'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      
      <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem; margin-top: 1.5rem; border: 1px solid var(--gray-200);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <span style="font-size: 1.25rem; font-weight: 600; color: var(--gray-700);">Total Pembayaran:</span>
          <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">Rp <?php echo number_format($summary['total'],0,',','.'); ?></span>
        </div>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
          <button type="submit" class="btn btn-secondary">🔄 Update Keranjang</button>
          <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=checkout" class="btn btn-primary">💳 Lanjut ke Checkout</a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>
