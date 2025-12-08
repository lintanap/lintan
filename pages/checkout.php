<?php
// Checkout Page
if(!is_logged_in()):
?>
  <div class="products-section">
    <div style="text-align: center; padding: 3rem 1rem;">
      <div style="font-size: 4rem; margin-bottom: 1rem;">🔐</div>
      <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Login Diperlukan</h3>
      <p style="color: var(--gray-500); margin-bottom: 2rem;">Silakan login terlebih dahulu untuk melanjutkan checkout</p>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=login" class="btn btn-primary">🔐 Login Sekarang</a>
    </div>
  </div>
<?php
else:
  $summary = cart_summary();
  if(empty($summary['items'])):
?>
  <div class="products-section">
    <div style="text-align: center; padding: 3rem 1rem;">
      <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
      <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Keranjang Kosong</h3>
      <p style="color: var(--gray-500); margin-bottom: 2rem;">Tambahkan produk ke keranjang terlebih dahulu</p>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">🏠 Mulai Belanja</a>
    </div>
  </div>
<?php else: ?>
  <div class="products-section">
    <h2>💳 Checkout</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
      <!-- Order Summary -->
      <div>
        <h3 style="color: var(--gray-700); margin-bottom: 1rem; font-size: 1.25rem;">📋 Ringkasan Pesanan</h3>
        <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--gray-200);">
      <?php foreach($summary['items'] as $it): $p=$it['product']; ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-200);">
              <div>
                <div style="font-weight: 600; color: var(--gray-900);"><?php echo esc($p['name']); ?></div>
                <div style="font-size: 0.875rem; color: var(--gray-500);"><?php echo $it['qty']; ?> x Rp <?php echo number_format($p['price'],0,',','.'); ?></div>
              </div>
              <div style="font-weight: 600; color: var(--green);">Rp <?php echo number_format($it['subtotal'],0,',','.'); ?></div>
            </div>
      <?php endforeach; ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; margin-top: 1rem; border-top: 2px solid var(--primary);">
          <span style="font-size: 1.25rem; font-weight: 700; color: var(--gray-700);">Total:</span>
          <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">Rp <?php echo number_format($summary['total'],0,',','.'); ?></span>
        </div>
        </div>
      </div>
      
      <!-- Payment Form -->
      <div>
        <h3 style="color: var(--gray-700); margin-bottom: 1rem; font-size: 1.25rem;">💳 Informasi Pembayaran</h3>
        <form method="post" style="background: var(--gray-50); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--gray-200);">
      <input type="hidden" name="form" value="checkout">
        
        <div class="form-group">
          <label style="font-weight: 600; color: var(--gray-700);">💳 Metode Pembayaran</label>
          <div style="background: white; padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--gray-200);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--green), #10b981); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">💰</div>
              <div>
                <div style="font-weight: 600; color: var(--gray-900);">Bayar Ditempat (COD)</div>
                <div style="font-size: 0.875rem; color: var(--gray-500);">Pembayaran saat barang diterima</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label style="font-weight: 600; color: var(--gray-700);">📱 Nomor Telepon</label>
          <input type="tel" class="form-input" placeholder="08xxxxxxxxxx" value="081234567890" readonly style="background: var(--gray-100);">
        </div>
        
        <div class="form-group">
          <label style="font-weight: 600; color: var(--gray-700);">📍 Alamat Pengiriman</label>
          <textarea class="form-input" rows="3" placeholder="Masukkan alamat lengkap pengiriman" readonly style="background: var(--gray-100);">Jl. Contoh No. 123, Kelurahan Contoh, Kecamatan Contoh, Kota Contoh, 12345</textarea>
        </div>
        
        <div style="background: #f0fdf4; padding: 1rem; border-radius: 0.5rem; border: 1px solid #bbf7d0; margin: 1rem 0;">
          <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--green); font-weight: 600;">
            <span>✅</span>
            <span>Simulasi Pembayaran - Langsung Berhasil</span>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.125rem;">
          💳 Bayar Sekarang - Rp <?php echo number_format($summary['total'],0,',','.'); ?>
        </button>
    </form>
      </div>
    </div>
  </div>
<?php
  endif;
endif;
?>
