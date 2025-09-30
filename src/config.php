<?php
$host = getenv('DB_HOST') ?: 'mysql.railway.internal';
$db   = getenv('DB_DATABASE') ?: 'railway';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'mtUTprOhWarfnJdwcDbbMKTozkcNyrln'; // isi lengkap dari MYSQLPASSWORD
$port = getenv('DB_PORT') ?: '3306';

$konek = new mysqli($host, $user, $pass, $db, $port);

if ($konek->connect_error) {
    die("❌ Koneksi gagal: " . $konek->connect_error);
} else {
    // echo "✅ Koneksi berhasil!";
}
?>
