<?php
// Test database connection for apotek_online
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','apotek_online');

echo "<h2>Testing Database Connection</h2>";

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($mysqli->connect_error) {
        throw new Exception('Koneksi gagal: ' . $mysqli->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test creating database
    $mysqli->query("CREATE DATABASE IF NOT EXISTS ".DB_NAME." CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $mysqli->select_db(DB_NAME);
    
    echo "<p style='color: green;'>✓ Database 'apotek_online' created/selected successfully!</p>";
    
    // Test creating tables
    $mysqli->query("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      is_admin TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "<p style='color: green;'>✓ Table 'users' created successfully!</p>";
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      description TEXT,
      price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      stock INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "<p style='color: green;'>✓ Table 'products' created successfully!</p>";
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      total DECIMAL(12,2) NOT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "<p style='color: green;'>✓ Table 'orders' created successfully!</p>";
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS order_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id INT NOT NULL,
      product_id INT NOT NULL,
      qty INT NOT NULL,
      price DECIMAL(10,2) NOT NULL,
      FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "<p style='color: green;'>✓ Table 'order_items' created successfully!</p>";
    
    echo "<h3>All tests passed! You can now use the apotek_online.php application.</h3>";
    echo "<p><a href='apotek_online.php'>Go to Apotek Online Application</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure XAMPP MySQL service is running.</p>";
}
?>
