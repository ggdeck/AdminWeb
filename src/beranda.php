<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

// Proses logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle voting control actions
$message = '';
$message_type = '';

// Cek apakah tabel voting_config sudah ada, jika tidak buat
try {
    $sql_check_table = "CREATE TABLE IF NOT EXISTS voting_config (
        id INT PRIMARY KEY DEFAULT 1,
        is_active BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $konek->query($sql_check_table);
    
    // Insert default config jika belum ada
    $sql_check_config = "SELECT COUNT(*) as total FROM voting_config";
    $result_check = $konek->query($sql_check_config);
    if ($result_check->fetch_assoc()['total'] == 0) {
        $sql_insert_config = "INSERT INTO voting_config (id, is_active) VALUES (1, FALSE)";
        $konek->query($sql_insert_config);
    }
} catch (Exception $e) {
    // Fallback jika tidak bisa membuat tabel
}

// Fetch voting status dari tabel voting_config
try {
    $sql = "SELECT is_active FROM voting_config WHERE id = 1";
    $result = $konek->query($sql);
    if ($result && $result->num_rows > 0) {
        $config = $result->fetch_assoc();
        $is_voting_active = (bool)$config['is_active'];
    } else {
        $is_voting_active = false;
    }
} catch (Exception $e) {
    $message = "Warning: Could not fetch voting status.";
    $message_type = "error";
    $is_voting_active = false;
}

// Handle start/stop/reset voting
if (isset($_POST['action'])) {
    try {
        $konek->begin_transaction();

        if ($_POST['action'] == 'start') {
            // Set voting status menjadi aktif
            $sql = "UPDATE voting_config SET is_active = TRUE, updated_at = NOW() WHERE id = 1";
            if (!$konek->query($sql)) {
                throw new Exception("Failed to start voting: " . $konek->error);
            }
            $message = "Voting berhasil dimulai untuk semua pemilih!";
            $message_type = "success";
            $is_voting_active = true;
            
        } elseif ($_POST['action'] == 'stop') {
            // Set voting status menjadi tidak aktif
            $sql = "UPDATE voting_config SET is_active = FALSE, updated_at = NOW() WHERE id = 1";
            if (!$konek->query($sql)) {
                throw new Exception("Failed to stop voting: " . $konek->error);
            }
            $message = "Voting berhasil dihentikan untuk semua pemilih!";
            $message_type = "success";
            $is_voting_active = false;
            
        } elseif ($_POST['action'] == 'reset') {
            // Reset voting results
            $sql_delete_votes = "DELETE FROM suara";
            if (!$konek->query($sql_delete_votes)) {
                throw new Exception("Failed to reset votes: " . $konek->error);
            }
            
            // Reset status pemilih - karena status_voting adalah varchar(50) NOT NULL
            // Set ke string kosong untuk menandakan belum voting
            $sql_reset_status = "UPDATE pemilih SET status_voting = ''";
            if (!$konek->query($sql_reset_status)) {
                throw new Exception("Failed to reset voter status: " . $konek->error);
            }
            
            // Set voting status menjadi tidak aktif
            $sql_config_reset = "UPDATE voting_config SET is_active = FALSE, updated_at = NOW() WHERE id = 1";
            if (!$konek->query($sql_config_reset)) {
                throw new Exception("Failed to reset voting config: " . $konek->error);
            }
            
            $message = "Voting berhasil direset! Semua data suara dihapus dan status pemilih dikembalikan ke awal.";
            $message_type = "success";
            $is_voting_active = false;
        }
        $konek->commit();
    } catch (Exception $e) {
        $konek->rollback();
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch summary data
$total_kandidat = 0;
$total_pemilih = 0;
$total_votes = 0;

try {
    // Total candidates
    $sql = "SELECT COUNT(*) as total_kandidat FROM kandidat";
    $stmt = $konek->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $konek->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_kandidat = $row['total_kandidat'] ?? 0;
    $result->free();
    $stmt->close();

    // Total voters (pemilih)
    $sql = "SELECT COUNT(*) as total_pemilih FROM pemilih";
    $stmt = $konek->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_pemilih = $row['total_pemilih'] ?? 0;
        $result->free();
        $stmt->close();
    } else {
        $total_pemilih = 0;
        if (empty($message)) {
            $message = "Warning: Could not fetch voter count. Table 'pemilih' may not exist.";
            $message_type = "error";
        }
    }

    // Total votes
    $sql = "SELECT COUNT(*) as total_votes FROM suara";
    $stmt = $konek->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_votes = $row['total_votes'] ?? 0;
        $result->free();
        $stmt->close();
    } else {
        $total_votes = 0;
        if (empty($message)) {
            $message = "Warning: Could not fetch vote count. Table 'suara' may not exist.";
            $message_type = "error";
        }
    }
} catch (Exception $e) {
    if (empty($message)) {
        $message = "Error: " . $e->getMessage();
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
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #1e40af;
            height: 100vh;
            padding: 20px;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.08);
            position: fixed;
            transition: transform 0.3s ease, width 0.3s ease;
            z-index: 1000;
            color: #ffffff;
        }

        .sidebar .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
            border-bottom: 1px solid #3b82f6;
            margin-bottom: 20px;
        }

        .sidebar .logo img {
            width: 170px;
            margin-bottom: 10px;
        }

        .sidebar h2 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffffff;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            margin: 8px 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .sidebar ul li a.active {
            background: #3b82f6;
        }

        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.1rem;
            color: #93c5fd;
        }

        .sidebar ul li:hover a {
            background: #3b82f6;
            color: #ffffff;
        }

        .sidebar ul li:hover a i {
            color: #ffffff;
        }

        .content {
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
            background: #f9fafb;
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        .header {
            background: #ffffff;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1f2a44;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #1e40af;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #059669;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #047857;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc2626;
            color: #ffffff;
        }

        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #d97706;
            color: #ffffff;
        }

        .btn-warning:hover {
            background: #b45309;
            transform: translateY(-2px);
        }

        .control-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            text-align: center;
        }

        .control-section h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 20px;
        }

        .control-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .control-btn {
            padding: 15px 25px;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .control-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .control-btn.start {
            background: #059669;
        }

        .control-btn.start:hover {
            background: #047857;
        }

        .control-btn.stop {
            background: #dc2626;
        }

        .control-btn.stop:hover {
            background: #b91c1c;
        }

        .control-btn.reset {
            background: #d97706;
            width: 120px;
            height: 50px;
            border-radius: 8px;
            font-size: 1rem;
            flex-direction: row;
        }

        .control-btn.reset:hover {
            background: #b45309;
        }

        .control-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .control-btn:disabled:hover {
            transform: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .status-indicator.active {
            background: #ecfdf5;
            color: #065f46;
            border-color: #bbf7d0;
        }

        .status-indicator i {
            font-size: 1.1rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .summary-card i {
            font-size: 2rem;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .summary-card h4 {
            font-size: 1.1rem;
            font-weight: 500;
            color: #1f2a44;
            margin-bottom: 10px;
        }

        .summary-card .count {
            font-size: 2rem;
            font-weight: 600;
            color: #1e40af;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: #ffffff;
            background: #1e40af;
            border: none;
            padding: 10px;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .reset-confirmation {
            background: #fef3c7;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 0.9rem;
            border: 1px solid #fcd34d;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-260px);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .control-buttons {
                flex-direction: column;
                align-items: center;
            }

            .control-btn.reset {
                width: 150px;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="images/image.png" alt="Logo">
            <h2>SMPN 6 Cibitung</h2>
        </div>
        <ul>
            <li><a href="beranda.php" class="active"><i class="fas fa-home-alt"></i> Beranda</a></li>
            <li><a href="kandidat.php"><i class="fas fa-users"></i> Data Kandidat</a></li>
            <li><a href="pemilih.php"><i class="fas fa-user-plus"></i> Data Pemilih</a></li>
            <li><a href="hasil-voting.php"><i class="fas fa-chart-bar"></i> Hasil Voting</a></li>
            <li><a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content" id="content">
        <div class="header">
            <h1><i class="fas fa-home"></i> Beranda</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="control-section">
            <h3><i class="fas fa-cog"></i> Kontrol Voting</h3>
            
            <div class="status-indicator <?php echo $is_voting_active ? 'active' : ''; ?>">
                <i class="fas fa-<?php echo $is_voting_active ? 'play-circle' : 'pause-circle'; ?>"></i>
                Status Voting: <?php echo $is_voting_active ? 'Sedang Berlangsung' : 'Belum Dimulai'; ?>
            </div>

            <div class="control-buttons">
                <form method="POST" style="display: inline;">
                    <?php if (!$is_voting_active): ?>
                        <button type="submit" name="action" value="start" class="control-btn start" title="Mulai Voting">
                            <i class="fas fa-play"></i>
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="stop" class="control-btn stop" title="Hentikan Voting">
                            <i class="fas fa-square"></i>
                        </button>
                    <?php endif; ?>
                </form>
                
                <form method="POST" style="display: inline;" onsubmit="return confirmReset()">
                    <button type="submit" name="action" value="reset" class="control-btn reset" title="Reset Voting">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </form>
            </div>
            
            <?php if ($total_votes > 0): ?>
                <div class="reset-confirmation">
                    <i class="fas fa-exclamation-triangle"></i>
                    Reset akan menghapus <strong><?php echo $total_votes; ?> suara</strong> dan mengatur ulang status <?php echo $total_pemilih; ?> pemilih!
                </div>
            <?php endif; ?>
        </div>

        <div class="summary-grid">
            <a href="kandidat.php" class="summary-card">
                <i class="fas fa-users"></i>
                <h4>Total Kandidat</h4>
                <div class="count"><?php echo htmlspecialchars($total_kandidat ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </a>
            <a href="pemilih.php" class="summary-card">
                <i class="fas fa-user-shield"></i>
                <h4>Total Pemilih</h4>
                <div class="count"><?php echo htmlspecialchars($total_pemilih ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </a>
            <a href="hasil-voting.php" class="summary-card">
                <i class="fas fa-chart-bar"></i>
                <h4>Total Voting</h4>
                <div class="count"><?php echo htmlspecialchars($total_votes ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </a>
        </div>
    </div>

    <script>
        // Menu toggle functionality
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768 && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Confirm reset function
        function confirmReset() {
            const totalVotes = <?php echo $total_votes; ?>;
            const totalVoters = <?php echo $total_pemilih; ?>;
            
            return confirm(
                `PERINGATAN: Reset akan menghapus semua data berikut:\n\n` +
                `• ${totalVotes} suara yang sudah masuk\n` +
                `• Status voting dari ${totalVoters} pemilih akan dikosongkan\n` +
                `• Status voting akan dihentikan\n\n` +
                `Data yang dihapus TIDAK DAPAT dikembalikan!\n\n` +
                `Apakah Anda yakin ingin melanjutkan?`
            );
        }

        // Auto hide alerts
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