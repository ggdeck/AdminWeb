<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

// Ambil ID kandidat dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: kandidat.php");
    exit;
}

$id = $_GET['id'];
$sql = "SELECT * FROM kandidat WHERE id_kandidat = ?";
$stmt = $konek->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$kandidat = $stmt->get_result()->fetch_assoc();

if (!$kandidat) {
    header("Location: kandidat.php");
    exit;
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

        .detail-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .detail-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(to right, #1e40af, #3b82f6);
            border-radius: 12px 12px 0 0;
        }

        .detail-card img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            margin: 0 auto 20px;
            display: block;
        }

        .detail-card .placeholder-img {
            width: 200px;
            height: 200px;
            background: #f3f4f6;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .detail-card h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1f2a44;
            text-align: center;
            margin-bottom: 20px;
        }

        .detail-card .info-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .detail-card .info-group p {
            margin-bottom: 10px;
            font-size: 1rem;
            color: #374151;
        }

        .detail-card .info-group p strong {
            color: #1f2a44;
            display: inline-block;
            width: 120px;
        }

        .detail-card .vision-mission {
            margin-top: 30px;
        }

        .detail-card .vision-mission h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 15px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 5px;
        }

        .detail-card .vision-mission p {
            font-size: 0.95rem;
            color: #374151;
            line-height: 1.6;
        }

        .detail-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
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

            .detail-card {
                padding: 20px;
            }

            .detail-card img,
            .detail-card .placeholder-img {
                width: 150px;
                height: 150px;
            }

            .detail-card .info-group p strong {
                width: 100px;
            }
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
            <li><a href="kandidat.php" class="active"><i class="fas fa-users"></i> Data Kandidat</a></li>
            <li><a href="pemilih.php"><i class="fas fa-user-plus"></i> Data Pemilih</a></li>
            <li><a href="hasil-voting.php"><i class="fas fa-chart-bar"></i> Hasil Voting</a></li>
            <li><a href="kandidat.php?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content" id="content">
        <div class="header">
            <h1><i class="fas fa-info-circle"></i> Detail Kandidat</h1>
        </div>

        <div class="detail-card">
            <?php if ($kandidat['gambar'] && file_exists("uploads/" . $kandidat['gambar'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($kandidat['gambar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto Kandidat">
            <?php else: ?>
                <div class="placeholder-img">
                    <i class="fas fa-user fa-3x" style="color: #6b7280;"></i>
                </div>
            <?php endif; ?>
            
            <h2><?php echo htmlspecialchars($kandidat['nama_ketua'], ENT_QUOTES, 'UTF-8'); ?> & <?php echo htmlspecialchars($kandidat['nama_wakil'], ENT_QUOTES, 'UTF-8'); ?></h2>
            
            <div class="info-group">
                <p><strong>Nama Ketua:</strong> <?php echo htmlspecialchars($kandidat['nama_ketua'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Nama Wakil:</strong> <?php echo htmlspecialchars($kandidat['nama_wakil'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Kelas:</strong> <?php echo htmlspecialchars($kandidat['kelas'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="vision-mission">
                <h3>Visi</h3>
                <p><?php echo nl2br(htmlspecialchars($kandidat['visi'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div class="vision-mission">
                <h3>Misi</h3>
                <p><?php echo nl2br(htmlspecialchars($kandidat['misi'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
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
    </script>
</body>
</html>