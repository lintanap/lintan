<?php
// Register Page
?>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h2>📝 Daftar Akun Baru</h2>
      <p>Bergabunglah dengan kami untuk mendapatkan akses ke produk obat terbaik</p>
    </div>
    
    <form method="post" class="auth-form">
    <input type="hidden" name="form" value="register">
      
      <div class="form-group">
        <label for="name">👤 Nama Lengkap</label>
        <input type="text" id="name" name="name" class="form-input" placeholder="Masukkan nama lengkap Anda" required>
      </div>
      
      <div class="form-group">
        <label for="email">📧 Email</label>
        <input type="email" id="email" name="email" class="form-input" placeholder="contoh@email.com" required>
      </div>
      
      <div class="form-group">
        <label for="password">🔒 Password</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required>
      </div>
      
      <button type="submit" class="btn btn-primary">🚀 Daftar Sekarang</button>
  </form>
    
    <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
      <p style="color: var(--gray-500); font-size: 0.875rem;">
        Sudah punya akun? <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=login" style="color: var(--primary); text-decoration: none; font-weight: 500;">Login di sini</a>
      </p>
    </div>
  </div>
</div>
