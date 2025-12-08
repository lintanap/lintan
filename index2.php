<?php
// Main Application File - Apotek Online
// --------------------------------------------------------

// Start session
session_start();

// Include database connection and helper functions
require_once 'includes/database.php';

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

// Include header
include 'includes/header.php';

// Include page content based on routing
switch($page) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'cart':
        include 'pages/cart.php';
        break;
    case 'checkout':
        include 'pages/checkout.php';
        break;
    case 'orders':
        include 'pages/orders.php';
        break;
    case 'admin':
        include 'pages/admin.php';
        break;
    case 'admin_edit':
        include 'pages/admin_edit.php';
        break;
    default:
        include 'pages/home.php';
        break;
}

// Include footer
include 'includes/footer.php';
?>