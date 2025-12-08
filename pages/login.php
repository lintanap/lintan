<?php
// Login Page
?>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h2>🔐 Masuk ke Akun Anda</h2>
      <p>Selamat datang kembali! Silakan login untuk melanjutkan</p>
    </div>
    
    <form method="post" class="auth-form">
    <input type="hidden" name="form" value="login">
      
      <div class="form-group">
        <label for="email">📧 Email</label>
        <input type="email" id="email" name="email" class="form-input" placeholder="Masukkan email Anda" required>
      </div>
      
      <div class="form-group">
        <label for="password">🔒 Password</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password Anda" required>
      </div>
      
      <button type="submit" class="btn btn-primary">🚀 Masuk</button>
  </form>
    
    <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
      <p style="color: var(--gray-500); font-size: 0.875rem;">
        Belum punya akun? <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=register" style="color: var(--primary); text-decoration: none; font-weight: 500;">Daftar di sini</a>
      </p>
    </div>
    
    <div style="background: var(--gray-50); padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; border: 1px solid var(--gray-200);">
      <h4 style="color: var(--gray-700); font-size: 0.875rem; margin-bottom: 0.5rem;">🔑 Akun Demo:</h4>
      <p style="color: var(--gray-600); font-size: 0.75rem; margin-bottom: 0.25rem;">
        <strong>Admin:</strong> user@example.com / password123
      </p>
      <p style="color: var(--gray-600); font-size: 0.75rem;">
        <strong>User:</strong> customer@example.com / userpass
      </p>
      <p style="color: var(--gray-500); font-size: 0.7rem; margin-top: 0.5rem;">
      </p>
    </div>
  </div>
</div>
