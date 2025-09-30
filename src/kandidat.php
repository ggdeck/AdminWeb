<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Include konfigurasi database
include 'config.php';

// Proses logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle CRUD Operations
$message = '';
$message_type = '';

// CREATE - Tambah kandidat baru
if (isset($_POST['tambah_kandidat'])) {
    try {
        $nama_ketua = $_POST['nama_ketua'];
        $nama_wakil = $_POST['nama_wakil'];
        $kelas = $_POST['kelas'];
        $visi = $_POST['visi'];
        $misi = $_POST['misi'];
        
        // Handle file upload untuk gambar
        $gambar = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'kandidat_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                $gambar = $new_filename;
            }
        }
        
        $sql = "INSERT INTO kandidat (nama_ketua, nama_wakil, kelas, visi, misi, gambar) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $konek->prepare($sql);
        $stmt->bind_param("ssssss", $nama_ketua, $nama_wakil, $kelas, $visi, $misi, $gambar);
        $stmt->execute();
        
        $message = "Kandidat berhasil ditambahkan!";
        $message_type = "success";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// UPDATE - Edit kandidat
if (isset($_POST['edit_kandidat'])) {
    try {
        $id = $_POST['id_kandidat'];
        $nama_ketua = $_POST['nama_ketua'];
        $nama_wakil = $_POST['nama_wakil'];
        $kelas = $_POST['kelas'];
        $visi = $_POST['visi'];
        $misi = $_POST['misi'];
        
        // Handle file upload untuk gambar baru
        $gambar_query = "";
        $params = [$nama_ketua, $nama_wakil, $kelas, $visi, $misi];
        $types = "sssss";
        
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'kandidat_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                $gambar_query = ", gambar = ?";
                $params[] = $new_filename;
                $types .= "s";
            }
        }
        
        $params[] = $id;
        $types .= "i";
        $sql = "UPDATE kandidat SET nama_ketua = ?, nama_wakil = ?, kelas = ?, visi = ?, misi = ? $gambar_query WHERE id_kandidat = ?";
        $stmt = $konek->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $message = "Kandidat berhasil diupdate!";
        $message_type = "success";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// DELETE - Hapus kandidat
if (isset($_GET['delete'])) {
    try {
        $id = $_GET['delete'];
        
        // Hapus file gambar jika ada
        $sql = "SELECT gambar FROM kandidat WHERE id_kandidat = ?";
        $stmt = $konek->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['gambar'] && file_exists("uploads/" . $result['gambar'])) {
            unlink("uploads/" . $result['gambar']);
        }
        
        $sql = "DELETE FROM kandidat WHERE id_kandidat = ?";
        $stmt = $konek->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $message = "Kandidat berhasil dihapus!";
        $message_type = "success";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// READ - Ambil semua data kandidat
$sql = "SELECT * FROM kandidat ORDER BY id_kandidat ASC";
$stmt = $konek->prepare($sql);
$stmt->execute();
$kandidats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Untuk edit - ambil data kandidat berdasarkan ID
$edit_kandidat = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM kandidat WHERE id_kandidat = ?";
    $stmt = $konek->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_kandidat = $stmt->get_result()->fetch_assoc();
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
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
        }

        .btn-warning {
            background: #d97706;
            color: #ffffff;
        }

        .btn-warning:hover {
            background: #b45309;
        }

        .btn-danger {
            background: #dc2626;
            color: #ffffff;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-info {
            background: #0284c7;
            color: #ffffff;
        }

        .btn-info:hover {
            background: #0369a1;
        }

        .btn-secondary {
            background: #6b7280;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            min-height: 400px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #1e40af, #3b82f6);
        }

        .card h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 15px;
            margin-left: 15px;
        }

        .card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
        }

        .card-placeholder {
            width: 100%;
            height: 200px;
            background: #f3f4f6;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e5e7eb;
        }

        .card-content {
            margin-bottom: 15px;
            flex-grow: 1;
            padding: 0 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-content p {
            margin-bottom: 10px;
            color: #374151;
            font-size: 0.96rem;
            text-align: left;
        }

        .card-content p strong {
            color: #1f2a44;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.3);
        }

        textarea.form-control {
            height: 120px;
            resize: vertical;
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

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #6b7280;
            font-style: italic;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 2001;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h3 {
            margin: 0;
            color: #1f2a44;
            font-size: 1.4rem;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 5px;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: #f3f4f6;
            color: #1f2a44;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translate(-50%, -60%); opacity: 0; }
            to { transform: translate(-50%, -50%); opacity: 1; }
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
            
            .row {
                grid-template-columns: 1fr;
            }
            
            .card-img, .card-placeholder {
                height: 150px;
            }
            
            .card-container {
                grid-template-columns: 1fr;
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
            <li><a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content" id="content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Data Kandidat Ketua dan Wakil Ketua OSIS</h1>
            <button class="btn btn-primary" onclick="openModal('tambahModal')">
                <i class="fas fa-plus"></i> Tambah Kandidat
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="card-container <?php echo count($kandidats) === 1 ? 'single-candidate' : ''; ?>">
            <?php if (count($kandidats) > 0): ?>
                <?php foreach ($kandidats as $index => $kandidat): ?>
                    <div class="card">
                        <?php if ($kandidat['gambar'] && file_exists("uploads/" . $kandidat['gambar'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($kandidat['gambar'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="Foto Kandidat" class="card-img">
                        <?php else: ?>
                            <div class="card-placeholder">
                                <i class="fas fa-user fa-3x" style="color: #6b7280;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($kandidat['nama_ketua'], ENT_QUOTES, 'UTF-8'); ?> & <?php echo htmlspecialchars($kandidat['nama_wakil'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><strong>Kelas:</strong> <?php echo htmlspecialchars($kandidat['kelas'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Visi:</strong> <?php echo nl2br(htmlspecialchars(substr($kandidat['visi'], 0, 100), ENT_QUOTES, 'UTF-8')); ?><?php if (strlen($kandidat['visi']) > 100): ?>...<?php endif; ?></p>
                            <p><strong>Misi:</strong> <?php echo nl2br(htmlspecialchars(substr($kandidat['misi'], 0, 100), ENT_QUOTES, 'UTF-8')); ?><?php if (strlen($kandidat['misi']) > 100): ?>...<?php endif; ?></p>
                        </div>
                        <div class="card-actions">
                            <a href="detail-kandidat-admin.php?id=<?php echo $kandidat['id_kandidat']; ?>" 
                               class="btn btn-info btn-sm">
                                <i class="fas fa-info-circle"></i> Detail
                            </a>
                            <button class="btn btn-warning btn-sm" 
                                    onclick="editKandidat(<?php echo htmlspecialchars(json_encode($kandidat), ENT_QUOTES, 'UTF-8'); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?delete=<?php echo $kandidat['id_kandidat']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus kandidat ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card text-center text-muted">
                    <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px;"></i>
                    <p>Belum ada data kandidat</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Tambah Kandidat -->
    <div class="modal-overlay" id="tambahModalOverlay" onclick="closeModal('tambahModal')"></div>
    <div class="modal" id="tambahModal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Tambah Kandidat Baru</h3>
            <button class="close-btn" onclick="closeModal('tambahModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="form-group">
                    <label for="nama_ketua">Nama Ketua</label>
                    <input type="text" class="form-control" id="nama_ketua" name="nama_ketua" required>
                </div>
                <div class="form-group">
                    <label for="nama_wakil">Nama Wakil</label>
                    <input type="text" class="form-control" id="nama_wakil" name="nama_wakil" required>
                </div>
            </div>
            <div class="form-group">
                <label for="kelas">Kelas</label>
                <input type="text" class="form-control" id="kelas" name="kelas" required>
            </div>
            <div class="form-group">
                <label for="gambar">Foto Kandidat</label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                <div id="preview_tambah" style="margin-top: 10px;"></div>
            </div>
            <div class="form-group">
                <label for="visi">Visi</label>
                <textarea class="form-control" id="visi" name="visi" required></textarea>
            </div>
            <div class="form-group">
                <label for="misi">Misi</label>
                <textarea class="form-control" id="misi" name="misi" required></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('tambahModal')">Batal</button>
                <button type="submit" name="tambah_kandidat" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Edit Kandidat -->
    <div class="modal-overlay" id="editModalOverlay" onclick="closeModal('editModal')"></div>
    <div class="modal" id="editModal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Kandidat</h3>
            <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" id="edit_id_kandidat" name="id_kandidat">
            <div class="row">
                <div class="form-group">
                    <label for="edit_nama_ketua">Nama Ketua</label>
                    <input type="text" class="form-control" id="edit_nama_ketua" name="nama_ketua" required>
                </div>
                <div class="form-group">
                    <label for="edit_nama_wakil">Nama Wakil</label>
                    <input type="text" class="form-control" id="edit_nama_wakil" name="nama_wakil" required>
                </div>
            </div>
            <div class="form-group">
                <label for="edit_kelas">Kelas</label>
                <input type="text" class="form-control" id="edit_kelas" name="kelas" required>
            </div>
            <div class="form-group">
                <label for="edit_gambar">Foto Kandidat (Kosongkan jika tidak ingin mengubah)</label>
                <input type="file" class="form-control" id="edit_gambar" name="gambar" accept="image/*">
                <div id="current_image" style="margin-top: 10px;"></div>
            </div>
            <div class="form-group">
                <label for="edit_visi">Visi</label>
                <textarea class="form-control" id="edit_visi" name="visi" required></textarea>
            </div>
            <div class="form-group">
                <label for="edit_misi">Misi</label>
                <textarea class="form-control" id="edit_misi" name="misi" required></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" name="edit_kandidat" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update
                </button>
            </div>
        </form>
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

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId + 'Overlay').style.display = 'block';
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId + 'Overlay').style.display = 'none';
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Reset form
            if (modalId === 'tambahModal') {
                document.querySelector('#tambahModal form').reset();
                document.getElementById('preview_tambah').innerHTML = '';
            }
        }

        // Edit kandidat function
        function editKandidat(kandidat) {
            document.getElementById('edit_id_kandidat').value = kandidat.id_kandidat;
            document.getElementById('edit_nama_ketua').value = kandidat.nama_ketua;
            document.getElementById('edit_nama_wakil').value = kandidat.nama_wakil;
            document.getElementById('edit_kelas').value = kandidat.kelas;
            document.getElementById('edit_visi').value = kandidat.visi;
            document.getElementById('edit_misi').value = kandidat.misi;
            
            // Show current image if exists
            const currentImageDiv = document.getElementById('current_image');
            if (kandidat.gambar) {
                currentImageDiv.innerHTML = `
                    <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 5px;">Foto saat ini:</p>
                    <img src="uploads/${kandidat.gambar}" alt="Foto saat ini" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb;">
                `;
            } else {
                currentImageDiv.innerHTML = '<p style="font-size: 0.9rem; color: #6b7280;">Tidak ada foto</p>';
            }
            
            openModal('editModal');
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

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let hasError = false;
                    
                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            hasError = true;
                            field.style.borderColor = '#dc2626';
                            field.focus();
                        } else {
                            field.style.borderColor = '#e5e7eb';
                        }
                    });
                    
                    if (hasError) {
                        e.preventDefault();
                        alert('Mohon lengkapi semua field yang wajib diisi!');
                    }
                });
            });
        });

        // File upload preview
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    preview.innerHTML = `
                        <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 5px;">Preview:</p>
                        <img src="${e.target.result}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb;">
                    `;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Add event listeners for file inputs
        document.getElementById('gambar').addEventListener('change', function() {
            previewImage(this, 'preview_tambah');
        });

        document.getElementById('edit_gambar').addEventListener('change', function() {
            previewImage(this, 'current_image');
        });
    </script>
</body>
</html>