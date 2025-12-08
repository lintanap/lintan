-- ============================================
-- DATABASE: APOTEK ONLINE
-- Deskripsi: Database untuk aplikasi apotek online
-- ============================================

-- Hapus database jika sudah ada (hati-hati!)
-- DROP DATABASE IF EXISTS apotek_online;

-- Buat database baru
CREATE DATABASE IF NOT EXISTS apotek_online 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

-- Gunakan database yang baru dibuat
USE apotek_online;

-- ============================================
-- TABEL: users (Pengguna)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABEL: products (Produk Obat)
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABEL: orders (Pesanan)
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABEL: order_items (Item Pesanan)
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT DATA DEMO: Users
-- ============================================
-- PENTING: Hash password di bawah ini adalah contoh.
-- Untuk keamanan, sebaiknya buat user melalui aplikasi atau gunakan:
-- index.php?action=setup (akan membuat user demo secara otomatis)
--
-- Login credentials demo:
-- Admin: user@example.com / password123
-- User: customer@example.com / userpass
--
-- Opsi 1: Buat user melalui aplikasi (DISARANKAN)
-- Jalankan: index.php?action=setup atau daftar melalui halaman register
--
-- Opsi 2: Insert user manual dengan hash password
-- Generate hash dulu dengan PHP: echo password_hash('password123', PASSWORD_DEFAULT);
-- Lalu jalankan INSERT statement di bawah (uncomment dulu):

/*
-- Uncomment baris di bawah dan isi dengan hash password yang sudah digenerate
INSERT INTO users (name, email, password, is_admin) VALUES
('Admin Demo', 'user@example.com', 'PASTE_HASH_PASSWORD123_DISINI', 1),
('Demo User', 'customer@example.com', 'PASTE_HASH_USERPASS_DISINI', 0);
*/

-- ============================================
-- INSERT DATA DEMO: Products (Produk Obat)
-- ============================================
INSERT INTO products (name, description, price, stock) VALUES
('Paracetamol 500mg (Strip 10)', 'Pereda demam dan nyeri', 5000.00, 50),
('OBH Combi Sirup 60ml', 'Syrup batuk pereda dahak', 15000.00, 20),
('Minyak Kayu Putih 30ml', 'Untuk menghangatkan badan', 8000.00, 30),
('Vitamin C 1000mg (Box 10)', 'Suplemen Vitamin C', 25000.00, 15),
('Amoxicillin 500mg (Strip 10)', 'Antibiotik untuk infeksi bakteri', 12000.00, 25),
('Cetirizine 10mg (Strip 10)', 'Antihistamin untuk alergi', 8500.00, 40),
('Ibuprofen 400mg (Strip 10)', 'Pereda nyeri dan anti inflamasi', 7500.00, 35),
('Tolak Angin Cair 15ml', 'Obat herbal untuk masuk angin', 9500.00, 30)
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- CATATAN PENTING
-- ============================================
-- 1. Password di atas adalah contoh saja. Untuk keamanan, 
--    gunakan password_hash() di PHP saat registrasi.
-- 2. Hapus baris ON DUPLICATE KEY UPDATE jika ingin insert ulang data.
-- 3. Untuk reset database, jalankan:
--    DROP DATABASE IF EXISTS apotek_online;
--    Kemudian jalankan script ini dari awal.
-- ============================================

