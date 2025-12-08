<?php
// ==================== KONEKSI DATABASE ====================
// Koneksi tanpa pilih database dulu
$koneksi = new mysqli("localhost", "root", "");

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Buat database jika belum ada
$koneksi->query("CREATE DATABASE IF NOT EXISTS apotek_db");

// Pilih database yang baru dibuat
$koneksi->select_db("apotek_db");

// Buat tabel obat jika belum ada
$koneksi->query("CREATE TABLE IF NOT EXISTS obat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_obat VARCHAR(100),
    kategori VARCHAR(50),
    stok INT,
    harga DECIMAL(10,2)
)");

// ==================== TAMBAH DATA ====================
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga'];
    $koneksi->query("INSERT INTO obat (nama_obat, kategori, stok, harga) VALUES ('$nama', '$kategori', '$stok', '$harga')");
    header("Location: stokobat.php");
    exit;
}

// ==================== HAPUS DATA ====================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM obat WHERE id=$id");
    header("Location: stokobat.php");
    exit;
}

// ==================== EDIT DATA ====================
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga'];
    $koneksi->query("UPDATE obat SET nama_obat='$nama', kategori='$kategori', stok='$stok', harga='$harga' WHERE id=$id");
    header("Location: stokobat.php");
    exit;
}

// ==================== AMBIL DATA OBAT ====================
$obat = $koneksi->query("SELECT * FROM obat");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Stok Obat</title>
    <style>
        body {font-family: Arial, sans-serif; margin: 40px; background: #f4f6f7;}
        h2 {text-align: center; color: #2c3e50;}
        form {background: #fff; padding: 20px; border-radius: 10px; width: 450px; margin: 20px auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
        input, select {width: 100%; padding: 8px; margin: 6px 0; border-radius: 5px; border: 1px solid #ccc;}
        button {background: #27ae60; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;}
        table {width: 90%; margin: auto; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
        th, td {border: 1px solid #ddd; padding: 10px; text-align: center;}
        th {background: #27ae60; color: white;}
        a {text-decoration: none; color: #e74c3c;}
        .edit {color: #2980b9;}
        .low-stock {background: #fce4e4;}
    </style>
</head>
<body>

<h2>💊 Aplikasi Stok Obat</h2>

<?php
// ==================== FORM EDIT ====================
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $data = $koneksi->query("SELECT * FROM obat WHERE id=$id")->fetch_assoc();
?>
<form method="post">
    <h3>Edit Data Obat</h3>
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <input type="text" name="nama" value="<?= $data['nama_obat'] ?>" required>
    <select name="kategori" required>
        <option value="Tablet" <?= ($data['kategori']=="Tablet"?"selected":"") ?>>Tablet</option>
        <option value="Kapsul" <?= ($data['kategori']=="Kapsul"?"selected":"") ?>>Kapsul</option>
        <option value="Cair" <?= ($data['kategori']=="Cair"?"selected":"") ?>>Cair</option>
        <option value="Salep" <?= ($data['kategori']=="Salep"?"selected":"") ?>>Salep</option>
    </select>
    <input type="number" name="stok" value="<?= $data['stok'] ?>" required>
    <input type="number" name="harga" step="0.01" value="<?= $data['harga'] ?>" required>
    <button type="submit" name="update">Update</button>
    <a href="stokobat.php"><button type="button">Batal</button></a>
</form>
<?php } else { ?>
<form method="post">
    <h3>Tambah Obat Baru</h3>
    <input type="text" name="nama" placeholder="Nama Obat" required>
    <select name="kategori" required>
        <option value="">-- Pilih Kategori --</option>
        <option value="Tablet">Tablet</option>
        <option value="Kapsul">Kapsul</option>
        <option value="Cair">Cair</option>
        <option value="Salep">Salep</option>
    </select>
    <input type="number" name="stok" placeholder="Jumlah Stok" required>
    <input type="number" step="0.01" name="harga" placeholder="Harga (Rp)" required>
    <button type="submit" name="tambah">Simpan</button>
</form>
<?php } ?>

<!-- ==================== TABEL DATA OBAT ==================== -->
<table>
    <tr>
        <th>No</th>
        <th>Nama Obat</th>
        <th>Kategori</th>
        <th>Stok</th>
        <th>Harga (Rp)</th>
        <th>Aksi</th>
    </tr>
    <?php 
    $no = 1;
    while($row = $obat->fetch_assoc()){ 
        $class = ($row['stok'] <= 5) ? "low-stock" : "";
    ?>
    <tr class="<?= $class ?>">
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['nama_obat']) ?></td>
        <td><?= htmlspecialchars($row['kategori']) ?></td>
        <td><?= htmlspecialchars($row['stok']) ?></td>
        <td><?= number_format($row['harga'], 2, ',', '.') ?></td>
        <td>
            <a class="edit" href="?edit=<?= $row['id'] ?>">Edit</a> | 
            <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
        </td>
    </tr>
    <?php } ?>
</table>

</body>
</html>
