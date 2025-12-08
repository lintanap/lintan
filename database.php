<?php
// Database Connection and Helper Functions
// --------------------------------------------------------

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Database connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    die('Koneksi gagal: ' . $mysqli->connect_error);
}

// Create database if not exists
$mysqli->query("CREATE DATABASE IF NOT EXISTS ".DB_NAME." CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db(DB_NAME);

// -------------------- Helper Functions --------------------
function esc($s){ 
    return htmlspecialchars($s, ENT_QUOTES); 
}

function is_logged_in(){ 
    return isset($_SESSION['user_id']); 
}

function current_user(){
    global $mysqli;
    if(!is_logged_in()) return null;
    $id = intval($_SESSION['user_id']);
    $stmt = $mysqli->prepare("SELECT id,name,email,is_admin FROM users WHERE id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc();
}

function ensure_setup_tables(){
    global $mysqli;
    // users table
    $mysqli->query("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      is_admin TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // products table
    $mysqli->query("CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      description TEXT,
      price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      stock INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // orders table
    $mysqli->query("CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      total DECIMAL(12,2) NOT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // order_items table
    $mysqli->query("CREATE TABLE IF NOT EXISTS order_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id INT NOT NULL,
      product_id INT NOT NULL,
      qty INT NOT NULL,
      price DECIMAL(10,2) NOT NULL,
      FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function create_demo_data(){
    global $mysqli;
    // Create demo admin user if not exists
    $res = $mysqli->query("SELECT id FROM users WHERE email='user@example.com'");
    if($res->num_rows===0){
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (name,email,password,is_admin) VALUES (?,?,?,1)");
        $name = 'Admin Demo';
        $email = 'user@example.com';
        $stmt->bind_param('sss',$name,$email,$hash);
        $stmt->execute();
        
        // Create demo regular user
        $hash2 = password_hash('userpass', PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (name,email,password,is_admin) VALUES (?,?,?,0)");
        $n2='Demo User'; $e2='customer@example.com';
        $stmt->bind_param('sss',$n2,$e2,$hash2); 
        $stmt->execute();
    }
    
    // Create demo products
    $res = $mysqli->query("SELECT id FROM products LIMIT 1");
    if($res->num_rows===0){
        $stmt = $mysqli->prepare("INSERT INTO products (name,description,price,stock) VALUES (?,?,?,?)");
        $items = [
            ['Paracetamol 500mg (Strip 10)','Pereda demam dan nyeri',5000.00,50],
            ['OBH Combi Sirup 60ml','Syrup batuk pereda dahak',15000.00,20],
            ['Minyak Kayu Putih 30ml','Untuk menghangatkan badan',8000.00,30],
            ['Vitamin C 1000mg (Box 10)','Suplemen Vitamin C',25000.00,15]
        ];
        foreach($items as $it){
            $stmt->bind_param('ssdi',$it[0],$it[1],$it[2],$it[3]);
            $stmt->execute();
        }
    }
}

// -------------------- CART Helper Functions --------------------
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = []; // product_id => qty

function add_to_cart($pid,$qty=1){
    if($qty<1) return;
    if(isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid] += $qty;
    else $_SESSION['cart'][$pid] = $qty;
}

function update_cart($pid,$qty){
    if($qty<=0) unset($_SESSION['cart'][$pid]);
    else $_SESSION['cart'][$pid] = $qty;
}

function cart_items(){ 
    return $_SESSION['cart']; 
}

function cart_summary(){
    global $mysqli;
    $items = cart_items();
    if(empty($items)) return ['items'=>[],'total'=>0];
    $ids = implode(',', array_map('intval', array_keys($items)));
    $res = $mysqli->query("SELECT id,name,price,stock FROM products WHERE id IN ($ids)");
    $rows = []; $total=0;
    while($p=$res->fetch_assoc()){
        $pid = $p['id'];
        $qty = isset($items[$pid]) ? $items[$pid] : 0;
        $sub = $qty * $p['price'];
        $rows[]=['product'=>$p,'qty'=>$qty,'subtotal'=>$sub];
        $total += $sub;
    }
    return ['items'=>$rows,'total'=>$total];
}

// Initialize database and demo data
ensure_setup_tables();
if(isset($_GET['action']) && $_GET['action']==='setup'){
    create_demo_data();
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}
?>
