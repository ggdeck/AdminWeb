<?php
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_DATABASE') ?: 'e-voting'; // Sesuaikan dengan nama database kamu
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$port = getenv('DB_PORT') ?: '3306';

$konek = new mysqli($host, $user, $pass, $db, $port);

if ($konek->connect_error) {
    die("❌ Koneksi gagal: " . $konek->connect_error);
} else {
    // echo "✅ Koneksi berhasil!";
}
?>
