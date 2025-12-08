<?php
// Orders Page
if(!is_logged_in()):
?>
  <div class="products-section">
    <div style="text-align: center; padding: 3rem 1rem;">
      <div style="font-size: 4rem; margin-bottom: 1rem;">🔐</div>
      <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Login Diperlukan</h3>
      <p style="color: var(--gray-500); margin-bottom: 2rem;">Silakan login untuk melihat riwayat pesanan Anda</p>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=login" class="btn btn-primary">🔐 Login Sekarang</a>
    </div>
  </div>
<?php
else:
  $u = current_user();
  $uid = $u['id'];
  $res = $mysqli->query("SELECT * FROM orders WHERE user_id=$uid ORDER BY created_at DESC");
?>
  <div class="products-section">
    <h2>📋 Riwayat Pesanan</h2>
    <?php if($res->num_rows===0): ?>
      <div style="text-align: center; padding: 3rem 1rem;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📦</div>
        <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Belum Ada Pesanan</h3>
        <p style="color: var(--gray-500); margin-bottom: 2rem;">Mulai belanja untuk membuat pesanan pertama Anda</p>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">🏠 Mulai Belanja</a>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 1.5rem;">
      <?php while($o=$res->fetch_assoc()): ?>
          <div style="background: white; border: 1px solid var(--gray-200); border-radius: 0.75rem; padding: 1.5rem; box-shadow: var(--shadow);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-200);">
              <div>
                <h3 style="color: var(--gray-900); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;">
                  📦 Order #<?php echo $o['id']; ?>
                </h3>
                <div style="display: flex; align-items: center; gap: 1rem;">
                  <span style="background: <?php echo $o['status']=='paid' ? 'var(--green)' : 'var(--orange)'; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 600;">
                    <?php echo $o['status']=='paid' ? '✅ Lunas' : '⏳ Pending'; ?>
                  </span>
                  <span style="color: var(--gray-500); font-size: 0.875rem;">
                    📅 <?php echo date('d M Y H:i', strtotime($o['created_at'])); ?>
                  </span>
                </div>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">
                  Rp <?php echo number_format($o['total'],0,',','.'); ?>
                </div>
              </div>
            </div>
            
            <div>
              <h4 style="color: var(--gray-700); font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Detail Produk:</h4>
              <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php
              $itres = $mysqli->prepare("SELECT oi.qty,oi.price,p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
              $itres->bind_param('i',$o['id']); $itres->execute(); $rr=$itres->get_result();
              while($it=$rr->fetch_assoc()):
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: var(--gray-50); border-radius: 0.5rem;">
                  <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.875rem;">💊</div>
                    <div>
                      <div style="font-weight: 600; color: var(--gray-900);"><?php echo esc($it['name']); ?></div>
                      <div style="font-size: 0.875rem; color: var(--gray-500);"><?php echo $it['qty']; ?> x Rp <?php echo number_format($it['price'],0,',','.'); ?></div>
                    </div>
                  </div>
                  <div style="font-weight: 600; color: var(--green);">
                    Rp <?php echo number_format($it['price']*$it['qty'],0,',','.'); ?>
                  </div>
                </div>
            <?php endwhile; ?>
              </div>
            </div>
        </div>
      <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
