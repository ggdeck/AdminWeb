<?php
session_start();

include 'config.php';

$loginFailed = false;

// Proses login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Clear any existing student session when new login attempt (tapi pertahankan admin)
    if (isset($_SESSION['siswa_login'])) {
        unset($_SESSION['siswa_login']);
        unset($_SESSION['siswa_nama']);
        unset($_SESSION['siswa_id']);
        unset($_SESSION['siswa_username']);
    }

    // Cek login admin terlebih dahulu
    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $konek->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Login sebagai admin
        if (password_verify($password, $row['password'])) {
            // Password sudah di-hash dan cocok
            $_SESSION['admin_login'] = $row['username'];
            $_SESSION['admin_nama']  = $row['nama_lengkap'];
            $_SESSION['admin_id']    = $row['id'];
            $_SESSION['user_type']   = 'admin';
            header("Location: beranda.php");
            exit;
        } elseif ($password === $row['password']) {
            // Password masih plain text dan cocok
            // Update password ke hash format
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateSql = "UPDATE admin SET password = ? WHERE id = ?";
            $updateStmt = $konek->prepare($updateSql);
            $updateStmt->bind_param("si", $hashedPassword, $row['id']);
            $updateStmt->execute();

            // Login berhasil
            $_SESSION['admin_login'] = $row['username'];
            $_SESSION['admin_nama']  = $row['nama_lengkap'];
            $_SESSION['admin_id']    = $row['id'];
            $_SESSION['user_type']   = 'admin';
            header("Location: beranda.php");
            exit;
        }
    }

    // Jika bukan admin, cek login siswa dengan NIS
    $sql_siswa = "SELECT * FROM pemilih WHERE nis = ? AND password = ?";
    $stmt_siswa = $konek->prepare($sql_siswa);
    $stmt_siswa->bind_param("ss", $username, $password);
    $stmt_siswa->execute();
    $result_siswa = $stmt_siswa->get_result();

    if ($row_siswa = $result_siswa->fetch_assoc()) {
        // Login sebagai siswa berhasil
        $_SESSION['siswa_login'] = $row_siswa['nis'];
        $_SESSION['siswa_nama']  = $row_siswa['nama'];
        $_SESSION['siswa_id']    = $row_siswa['id'];
        $_SESSION['siswa_username'] = $row_siswa['username'];
        $_SESSION['user_type']   = 'siswa';
        header("Location: beranda-siswa.php");
        exit;
    } else {
        // Login gagal
        $loginFailed = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PILKETOS | SMPN 6 Cibitung</title>
    <link rel="shortcut icon" type="image/x-icon" href="images/image.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(rgba(30, 43, 188, 0.8), rgba(30, 43, 188, 0.9)), 
                        url('https://www.lesprivatinsan.com/wp-content/uploads/2021/10/les-privat-cibitung-bekasi-smpn-6-1024x768.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        /* Overlay untuk meningkatkan kontras */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 40px 30px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            text-align: left;
            color: #333;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease forwards;
            position: relative;
            z-index: 1;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .shake {
            animation: shakeAnim 0.5s;
        }

        @keyframes shakeAnim {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
            100% { transform: translateX(0); }
        }

        .logo {
            margin-bottom: 25px;
            text-align: center;
        }

        .logo img {
            width: 280px;
            max-width: 80%;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 24px;
            color: #1e2bbc;
        }

        .input-label {
            color: #333;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
            margin-top: 20px;
            display: block;
        }

        .login-container input {
            width: 100%;
            padding: 18px 20px;
            margin: 10px 0;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            outline: none;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            transition: all 0.3s ease;
        }

        .login-container input:focus {
            border-color: rgb(51, 149, 174);
            box-shadow: 0 0 0 3px rgba(51, 149, 174, 0.2);
            transform: translateY(-2px);
        }

        .login-container button {
            width: 100%;
            padding: 18px;
            border-radius: 12px;
            border: none;
            background: rgba(30, 43, 188, 1);
            color: #fff;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(30, 43, 188, 0.3);
        }

        .login-container button:hover {
            background: rgba(45, 62, 252, 1);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30, 43, 188, 0.4);
        }

        .login-container button:active {
            transform: translateY(0);
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 16px;
            text-align: center;
            color: #d32f2f;
            border: 2px solid rgba(255, 0, 0, 0.2);
        }

        .success {
            background: rgba(76, 175, 80, 0.1);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 16px;
            text-align: center;
            color: #2e7d32;
            border: 2px solid rgba(76, 175, 80, 0.2);
        }

        .admin-indicator {
            background: rgba(255, 193, 7, 0.15);
            border: 2px solid rgba(255, 193, 7, 0.3);
            color: #856404;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .admin-logout-btn {
            background: rgba(220, 53, 69, 0.8);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .admin-logout-btn:hover {
            background: rgba(220, 53, 69, 1);
            transform: scale(1.05);
        }

        .quick-switch {
            background: rgba(30, 43, 188, 0.1);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px solid rgba(30, 43, 188, 0.2);
            text-align: center;
        }

        .quick-switch p {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .switch-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .switch-btn {
            flex: 1;
            min-width: 120px;
            padding: 10px 12px;
            background: rgba(30, 43, 188, 0.15);
            color: rgba(30, 43, 188, 1);
            border: 2px solid rgba(30, 43, 188, 0.3);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .switch-btn:hover {
            background: rgba(30, 43, 188, 0.25);
            transform: translateY(-2px);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 20px;
            background: rgba(76, 175, 80, 0.95);
            color: white;
            border-radius: 12px;
            font-size: 16px;
            z-index: 9999;
            transform: translateX(300px);
            transition: transform 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .notification.show {
            transform: translateX(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo img {
                width: 240px;
            }
            
            .login-container h2 {
                font-size: 22px;
            }
            
            .login-container input {
                padding: 16px;
                font-size: 15px;
            }
            
            .login-container button {
                padding: 16px;
                font-size: 16px;
            }
            
            .switch-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <form method="POST" class="login-container <?php if ($loginFailed) echo 'shake'; ?>">
        <script>
            <?php if ($loginFailed): ?>
                const form = document.querySelector('.login-container');
                form.classList.add('shake');
                setTimeout(() => form.classList.remove('shake'), 500);
            <?php endif; ?>
        </script>

        <div class="logo">
            <img src="images/image.png" alt="Logo SMPN 6 Cibitung">
        </div>
        <h2>Pemilihan Ketua OSIS</h2>

        <?php
        if ($loginFailed) {
            echo "<div class='error'>Username/NIS atau Password salah!</div>";
        }

        // Show success message if came from quick logout
        if (isset($_GET['quick_logout']) && $_GET['quick_logout'] == 'success') {
            echo "<div class='success'>Siswa berhasil logout! Silakan login siswa berikutnya.</div>";
        }
        ?>

        <label class="input-label">Username / NIS</label>
        <input type="text" name="username" id="username" autocomplete="off" value="" placeholder="Admin username atau NIS siswa" required>

        <label class="input-label">Password</label>
        <input type="password" name="password" id="password" autocomplete="off" value="" placeholder="Masukkan password" required>

        <button type="submit" name="login">MASUK</button>
        
        <div class="footer-text">
            SMPN 6 Cibitung © <?php echo date('Y'); ?>
        </div>
    </form>

    <script>
        // Function to logout admin
        function logoutAdmin() {
            if (confirm('Yakin ingin logout admin? Anda harus login admin lagi jika ingin mengakses panel admin.')) {
                fetch('logout-admin.php', {
                    method: 'POST'
                }).then(() => {
                    location.reload();
                });
            }
        }

        // Fill demo student credentials (hanya jika admin login)
        function fillDemoStudent(nis, password) {
            document.getElementById('username').value = nis;
            document.getElementById('password').value = password;
        }

        // Auto-clear success message
        setTimeout(() => {
            const successMsg = document.querySelector('.success');
            if (successMsg) {
                successMsg.style.transition = 'opacity 0.5s ease';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 4000);

        // Show notification if redirected from quick logout
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('quick_logout') === 'success') {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.innerHTML = '✅ Siswa berhasil logout! Siap untuk siswa berikutnya.';
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
            
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>
<?php
mysqli_close($konek);
?>