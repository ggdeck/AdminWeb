<?php
session_start();

// Logout hanya admin, pertahankan session siswa jika ada
if (isset($_SESSION['admin_login'])) {
    // Simpan data siswa jika ada
    $siswa_data = [];
    if (isset($_SESSION['siswa_login'])) {
        $siswa_data = [
            'siswa_login' => $_SESSION['siswa_login'],
            'siswa_nama' => $_SESSION['siswa_nama'],
            'siswa_id' => $_SESSION['siswa_id'],
            'siswa_username' => $_SESSION['siswa_username']
        ];
    }
    
    // Hapus semua session
    session_unset();
    session_destroy();
    
    // Start session baru dan restore siswa jika ada
    if (!empty($siswa_data)) {
        session_start();
        $_SESSION['siswa_login'] = $siswa_data['siswa_login'];
        $_SESSION['siswa_nama'] = $siswa_data['siswa_nama'];
        $_SESSION['siswa_id'] = $siswa_data['siswa_id'];
        $_SESSION['siswa_username'] = $siswa_data['siswa_username'];
        $_SESSION['user_type'] = 'siswa';
    }
}

// Response for AJAX
if (isset($_POST) && !empty($_POST)) {
    echo json_encode(['status' => 'success', 'message' => 'Admin berhasil logout']);
    exit;
}

// Redirect jika akses langsung
header("Location: login.php");
exit;
?>