<?php
// Salin file ini menjadi config.php, lalu isi sesuai environment kamu.
// config.php TIDAK di-upload ke GitHub (lihat .gitignore) karena berisi kredensial database.

$host = 'localhost';
$dbname = 'php_preUtix';
$username = 'root';       // ganti sesuai username MySQL kamu
$password = '';           // ganti sesuai password MySQL kamu

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
