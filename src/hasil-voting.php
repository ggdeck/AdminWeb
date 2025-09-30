<?php
session_start();

// ✅ Cek login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ Konfigurasi database (Railway)
$host = getenv('DB_HOST') ?: 'mysql.railway.internal';
$dbname = getenv('DB_DATABASE') ?: 'railway';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'mtUTprOhWarfnJdwcDbbMKTozkcNyrln';
$port = getenv('DB_PORT') ?: '3306';

try {
    // ✅ Gunakan variabel yang sama (tidak tumpang tindih)
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Koneksi gagal: " . $e->getMessage());
}

// Proses logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Ambil data hasil voting
$sql = "SELECT 
            k.id_kandidat,
            k.nama_ketua,
            k.nama_wakil,
            k.kelas,
            COALESCE(k.nomor_urut, 0) as nomor_urut,
            COUNT(s.kandidat_id) as jumlah_suara
        FROM kandidat k 
        LEFT JOIN suara s ON k.id_kandidat = s.kandidat_id 
        GROUP BY k.id_kandidat, k.nama_ketua, k.nama_wakil, k.kelas, k.nomor_urut
        ORDER BY k.nomor_urut ASC, k.id_kandidat ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$hasil_voting = $stmt->fetchAll();

// Hitung total suara
$total_suara = 0;
foreach ($hasil_voting as $kandidat) {
    $total_suara += $kandidat['jumlah_suara'];
}

// Ambil total pemilih
$sql_total_pemilih = "SELECT COUNT(*) as total_pemilih FROM pemilih";
$stmt_total_pemilih = $pdo->prepare($sql_total_pemilih);
$stmt_total_pemilih->execute();
$total_pemilih = $stmt_total_pemilih->fetch()['total_pemilih'];

// Hitung persentase partisipasi
$persentase_partisipasi = $total_pemilih > 0 ? round(($total_suara / $total_pemilih) * 100, 2) : 0;

// Ambil suara terbanyak untuk menentukan skala diagram
$suara_terbanyak = 0;
foreach ($hasil_voting as $kandidat) {
    if ($kandidat['jumlah_suara'] > $suara_terbanyak) {
        $suara_terbanyak = $kandidat['jumlah_suara'];
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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
            margin-bottom: 10px;
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

        .results-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .results-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 25px;
            text-align: center;
        }

        .candidate-result {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .candidate-result:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .candidate-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .candidate-name {
            flex: 1;
        }

        .candidate-name h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 5px;
        }

        .candidate-name p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .vote-count {
            text-align: right;
            margin-right: 20px;
        }

        .vote-count .count {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
        }

        .vote-count .percentage {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .progress-bar {
            height: 40px;
            background: #f3f4f6;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, #1e40af, #3b82f6);
            border-radius: 20px;
            position: relative;
            transition: width 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 15px;
        }

        .progress-text {
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
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

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-260px);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
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
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .candidate-info {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .vote-count {
                text-align: center;
                margin-right: 0;
            }
        }

        @keyframes progressAnimation {
            from { width: 0; }
        }

        .progress-fill {
            animation: progressAnimation 1.5s ease-out;
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
            <li><a href="beranda.php"><i class="fas fa-home-alt"></i> Beranda</a></li>
            <li><a href="kandidat.php"><i class="fas fa-users"></i> Data Kandidat</a></li>
            <li><a href="pemilih.php"><i class="fas fa-user-plus"></i> Data Pemilih</a></li>
            <li><a href="hasil-voting.php" class="active"><i class="fas fa-chart-bar"></i> Hasil Voting</a></li>
            <li><a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content" id="content">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Hasil Voting</h1>
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh Data
            </button>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-vote-yea"></i>
                <h3><?php echo $total_suara; ?></h3>
                <p>Total Suara Masuk</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo $total_pemilih; ?></h3>
                <p>Total Pemilih</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-percentage"></i>
                <h3><?php echo $persentase_partisipasi; ?>%</h3>
                <p>Partisipasi</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-friends"></i>
                <h3><?php echo count($hasil_voting); ?></h3>
                <p>Kandidat</p>
            </div>
        </div>

        <div class="results-container">
            <h2 class="results-title">Hasil Perolehan Suara</h2>
            
            <?php if (count($hasil_voting) > 0): ?>
                <?php foreach ($hasil_voting as $kandidat): ?>
                    <?php 
                    $persentase = $total_suara > 0 ? round(($kandidat['jumlah_suara'] / $total_suara) * 100, 2) : 0;
                    $lebar_bar = $suara_terbanyak > 0 ? ($kandidat['jumlah_suara'] / $suara_terbanyak) * 100 : 0;
                    ?>
                    <div class="candidate-result">
                        <div class="candidate-info">
                            <div class="candidate-name">
                                <h3>
                                    <?php if($kandidat['nomor_urut'] > 0): ?>
                                        Nomor <?php echo $kandidat['nomor_urut']; ?> - 
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($kandidat['nama_ketua'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if(!empty($kandidat['nama_wakil'])): ?>
                                        & <?php echo htmlspecialchars($kandidat['nama_wakil'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </h3>
                                <p>Kelas: <?php echo htmlspecialchars($kandidat['kelas'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="vote-count">
                                <div class="count"><?php echo $kandidat['jumlah_suara']; ?></div>
                                <div class="percentage"><?php echo $persentase; ?>%</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $lebar_bar; ?>%;">
                                <?php if($lebar_bar > 20): ?>
                                    <span class="progress-text"><?php echo $kandidat['jumlah_suara']; ?> suara</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h3>Belum Ada Data Voting</h3>
                    <p>Data hasil voting akan muncul setelah ada suara yang masuk</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Menu toggle functionality
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768 && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Auto refresh setiap 30 detik
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>