<?php
session_start();

// Cek apakah siswa sudah login
if (!isset($_SESSION['siswa_id'])) {
    header("Location: login.php");
    exit;
}

// Include koneksi database
include 'config.php';

// Proses logout - modifikasi untuk mempertahankan session admin
if (isset($_GET['logout'])) {
    // Hanya hapus session siswa, biarkan admin tetap login
    if (isset($_SESSION['admin_login'])) {
        // Simpan data admin
        $admin_login = $_SESSION['admin_login'];
        $admin_nama = $_SESSION['admin_nama'];
        $admin_id = $_SESSION['admin_id'];
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start session baru dan restore admin
        session_start();
        $_SESSION['admin_login'] = $admin_login;
        $_SESSION['admin_nama'] = $admin_nama;
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['user_type'] = 'admin';
    } else {
        // Jika tidak ada admin, destroy semua
        session_unset();
        session_destroy();
    }
    
    header("Location: login.php");
    exit;
}

// Quick logout untuk siswa saja (AJAX)
if (isset($_POST['quick_logout']) && $_POST['quick_logout'] == 'siswa') {
    // Hapus hanya session siswa
    unset($_SESSION['siswa_login']);
    unset($_SESSION['siswa_nama']);
    unset($_SESSION['siswa_id']);
    unset($_SESSION['siswa_username']);
    
    echo json_encode(['status' => 'success', 'message' => 'Siswa berhasil logout']);
    exit;
}

// Ambil data siswa
$siswa_nama = $_SESSION['siswa_nama'];
$siswa_nis = $_SESSION['siswa_login'];
$siswa_id = $_SESSION['siswa_id'];

// Cek status voting siswa langsung dari tabel pemilih
$sql_status = "SELECT status_voting FROM pemilih WHERE id = ?";
$stmt_status = $konek->prepare($sql_status);
$stmt_status->bind_param("i", $siswa_id);
$stmt_status->execute();
$result_status = $stmt_status->get_result();
$status_voting_db = $result_status->fetch_assoc()['status_voting'];
// Untuk varchar: '' atau NULL = belum, ada nilai = sudah
$status_voting = (!empty($status_voting_db) && $status_voting_db !== '') ? 'sudah' : 'belum';

// Ambil data kandidat untuk voting
$sql_kandidat = "SELECT 
                    id_kandidat, 
                    nama_ketua, 
                    nama_wakil, 
                    kelas, 
                    visi, 
                    misi,
                    COALESCE(nomor_urut, 0) as nomor_urut,
                    gambar
                FROM kandidat 
                ORDER BY nomor_urut ASC, id_kandidat ASC";
$stmt_kandidat = $konek->prepare($sql_kandidat);
$stmt_kandidat->execute();
$result_kandidat = $stmt_kandidat->get_result();
$kandidat_list = [];
while ($row = $result_kandidat->fetch_assoc()) {
    $kandidat_list[] = $row;
}

// Ambil statistik
$sql_total_suara = "SELECT COUNT(*) as total FROM suara";
$stmt_total_suara = $konek->prepare($sql_total_suara);
$stmt_total_suara->execute();
$result_total_suara = $stmt_total_suara->get_result();
$total_suara = $result_total_suara->fetch_assoc()['total'];

$sql_total_pemilih = "SELECT COUNT(*) as total FROM pemilih";
$stmt_total_pemilih = $konek->prepare($sql_total_pemilih);
$stmt_total_pemilih->execute();
$result_total_pemilih = $stmt_total_pemilih->get_result();
$total_pemilih = $result_total_pemilih->fetch_assoc()['total'];

$partisipasi = $total_pemilih > 0 ? round(($total_suara / $total_pemilih) * 100, 1) : 0;

// Proses voting langsung saat tombol kandidat diklik
$message = '';
$message_type = '';

if (isset($_POST['pilih_kandidat']) && $status_voting == 'belum') {
    try {
        $kandidat_pilihan = $_POST['kandidat_id'];
        
        // Mulai transaksi untuk memastikan konsistensi data
        $konek->begin_transaction();
        
        // Insert vote langsung
        $sql_vote = "INSERT INTO suara (pemilih_id, kandidat_id) VALUES (?, ?)";
        $stmt_vote = $konek->prepare($sql_vote);
        $stmt_vote->bind_param("ii", $siswa_id, $kandidat_pilihan);
        
        // Update status voting di tabel pemilih - set ke 'voted' untuk varchar
        $sql_update_status = "UPDATE pemilih SET status_voting = 'voted' WHERE id = ?";
        $stmt_update_status = $konek->prepare($sql_update_status);
        $stmt_update_status->bind_param("i", $siswa_id);
        
        if ($stmt_vote->execute() && $stmt_update_status->execute()) {
            $konek->commit();
            $message = "Terima kasih! Suara Anda berhasil disimpan.";
            $message_type = "success";
            $status_voting = 'sudah'; // Update status
            
            // Update statistik setelah voting
            $sql_total_suara = "SELECT COUNT(*) as total FROM suara";
            $stmt_total_suara = $konek->prepare($sql_total_suara);
            $stmt_total_suara->execute();
            $result_total_suara = $stmt_total_suara->get_result();
            $total_suara = $result_total_suara->fetch_assoc()['total'];
            $partisipasi = $total_pemilih > 0 ? round(($total_suara / $total_pemilih) * 100, 1) : 0;
        } else {
            $konek->rollback();
            $message = "Terjadi kesalahan saat menyimpan suara.";
            $message_type = "error";
        }
        
    } catch(Exception $e) {
        $konek->rollback();
        $message = "Terjadi kesalahan: " . $e->getMessage();
        $message_type = "error";
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #f3f4f6 100%);
            color: #1f2a44;
            min-height: 100vh;
        }

        .header {
            background: #1e40af;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            width: 50px;
            height: 50px;
        }

        .logo-text h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .logo-text p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info .welcome {
            text-align: right;
        }

        .user-info .welcome h3 {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .user-info .welcome p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .logout-buttons {
            display: flex;
            gap: 10px;
            flex-direction: column;
        }

        .logout-btn {
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            text-align: center;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .quick-logout-btn {
            background: rgba(255, 193, 7, 0.8);
            color: #333;
            border: 1px solid rgba(255, 193, 7, 1);
        }

        .quick-logout-btn:hover {
            background: rgba(255, 193, 7, 1);
            color: #000;
        }

        .admin-indicator {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
            color: #fff;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            text-align: center;
            margin-bottom: 10px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, #1e40af, #3b82f6);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #1e40af;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2a44;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .voting-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 20px;
            text-align: center;
        }

        .voting-status {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }

        .voting-status.sudah {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .voting-status.belum {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .candidate-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 420px;
        }

        .candidate-card:hover {
            border-color: #3b82f6;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .candidate-card.selected {
            border-color: #1e40af;
            background: #f0f4ff;
        }

        .candidate-number {
            position: absolute;
            top: -10px;
            left: 20px;
            background: #1e40af;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .candidate-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #f3f4f6;
            margin: 10px auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 2.5rem;
            overflow: hidden;
        }

        .candidate-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .candidate-name {
            margin-bottom: 15px;
        }

        .candidate-name h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 5px;
        }

        .candidate-name p {
            color: #6b7280;
            font-size: 0.85rem;
            margin-bottom: 3px;
        }

        .candidate-details {
            text-align: left;
            margin: 15px 0;
            flex-grow: 1;
            overflow: hidden;
        }

        .candidate-details h4 {
            color: #1f2a44;
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .candidate-details p {
            color: #6b7280;
            font-size: 0.8rem;
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .detail-button {
            width: 100%;
            padding: 10px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: auto;
        }

        .detail-button:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .vote-button-candidate {
            width: 100%;
            padding: 10px 16px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: auto;
        }

        .vote-button-candidate:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .vote-disabled {
            width: 100%;
            padding: 10px 16px;
            background: #9ca3af;
            color: white;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: auto;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .auto-logout-timer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 193, 7, 0.95);
            color: #333;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 12px;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 193, 7, 1);
            z-index: 1000;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .user-info {
                flex-direction: column;
                gap: 10px;
            }

            .logout-buttons {
                flex-direction: row;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 20px 15px;
            }

            .candidate-card {
                height: auto;
                min-height: 380px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="images/image.png" alt="Logo">
                <div class="logo-text">
                    <h1>E-Voting OSIS</h1>
                    <p>SMPN 6 Cibitung</p>
                </div>
            </div>
            <div class="user-info">
                <div class="welcome">
                    <h3>Selamat Datang, <?php echo htmlspecialchars($siswa_nama, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>NIS: <?php echo htmlspecialchars($siswa_nis, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="logout-buttons">
                    <a href="?logout=true" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($message_type == 'success'): ?>
                    <script>
                        // Auto logout setelah voting berhasil
                        setTimeout(() => {
                            quickLogout();
                        }, 3000);
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo count($kandidat_list); ?></h3>
                <p>Kandidat</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-vote-yea"></i>
                <h3><?php echo $total_suara; ?></h3>
                <p>Suara Masuk</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-percentage"></i>
                <h3><?php echo $partisipasi; ?>%</h3>
                <p>Partisipasi</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-<?php echo $status_voting == 'sudah' ? 'check-circle' : 'clock'; ?>"></i>
                <h3><?php echo ucfirst($status_voting); ?></h3>
                <p>Status Voting</p>
            </div>
        </div>

        <div class="voting-section">
            <h2 class="section-title">Pilih Kandidat Ketua OSIS</h2>
            
            <div class="voting-status <?php echo $status_voting; ?>">
                <?php if ($status_voting == 'sudah'): ?>
                    <i class="fas fa-check-circle"></i>
                    Anda sudah memberikan suara. Terima kasih atas partisipasi Anda!
                <?php else: ?>
                    <i class="fas fa-vote-yea"></i>
                    Silakan klik tombol "LIHAT DETAIL" pada kandidat yang Anda inginkan untuk melihat profil lengkap.
                <?php endif; ?>
            </div>

            <div class="candidates-grid">
                <?php if (count($kandidat_list) > 0): ?>
                    <?php foreach ($kandidat_list as $kandidat): ?>
                        <div class="candidate-card">
                            <?php if($kandidat['nomor_urut'] > 0): ?>
                                <div class="candidate-number"><?php echo $kandidat['nomor_urut']; ?></div>
                            <?php endif; ?>
                            
                            <div class="candidate-photo">
                                <?php if (!empty($kandidat['gambar']) && file_exists("uploads/" . $kandidat['gambar'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($kandidat['gambar'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="Foto Kandidat">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="candidate-name">
                                <h3><?php echo htmlspecialchars($kandidat['nama_ketua'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php if (!empty($kandidat['nama_wakil'])): ?>
                                    <p>Wakil: <?php echo htmlspecialchars($kandidat['nama_wakil'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <p>Kelas: <?php echo htmlspecialchars($kandidat['kelas'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            
                            <?php if (!empty($kandidat['visi']) || !empty($kandidat['misi'])): ?>
                            <div class="candidate-details">
                                <?php if (!empty($kandidat['visi'])): ?>
                                    <h4>Visi:</h4>
                                    <p><?php echo htmlspecialchars($kandidat['visi'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($kandidat['misi'])): ?>
                                    <h4>Misi:</h4>
                                    <p><?php echo htmlspecialchars($kandidat['misi'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($status_voting == 'sudah'): ?>
                                <div class="vote-disabled">
                                    <i class="fas fa-check-circle"></i> Voting Selesai
                                </div>
                            <?php else: ?>
                                <a href="detail-kandidat.php?id=<?php echo $kandidat['id_kandidat']; ?>" class="detail-button">
                                    <i class="fas fa-eye"></i> LIHAT DETAIL
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="candidate-card" style="text-align: center; color: #6b7280;">
                        <i class="fas fa-users fa-3x" style="margin-bottom: 15px;"></i>
                        <h3>Belum Ada Kandidat</h3>
                        <p>Kandidat akan ditampilkan saat sudah tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Quick logout function - hanya menghapus session siswa
        function quickLogout() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'quick_logout=siswa'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success message
                    const message = document.createElement('div');
                    message.className = 'alert alert-success';
                    message.innerHTML = '<i class="fas fa-check-circle"></i> Logout berhasil! Siswa berikutnya bisa login...';
                    message.style.position = 'fixed';
                    message.style.top = '20px';
                    message.style.right = '20px';
                    message.style.zIndex = '9999';
                    document.body.appendChild(message);
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback to regular logout
                window.location.href = '?logout=true';
            });
        }

        // Auto-logout setelah 3 menit tidak ada aktivitas
        let inactivityTime = function () {
            let time;
            let countdownTime = 180; // 3 minutes in seconds
            let timerElement = document.getElementById('logoutTimer');
            let countdownElement = document.getElementById('countdown');
            let isCountdownActive = false;

            // Reset timer
            function resetTimer() {
                clearTimeout(time);
                if (isCountdownActive) {
                    countdownTime = 180;
                    timerElement.style.display = 'none';
                    isCountdownActive = false;
                }
                
                time = setTimeout(() => {
                    // Show countdown for last 30 seconds
                    isCountdownActive = true;
                    countdownTime = 30;
                    timerElement.style.display = 'block';
                    
                    let countdownInterval = setInterval(() => {
                        countdownTime--;
                        countdownElement.textContent = countdownTime;
                        
                        if (countdownTime <= 0) {
                            clearInterval(countdownInterval);
                            quickLogout();
                        }
                    }, 1000);
                }, 150000); // Start countdown after 2.5 minutes (150 seconds)
            }

            // Events that reset the timer
            window.onmousemove = resetTimer;
            window.onmousedown = resetTimer;
            window.ontouchstart = resetTimer;
            window.onclick = resetTimer;
            window.onkeydown = resetTimer;
            window.addEventListener('scroll', resetTimer, true);

            // Start the timer
            resetTimer();
        };

        // Initialize inactivity timer only if not voted yet
        <?php if ($status_voting != 'sudah'): ?>
        inactivityTime();
        <?php endif; ?>

        // Confirm voting function
        function confirmVote(namaKandidat) {
            return confirm('Apakah Anda yakin memilih "' + namaKandidat + '"?\n\nSetelah memilih, Anda tidak dapat mengubah pilihan lagi.');
        }

        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (!alert.innerHTML.includes('berhasil disimpan')) { // Don't auto-hide success voting message
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }
            });
        }, 5000);

        // Show notification if admin is still logged in
        <?php if (isset($_SESSION['admin_login'])): ?>
        console.log('Admin masih login: <?php echo $_SESSION["admin_nama"]; ?>');
        <?php endif; ?>
    </script>
</body>
</html>