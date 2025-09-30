<?php
session_start();

// Cek apakah siswa sudah login
if (!isset($_SESSION['siswa_id'])) {
    header("Location: login.php");
    exit;
}

// Include koneksi database
include 'config.php';

// Ambil ID kandidat dari parameter URL
$kandidat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($kandidat_id <= 0) {
    header("Location: beranda-siswa.php");
    exit;
}

// Ambil data kandidat
$sql_kandidat = "SELECT id_kandidat, nama_ketua, nama_wakil, kelas, visi, misi, gambar 
                 FROM kandidat 
                 WHERE id_kandidat = ?";
$stmt_kandidat = $konek->prepare($sql_kandidat);
$stmt_kandidat->bind_param("i", $kandidat_id);
$stmt_kandidat->execute();
$result_kandidat = $stmt_kandidat->get_result();

if ($result_kandidat->num_rows === 0) {
    header("Location: beranda-siswa.php");
    exit;
}

$kandidat = $result_kandidat->fetch_assoc();

// Ambil data siswa
$siswa_id = $_SESSION['siswa_id'];
$siswa_nama = $_SESSION['siswa_nama'];
$siswa_nis = $_SESSION['siswa_login'];

// Cek status voting dari tabel voting_config
$is_voting_active = false;
try {
    $sql_voting_status = "SELECT is_active FROM voting_config WHERE id = 1";
    $result_voting = $konek->query($sql_voting_status);
    if ($result_voting && $result_voting->num_rows > 0) {
        $config = $result_voting->fetch_assoc();
        $is_voting_active = (bool)$config['is_active'];
    }
} catch (Exception $e) {
    $is_voting_active = false;
}

// Cek apakah siswa ini sudah memilih
$sudah_memilih = false;
try {
    $sql_check_vote = "SELECT COUNT(*) as sudah_pilih FROM suara WHERE pemilih_id = ?";
    $stmt_check = $konek->prepare($sql_check_vote);
    $stmt_check->bind_param("i", $siswa_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $vote_check = $result_check->fetch_assoc();
    $sudah_memilih = ($vote_check['sudah_pilih'] > 0);
    $result_check->free();
    $stmt_check->close();
} catch (Exception $e) {
    $sudah_memilih = false;
}

// Proses voting
$message = '';
$message_type = '';

if (isset($_POST['pilih_kandidat'])) {
    // Cek ulang kondisi sebelum memproses
    if ($sudah_memilih) {
        $message = "Anda sudah memilih sebelumnya!";
        $message_type = "error";
    } elseif (!$is_voting_active) {
        $message = "Voting belum dimulai atau sudah ditutup!";
        $message_type = "error";
    } else {
        try {
            $konek->begin_transaction();
            
            // Insert vote
            $sql_vote = "INSERT INTO suara (pemilih_id, kandidat_id) VALUES (?, ?)";
            $stmt_vote = $konek->prepare($sql_vote);
            $stmt_vote->bind_param("ii", $siswa_id, $kandidat_id);
            
            // Update status pemilih menjadi active (menandakan sudah memilih)
            $sql_update_status = "UPDATE pemilih SET status_voting = 'active' WHERE id = ?";
            $stmt_update_status = $konek->prepare($sql_update_status);
            $stmt_update_status->bind_param("i", $siswa_id);
            
            if ($stmt_vote->execute() && $stmt_update_status->execute()) {
                $konek->commit();
                $message = "Terima kasih! Suara Anda berhasil disimpan.";
                $message_type = "success";
                $sudah_memilih = true; // Update status lokal
            } else {
                $konek->rollback();
                $message = "Terjadi kesalahan saat menyimpan suara.";
                $message_type = "error";
            }
        } catch (Exception $e) {
            $konek->rollback();
            $message = "Terjadi kesalahan: " . $e->getMessage();
            $message_type = "error";
        }
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

        .logout-btn {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .candidate-detail {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 30px;
            max-width: 900px;
            margin: 0 auto;
        }

        .candidate-image {
            flex: 0 0 250px;
            position: relative;
            overflow: hidden;
        }

        .candidate-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .candidate-image .placeholder {
            width: 100%;
            height: 300px;
            background: #f3f4f6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 3rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .candidate-content {
            flex: 1;
        }

        .candidate-content h2 {
            color: #1f2a44;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .candidate-info {
            margin-bottom: 20px;
        }

        .candidate-info h3 {
            color: #1f2a44;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .candidate-info p {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .candidate-visi-misi {
            margin-bottom: 20px;
        }

        .candidate-visi-misi h3 {
            color: #1f2a44;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .candidate-visi-misi p {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .candidate-visi-misi ul {
            list-style-type: disc;
            padding-left: 20px;
        }

        .candidate-visi-misi li {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .vote-button {
            width: 100%;
            padding: 12px 20px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .vote-button:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .vote-disabled {
            width: 100%;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: white;
        }

        .vote-disabled.already-voted {
            background: #059669;
        }

        .vote-disabled.not-started {
            background: #9ca3af;
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

            .candidate-detail {
                flex-direction: column;
                padding: 20px;
            }

            .candidate-image {
                flex: none;
                width: 100%;
                margin-bottom: 20px;
            }

            .candidate-image img,
            .candidate-image .placeholder {
                width: 100%;
                height: 200px;
            }

            .candidate-content h2 {
                font-size: 1.5rem;
            }

            .candidate-info h3 {
                font-size: 1rem;
            }

            .candidate-info p {
                font-size: 0.9rem;
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
                <a href="beranda-siswa.php" class="logout-btn">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="candidate-detail">
            <div class="candidate-image">
                <?php if (!empty($kandidat['gambar']) && file_exists("uploads/" . $kandidat['gambar'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($kandidat['gambar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto Kandidat">
                <?php else: ?>
                    <div class="placeholder"><i class="fas fa-user"></i></div>
                <?php endif; ?>
            </div>
            <div class="candidate-content">
                <h2><?php echo htmlspecialchars($kandidat['nama_ketua'], ENT_QUOTES, 'UTF-8'); ?></h2>

                <div class="candidate-info">
                    <?php if (!empty($kandidat['nama_wakil'])): ?>
                        <h3>Nama Wakil</h3>
                        <p><?php echo htmlspecialchars($kandidat['nama_wakil'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <h3>Kelas</h3>
                    <p><?php echo htmlspecialchars($kandidat['kelas'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <?php if (!empty($kandidat['visi']) || !empty($kandidat['misi'])): ?>
                    <div class="candidate-visi-misi">
                        <?php if (!empty($kandidat['visi'])): ?>
                            <h3>Visi</h3>
                            <p><?php echo nl2br(htmlspecialchars($kandidat['visi'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($kandidat['misi'])): ?>
                            <h3>Misi</h3>
                            <?php
                            $misi_lines = array_filter(array_map('trim', explode("\n", $kandidat['misi'])));
                            if (count($misi_lines) > 1) {
                                echo '<ul>';
                                foreach ($misi_lines as $line) {
                                    echo '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<p>' . nl2br(htmlspecialchars($kandidat['misi'], ENT_QUOTES, 'UTF-8')) . '</p>';
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php
                // Logika tombol berdasarkan status voting dan apakah siswa sudah memilih
                if ($sudah_memilih) {
                    // Siswa sudah memilih
                    echo '<div class="vote-disabled already-voted">
                            <i class="fas fa-check-circle"></i> Anda Sudah Memilih
                          </div>';
                } elseif ($is_voting_active) {
                    // Voting sedang aktif dan siswa belum memilih
                    echo '<form method="POST" onsubmit="return confirmVote(\'' . htmlspecialchars($kandidat['nama_ketua'], ENT_QUOTES, 'UTF-8') . '\')">
                            <button type="submit" name="pilih_kandidat" class="vote-button">
                                <i class="fas fa-vote-yea"></i> Pilih Kandidat Ini
                            </button>
                          </form>';
                } else {
                    // Voting belum dimulai atau sudah dihentikan
                    echo '<div class="vote-disabled not-started">
                            <i class="fas fa-pause-circle"></i> Voting Belum Dimulai
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        function confirmVote(namaKandidat) {
            return confirm('Apakah Anda yakin memilih "' + namaKandidat + '"?\n\nSetelah memilih, Anda tidak dapat mengubah pilihan lagi.');
        }

        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>