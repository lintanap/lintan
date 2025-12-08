<?php
// aplikasi_obat_online.php
// --------------------------------------------------------
// CONFIG DATABASE - sesuaikan bila perlu
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','apotek_online');

session_start();

// koneksi
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    die('Koneksi gagal: ' . $mysqli->connect_error);
}

// buat db jika belum ada
$mysqli->query("CREATE DATABASE IF NOT EXISTS ".DB_NAME." CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db(DB_NAME);


// -------------------- helper & setup --------------------
function esc($s){ return htmlspecialchars($s, ENT_QUOTES); }
function is_logged_in(){ return isset($_SESSION['user_id']); }
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
    // users
    $mysqli->query("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      is_admin TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // products
    $mysqli->query("CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      description TEXT,
      price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      stock INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // orders
    $mysqli->query("CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      total DECIMAL(12,2) NOT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // order_items
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
    // buat user demo jika belum ada
    $res = $mysqli->query("SELECT id FROM users WHERE email='user@example.com'");
    if($res->num_rows===0){
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (name,email,password,is_admin) VALUES (?,?,?,1)");
        $name = 'Admin Demo';
        $email = 'user@example.com';
        $stmt->bind_param('sss',$name,$email,$hash);
        $stmt->execute();
        // buat user non-admin juga
        $hash2 = password_hash('userpass', PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (name,email,password,is_admin) VALUES (?,?,?,0)");
        $n2='Demo User'; $e2='customer@example.com';
        $stmt->bind_param('sss',$n2,$e2,$hash2); $stmt->execute();
    }
    // produk
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

// initialize db + demo data if user requested setup
ensure_setup_tables();
if(isset($_GET['action']) && $_GET['action']==='setup'){
    create_demo_data();
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// ------------------- CART helpers (session) -------------------
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
function cart_items(){ return $_SESSION['cart']; }
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

// -------------------- ROUTING: simple 'page' param --------------------
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$errors = [];
$info = null;

// handle common POST actions
if($_SERVER['REQUEST_METHOD']==='POST'){
    // REGISTER
    if(isset($_POST['form']) && $_POST['form']==='register'){
        $name = trim($_POST['name']); $email = trim($_POST['email']); $pass = $_POST['password'];
        if(!$name||!$email||!$pass) $errors[]='Lengkapi semua field registrasi.';
        else{
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=?");
            $stmt->bind_param('s',$email); $stmt->execute(); $r=$stmt->get_result();
            if($r->num_rows>0) $errors[]='Email sudah terdaftar.';
            else{
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $ins = $mysqli->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)");
                $ins->bind_param('sss',$name,$email,$hash);
                if($ins->execute()){ $info='Registrasi berhasil. Silakan login.'; $page='login'; }
                else $errors[]='Gagal registrasi: '.$mysqli->error;
            }
        }
    }
    // LOGIN
    if(isset($_POST['form']) && $_POST['form']==='login'){
        $email = trim($_POST['email']); $pass = $_POST['password'];
        if(!$email||!$pass) $errors[]='Lengkapi email & password.';
        else{
            $stmt = $mysqli->prepare("SELECT id,password FROM users WHERE email=?");
            $stmt->bind_param('s',$email); $stmt->execute(); $r=$stmt->get_result();
            if($r->num_rows===1){
                $u=$r->fetch_assoc();
                if(password_verify($pass,$u['password'])){
                    $_SESSION['user_id'] = $u['id'];
                    header('Location: '.$_SERVER['PHP_SELF']); exit;
                } else $errors[]='Email atau password salah.';
            } else $errors[]='Email atau password salah.';
        }
    }
    // ADD TO CART (from home)
    if(isset($_POST['form']) && $_POST['form']==='add_cart'){
        $pid = intval($_POST['product_id']); $qty = intval($_POST['qty']);
        // cek stok
        $stmt = $mysqli->prepare("SELECT stock FROM products WHERE id=?");
        $stmt->bind_param('i',$pid); $stmt->execute(); $res=$stmt->get_result();
        if($res->num_rows===1){
            $p=$res->fetch_assoc();
            if($qty <= 0) $errors[]='Jumlah minimal 1.';
            elseif($qty > $p['stock']) $errors[]='Stok tidak cukup.';
            else{ add_to_cart($pid,$qty); $info='Produk ditambahkan ke keranjang.'; $page='cart'; }
        } else $errors[]='Produk tidak ditemukan.';
    }
    // UPDATE CART
    if(isset($_POST['form']) && $_POST['form']==='update_cart'){
        if(isset($_POST['qty']) && is_array($_POST['qty'])){
            foreach($_POST['qty'] as $pid=>$q){
                update_cart(intval($pid), intval($q));
            }
            $info='Keranjang diperbarui.';
            $page='cart';
        }
    }
    // CHECKOUT
    if(isset($_POST['form']) && $_POST['form']==='checkout'){
        if(!is_logged_in()){ header('Location: '.$_SERVER['PHP_SELF'].'?page=login'); exit; }
        $summary = cart_summary();
        if(empty($summary['items'])){ $errors[]='Keranjang kosong.'; $page='cart'; }
        else{
            $mysqli->begin_transaction();
            try{
                $user = current_user();
                $total = $summary['total'];
                $ins = $mysqli->prepare("INSERT INTO orders (user_id,total,status) VALUES (?,?,?)");
                $status = 'paid'; // simulasi langsung paid
                $ins->bind_param('ids',$user['id'],$total,$status);
                $ins->execute();
                $order_id = $ins->insert_id;
                $ins2 = $mysqli->prepare("INSERT INTO order_items (order_id,product_id,qty,price) VALUES (?,?,?,?)");
                foreach($summary['items'] as $it){
                    $p = $it['product']; $qty = $it['qty'];
                    // cek stok ulang
                    $stmt = $mysqli->prepare("SELECT stock FROM products WHERE id=? FOR UPDATE");
                    $stmt->bind_param('i',$p['id']); $stmt->execute(); $r=$stmt->get_result(); $row=$r->fetch_assoc();
                    if($row['stock'] < $qty) throw new Exception('Stok tidak cukup untuk '.$p['name']);
                    $ins2->bind_param('iiid',$order_id,$p['id'],$qty,$p['price']); $ins2->execute();
                    $upd = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $upd->bind_param('ii',$qty,$p['id']); $upd->execute();
                }
                $mysqli->commit();
                $_SESSION['cart'] = [];
                $info = 'Pembayaran berhasil. Pesanan dibuat.';
                $page = 'orders';
            } catch(Exception $e){
                $mysqli->rollback();
                $errors[] = 'Gagal saat checkout: '.$e->getMessage();
            }
        }
    }
    // ADMIN: add product
    if(isset($_POST['form']) && $_POST['form']==='admin_add'){
        $u = current_user();
        if(!$u || $u['is_admin']!=1) { $errors[]='Akses ditolak.'; }
        else{
            $name = trim($_POST['name']); $desc = trim($_POST['description']); $price = floatval($_POST['price']); $stock = intval($_POST['stock']);
            if(!$name) $errors[]='Nama produk wajib.';
            else{
                $ins = $mysqli->prepare("INSERT INTO products (name,description,price,stock) VALUES (?,?,?,?)");
                $ins->bind_param('ssdi',$name,$desc,$price,$stock);
                if($ins->execute()){ $info='Produk berhasil ditambahkan.'; $page='admin'; }
                else $errors[]='Gagal menambah produk: '.$mysqli->error;
            }
        }
    }
    // ADMIN: edit product
    if(isset($_POST['form']) && $_POST['form']==='admin_edit'){
        $u = current_user();
        if(!$u || $u['is_admin']!=1) { $errors[]='Akses ditolak.'; }
        else{
            $id = intval($_POST['id']); $name = trim($_POST['name']); $desc = trim($_POST['description']); $price = floatval($_POST['price']); $stock = intval($_POST['stock']);
            $upd = $mysqli->prepare("UPDATE products SET name=?,description=?,price=?,stock=? WHERE id=?");
            $upd->bind_param('ssdii',$name,$desc,$price,$stock,$id);
            if($upd->execute()){ $info='Produk berhasil diperbarui.'; $page='admin'; }
            else $errors[]='Gagal mengubah produk: '.$mysqli->error;
        }
    }
}

// handle GET actions
if(isset($_GET['do']) && $_GET['do']==='logout'){
    session_unset(); session_destroy();
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
if(isset($_GET['do']) && $_GET['do']==='add' && isset($_GET['id'])){
    $pid = intval($_GET['id']); $qty = isset($_GET['qty'])? intval($_GET['qty']):1;
    // cek stok
    $stmt = $mysqli->prepare("SELECT stock FROM products WHERE id=?");
    $stmt->bind_param('i',$pid); $stmt->execute(); $res = $stmt->get_result();
    if($res->num_rows===1){
        $p=$res->fetch_assoc();
        if($qty > 0 && $qty <= $p['stock']){
            add_to_cart($pid,$qty);
            header('Location: '.$_SERVER['PHP_SELF'].'?page=cart'); exit;
        } else { $errors[]='Stok tidak cukup atau jumlah salah.'; }
    } else $errors[]='Produk tidak ditemukan.';
}
if(isset($_GET['do']) && $_GET['do']==='delete_product' && isset($_GET['id'])){
    $u = current_user();
    if(!$u || $u['is_admin']!=1){ $errors[]='Akses ditolak.'; }
    else{
        $id = intval($_GET['id']);
        $stmt = $mysqli->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param('i',$id); $stmt->execute();
        header('Location: '.$_SERVER['PHP_SELF'].'?page=admin'); exit;
    }
}


?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Apotek Online</title>
<style>
:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --accent: #7c3aed;
  --green: #059669;
  --red: #dc2626;
  --orange: #ea580c;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  color: var(--gray-800);
  line-height: 1.6;
}

/* Header */
.header {
  background-color: #f8f9ff;
  padding: 10px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo {
  height: 40px;
  width: auto;
}

h1 {
  font-size: 1.8em;
  color: #6a4ef3;
  margin: 0;
  font-weight: bold;
}

.header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--gray-200);
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--shadow-sm);
}

.header .wrap {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 1rem;
}

.header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.header nav {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.header nav a {
  color: var(--gray-600);
  text-decoration: none;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  position: relative;
}

.header nav a:hover {
  color: var(--primary);
  background: var(--gray-50);
}

.header nav a.cart-link {
  background: var(--primary);
  color: white;
  border-radius: 2rem;
  padding: 0.5rem 1rem;
}

.header nav a.cart-link:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

/* Container */
.container {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 0 1rem;
}

/* Auth Pages */
.auth-container {
  min-height: calc(100vh - 200px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 0;
}

.auth-card {
  background: white;
  border-radius: 1rem;
  box-shadow: var(--shadow-xl);
  padding: 2.5rem;
  width: 100%;
  max-width: 400px;
  border: 1px solid var(--gray-200);
}

.auth-header {
  text-align: center;
  margin-bottom: 2rem;
}

.auth-header h2 {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--gray-900);
  margin-bottom: 0.5rem;
}

.auth-header p {
  color: var(--gray-500);
  font-size: 0.875rem;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.form-input {
  padding: 0.75rem 1rem;
  border: 2px solid var(--gray-200);
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: all 0.2s ease;
  background: white;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input::placeholder {
  color: var(--gray-400);
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-lg);
}

.btn-secondary {
  background: var(--gray-100);
  color: var(--gray-700);
  border: 1px solid var(--gray-200);
}

.btn-secondary:hover {
  background: var(--gray-200);
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

/* Alerts */
.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
  border: 1px solid;
  font-weight: 500;
}

.alert.err {
  background: #fef2f2;
  color: var(--red);
  border-color: #fecaca;
}

.alert.info {
  background: #f0fdf4;
  color: var(--green);
  border-color: #bbf7d0;
}

/* Homepage */
.hero-section {
  background: white;
  border-radius: 1rem;
  padding: 3rem 2rem;
  text-align: center;
  margin-bottom: 2rem;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--gray-200);
}

.hero-section h1 {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--gray-900);
  margin-bottom: 1rem;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-section p {
  font-size: 1.125rem;
  color: var(--gray-600);
  max-width: 600px;
  margin: 0 auto 2rem;
}

.hero-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary);
}

.stat-label {
  color: var(--gray-500);
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

/* Products Grid */
.products-section {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--gray-200);
}

.products-section h2 {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--gray-900);
  margin-bottom: 1.5rem;
  text-align: center;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.product-card {
  background: white;
  border: 1px solid var(--gray-200);
  border-radius: 0.75rem;
  padding: 1.5rem;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary);
}

.product-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary), var(--accent));
}

.product-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin-bottom: 0.5rem;
}

.product-description {
  color: var(--gray-500);
  font-size: 0.875rem;
  margin-bottom: 1rem;
  line-height: 1.5;
}

.product-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 0.5rem;
}

.product-stock {
  color: var(--gray-500);
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.product-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.quantity-input {
  width: 80px;
  padding: 0.5rem;
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
  text-align: center;
}

.quick-add-btn {
  background: var(--gray-100);
  color: var(--gray-600);
  border: 1px solid var(--gray-200);
  padding: 0.5rem;
  border-radius: 0.375rem;
  text-decoration: none;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.quick-add-btn:hover {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

/* Tables */
.table-container {
  background: white;
  border-radius: 0.75rem;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--gray-200);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background: var(--gray-50);
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--gray-700);
  border-bottom: 1px solid var(--gray-200);
}

.table td {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  color: var(--gray-600);
}

.table tr:hover {
  background: var(--gray-50);
}

/* Footer */
.footer {
  background: white;
  border-top: 1px solid var(--gray-200);
  padding: 2rem 0;
  margin-top: 3rem;
  text-align: center;
  color: var(--gray-500);
}

/* Responsive */
@media (max-width: 768px) {
  .header .wrap {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header nav {
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .hero-section {
    padding: 2rem 1rem;
  }
  
  .hero-section h1 {
    font-size: 2rem;
  }
  
  .products-grid {
    grid-template-columns: 1fr;
  }
  
  .auth-card {
    margin: 1rem;
    padding: 2rem;
  }
  
  .hero-stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 0.5rem;
  }
  
  .auth-card {
    padding: 1.5rem;
  }
  
  .hero-stats {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
<header class="header">
  <div class="wrap">
    <div class="logo-container">
      <img src="apotek.png" alt="Logo Apotek Online" class="logo">
      <h1>Apotek Maju Sehat</h1>
    <nav>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Beranda</a>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=cart" class="cart-link">🛒 Keranjang (<?php echo array_sum($_SESSION['cart']); ?>)</a>
      <?php if(is_logged_in()): $u=current_user(); ?>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=orders">Pesanan</a>
        <?php if($u && $u['is_admin']==1): ?>
          <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin">Admin</a>
        <?php endif; ?>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?do=logout">Logout (<?php echo esc($u['name']); ?>)</a>
      <?php else: ?>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=login">Login</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=register">Register</a>
      <?php endif; ?> 
    </nav>
  </div>
</header>

<main class="container">
  <?php if(!empty($errors)): foreach($errors as $e): ?>
    <div class="alert err"><?php echo esc($e); ?></div>
  <?php endforeach; endif; ?>
  <?php if($info): ?><div class="alert info"><?php echo esc($info); ?></div><?php endif; ?>

  <?php
  // ---------- PAGE: HOME ----------
  if($page==='home'):
    $res = $mysqli->query("SELECT * FROM products ORDER BY id DESC");
    $total_products = $mysqli->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    $total_orders = $mysqli->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    $total_users = $mysqli->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
  ?>
    <!-- Hero Section -->
    <div class="hero-section">
      <h1>Selamat Datang di Apotek Online</h1>
      <p>Dapatkan obat-obatan berkualitas dengan mudah dan aman. Layanan 24/7 untuk kesehatan Anda dan keluarga.</p>
      
      <div class="hero-stats">
        <div class="stat-item">
          <div class="stat-number"><?php echo $total_products; ?></div>
          <div class="stat-label">Produk Tersedia</div>
        </div>
        <div class="stat-item">
          <div class="stat-number"><?php echo $total_orders; ?></div>
          <div class="stat-label">Pesanan Berhasil</div>
        </div>
        <div class="stat-item">
          <div class="stat-number"><?php echo $total_users; ?></div>
          <div class="stat-label">Pelanggan Puas</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">24/7</div>
          <div class="stat-label">Layanan</div>
        </div>
      </div>
    </div>

    <!-- Products Section -->
    <div class="products-section">
      <h2>Produk Obat Terbaru</h2>
      <div class="products-grid">
        <?php while($p=$res->fetch_assoc()): ?>
          <div class="product-card">
            <h3 class="product-name"><?php echo esc($p['name']); ?></h3>
            <p class="product-description"><?php echo esc($p['description']); ?></p>
            <p class="product-price">Rp <?php echo number_format($p['price'],0,',','.'); ?></p>
            <p class="product-stock">📦 Stok: <?php echo intval($p['stock']); ?> unit</p>
            <form method="post">
              <input type="hidden" name="form" value="add_cart">
              <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
              <div class="product-actions">
                <input type="number" name="qty" value="1" min="1" max="<?php echo $p['stock']; ?>" class="quantity-input">
                <button class="btn btn-primary" type="submit">🛒 Tambah ke Keranjang</button>
                <a class="quick-add-btn" href="<?php echo $_SERVER['PHP_SELF']; ?>?do=add&id=<?php echo $p['id']; ?>" title="Tambah 1 ke keranjang">+</a>
              </div>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    </div>

  <?php
  // ---------- PAGE: REGISTER ----------
  elseif($page==='register'):
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

  <?php
  // ---------- PAGE: LOGIN ----------
  elseif($page==='login'):
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

  <?php
  // ---------- PAGE: CART ----------
  elseif($page==='cart'):
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

  <?php
  // ---------- PAGE: CHECKOUT ----------
  elseif($page==='checkout'):
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
  // ---------- PAGE: ORDERS ----------
  elseif($page==='orders'):
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
  <?php endif; // end orders ?>

  <?php
  // ---------- PAGE: ADMIN ----------
  if($page==='admin'):
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
    <?php endif; // end admin ?>
<?php
  // ---------- PAGE: ADMIN EDIT ----------
  elseif($page==='admin_edit'):
    $u = current_user();
    if(!$u || $u['is_admin']!=1):
  ?>
      <div class="products-section">
        <div style="text-align: center; padding: 3rem 1rem;">
          <div style="font-size: 4rem; margin-bottom: 1rem;">🚫</div>
          <h3 style="color: var(--red); margin-bottom: 1rem;">Akses Ditolak</h3>
          <p style="color: var(--gray-500); margin-bottom: 2rem;">Anda tidak memiliki izin untuk mengakses halaman ini</p>
          <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin" class="btn btn-primary">⚙️ Kembali ke Admin</a>
        </div>
      </div>
  <?php
    else:
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $p = $res->fetch_assoc();
      if(!$p):
  ?>
        <div class="products-section">
          <div style="text-align: center; padding: 3rem 1rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">❌</div>
            <h3 style="color: var(--red); margin-bottom: 1rem;">Produk Tidak Ditemukan</h3>
            <p style="color: var(--gray-500); margin-bottom: 2rem;">Produk yang Anda cari tidak ditemukan atau sudah dihapus</p>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin" class="btn btn-primary">⚙️ Kembali ke Admin</a>
          </div>
        </div>
      <?php else: ?>
        <div class="products-section">
          <h2>✏️ Edit Produk #<?php echo $p['id']; ?></h2>
          
          <div style="max-width: 600px; margin: 2rem auto;">
            <form method="post" style="background: var(--gray-50); padding: 2rem; border-radius: 0.75rem; border: 1px solid var(--gray-200);">
              <input type="hidden" name="form" value="admin_update">
                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                    
              <div class="form-group">
                <label for="name">📦 Nama Produk</label>
                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                    </div>

              <div class="form-group">
                <label for="description">📝 Deskripsi</label>
                <textarea id="description" name="description" class="form-input" rows="4"><?php echo htmlspecialchars($p['description']); ?></textarea>
                    </div>

              <div class="form-group">
                <label for="price">💰 Harga (Rp)</label>
                <input type="number" id="price" name="price" step="0.01" class="form-input" value="<?php echo $p['price']; ?>" required>
                    </div>

              <div class="form-group">
                <label for="stock">📊 Stok</label>
                <input type="number" id="stock" name="stock" class="form-input" value="<?php echo $p['stock']; ?>" required>
                    </div>

              <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Update Produk</button>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=admin" class="btn btn-secondary">❌ Batal</a>
                    </div>
                </form>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; // end admin_edit ?>
  <?php endif; // end main page structure ?>

<footer class="footer">
<p>@LINTAN AMALLIYAH PUTRI</p>
</footer>

</body>
</html>