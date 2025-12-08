<?php
/**
 * Script Helper: Generate SQL INSERT statements untuk user dengan password hash yang benar
 * 
 * Cara penggunaan:
 * 1. Jalankan script ini: php generate_user_sql.php
 * 2. Copy output SQL dan paste ke file apotek_online.sql atau jalankan langsung di MySQL
 */

// Generate password hash
$admin_password = 'password123';
$user_password = 'userpass';

$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$user_hash = password_hash($user_password, PASSWORD_DEFAULT);

echo "-- ============================================\n";
echo "-- INSERT DATA DEMO: Users (Generated)\n";
echo "-- ============================================\n";
echo "-- Login credentials:\n";
echo "-- Admin: user@example.com / password123\n";
echo "-- User: customer@example.com / userpass\n";
echo "-- \n";
echo "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
echo "-- ============================================\n\n";

echo "INSERT INTO users (name, email, password, is_admin) VALUES\n";
echo "('Admin Demo', 'user@example.com', '" . $admin_hash . "', 1),\n";
echo "('Demo User', 'customer@example.com', '" . $user_hash . "', 0)\n";
echo "ON DUPLICATE KEY UPDATE name=name;\n\n";

echo "-- Copy SQL di atas dan paste ke file apotek_online.sql\n";
echo "-- atau jalankan langsung di MySQL/MariaDB\n";

