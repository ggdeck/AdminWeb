<?php
session_start();

// Logout hanya untuk siswa, admin tetap login
if (isset($_SESSION['siswa_login'])) {
    unset($_SESSION['siswa_login']);
    unset($_SESSION['siswa_nama']);
    unset($_SESSION['siswa_id']);
    unset($_SESSION['siswa_username']);
    
    // Jika dipanggil via AJAX
    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        echo json_encode(['status' => 'success', 'message' => 'Siswa berhasil logout']);
        exit;
    }
}

// Redirect ke halaman login
header("Location: index.php");
exit;
?>