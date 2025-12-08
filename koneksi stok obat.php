<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "apotek_db";

// Membuat koneksi ke MySQL
$koneksi = new mysqli($servername, $username, $password, $database);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
} else {
    echo "Koneksi berhasil ke database!";
}
?>
