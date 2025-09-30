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

// CREATE - Tambah pemilih baru
if (isset($_POST['tambah_pemilih'])) {
    try {
        if (!isset($_POST['nis']) || empty(trim($_POST['nis']))) {
            throw new Exception("NIS wajib diisi!");
        }
        if (!isset($_POST['nama']) || empty(trim($_POST['nama']))) {
            throw new Exception("Nama wajib diisi!");
        }
        if (!isset($_POST['kelas']) || empty(trim($_POST['kelas']))) {
            throw new Exception("Kelas wajib diisi!");
        }

        $nama = trim($_POST['nama']);
        $nis = trim($_POST['nis']);
        $kelas = trim($_POST['kelas']);
        $username = $nis; // Username = NIS
        $password = $nis; // Password = NIS (tanpa hash)
        $status_voting = 0; // Default: Belum memilih
        
        // Cek duplikat NIS
        $check_sql = "SELECT id FROM pemilih WHERE nis = ?";
        $check_stmt = $konek->prepare($check_sql);
        $check_stmt->bind_param("s", $nis);
        $check_stmt->execute();
        if ($check_stmt->fetch()) {
            throw new Exception("NIS sudah digunakan!");
        }
        
        $sql = "INSERT INTO pemilih (nama, nis, kelas, username, password, status_voting) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $konek->prepare($sql);
        $stmt->bind_param("sssssi", $nama, $nis, $kelas, $username, $password, $status_voting);
        $stmt->execute();
        
        $message = "Pemilih berhasil ditambahkan! Username: $username, Password: $password";
        $message_type = "success";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// UPDATE - Edit pemilih (tanpa mengubah status_voting)
if (isset($_POST['edit_pemilih'])) {
    try {
        if (!isset($_POST['id_pemilih']) || empty(trim($_POST['id_pemilih']))) {
            throw new Exception("ID Pemilih tidak valid!");
        }
        if (!isset($_POST['nis']) || empty(trim($_POST['nis']))) {
            throw new Exception("NIS wajib diisi!");
        }
        if (!isset($_POST['nama']) || empty(trim($_POST['nama']))) {
            throw new Exception("Nama wajib diisi!");
        }
        if (!isset($_POST['kelas']) || empty(trim($_POST['kelas']))) {
            throw new Exception("Kelas wajib diisi!");
        }

        $id = trim($_POST['id_pemilih']);
        $nama = trim($_POST['nama']);
        $nis = trim($_POST['nis']);
        $kelas = trim($_POST['kelas']);
        $username = $nis; // Username = NIS
        $password = $nis; // Password = NIS (tanpa hash)
        // Ambil status_voting dari database, tidak diubah dari form
        $sql_get_status = "SELECT status_voting FROM pemilih WHERE id = ?";
        $stmt_get_status = $konek->prepare($sql_get_status);
        $stmt_get_status->bind_param("i", $id);
        $stmt_get_status->execute();
        $result_status = $stmt_get_status->get_result()->fetch_assoc();
        $status_voting = $result_status['status_voting'];
        
        // Cek duplikat NIS (kecuali untuk record sendiri)
        $check_sql = "SELECT id FROM pemilih WHERE nis = ? AND id != ?";
        $check_stmt = $konek->prepare($check_sql);
        $check_stmt->bind_param("si", $nis, $id);
        $check_stmt->execute();
        if ($check_stmt->fetch()) {
            throw new Exception("NIS sudah digunakan oleh pemilih lain!");
        }
        
        $sql = "UPDATE pemilih SET nama = ?, nis = ?, kelas = ?, username = ?, password = ?, status_voting = ? WHERE id = ?";
        $stmt = $konek->prepare($sql);
        $stmt->bind_param("sssssii", $nama, $nis, $kelas, $username, $password, $status_voting, $id);
        $stmt->execute();
        
        // Refresh session data if the current admin's status was updated
        if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $id) {
            $sql_status = "SELECT status_voting FROM pemilih WHERE id = ?";
            $stmt_status = $konek->prepare($sql_status);
            $stmt_status->bind_param("i", $id);
            $stmt_status->execute();
            $result_status = $stmt_status->get_result()->fetch_assoc();
            $_SESSION['status_voting'] = $result_status['status_voting'];
        }
        
        $message = "Pemilih berhasil diupdate! Username: $username, Password: $password";
        $message_type = "success";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// DELETE - Hapus pemilih
if (isset($_GET['delete'])) {
    try {
        $id = $_GET['delete'];
        $sql = "DELETE FROM pemilih WHERE id = ?";
        $stmt = $konek->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $message = "Pemilih berhasil dihapus!";
        $message_type = "success";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Check NIS via AJAX
if (isset($_POST['check_nis'])) {
    $nis = trim($_POST['nis']);
    $response = ['exists' => false];
    
    $check_sql = "SELECT id FROM pemilih WHERE nis = ?";
    $check_stmt = $konek->prepare($check_sql);
    $check_stmt->bind_param("s", $nis);
    $check_stmt->execute();
    if ($check_stmt->fetch()) {
        $response['exists'] = true;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Filter berdasarkan nama, kelas, dan status voting
$filter_nama = isset($_GET['filter_nama']) ? trim($_GET['filter_nama']) : '';
$filter_kelas = isset($_GET['filter_kelas']) ? trim($_GET['filter_kelas']) : '';
$filter_status_voting = isset($_GET['filter_status_voting']) ? trim($_GET['filter_status_voting']) : '';

// Query dengan filter
$sql = "SELECT id, nama, nis, kelas, username, status_voting FROM pemilih WHERE 1=1";
$params = [];
$types = '';

if (!empty($filter_nama)) {
    $sql .= " AND nama LIKE ?";
    $params[] = "%$filter_nama%";
    $types .= 's';
}

if (!empty($filter_kelas)) {
    $sql .= " AND kelas LIKE ?";
    $params[] = "%$filter_kelas%";
    $types .= 's';
}

if ($filter_status_voting !== '') {
    $sql .= " AND status_voting = ?";
    $params[] = $filter_status_voting;
    $types .= 'i';
}

$sql .= " ORDER BY id ASC";
$stmt = $konek->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$pemilhs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Untuk edit - ambil data pemilih berdasarkan ID
$edit_pemilih = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT id, nama, nis, kelas, username, status_voting FROM pemilih WHERE id = ?";
    $stmt = $konek->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_pemilih = $stmt->get_result()->fetch_assoc();
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

        .filter-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-section .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }

        .filter-section .form-control {
            width: 100%;
        }

        .filter-section .btn {
            padding: 10px 20px;
            margin-top: 0;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        /* Ensure single card is not too wide */
        .card-container:has(.card:nth-child(1):nth-last-child(1)) {
            display: flex;
            justify-content: center;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            max-width: 400px; /* Limit max width for single card */
            width: 100%;
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

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2a44;
            margin-bottom: 10px;
        }

        .card p {
            margin-bottom: 8px;
            color: #374151;
            font-size: 0.95rem;
        }

        .card p strong {
            color: #1f2a44;
        }

        .status-voted {
            color: #059669;
            font-weight: 500;
        }

        .status-not-voted {
            color: #dc2626;
            font-weight: 500;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
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

        .form-control[readonly] {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        .small-hint {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
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
            max-width: 500px;
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

        .modal-content p {
            margin-bottom: 15px;
            color: #374151;
            font-size: 0.95rem;
        }

        .modal-content p strong {
            color: #1f2a44;
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
            
            .card-container {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                flex-direction: column;
                gap: 15px;
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
            <li><a href="kandidat.php"><i class="fas fa-users"></i> Data Kandidat</a></li>
            <li><a href="pemilih.php" class="active"><i class="fas fa-user-plus"></i> Data Pemilih</a></li>
            <li><a href="hasil-voting.php"><i class="fas fa-chart-bar"></i> Hasil Voting</a></li>
            <li><a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content" id="content">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Data Pemilih</h1>
            <button class="btn btn-primary" onclick="openModal('tambahModal')">
                <i class="fas fa-plus"></i> Tambah Pemilih
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div class="form-group">
                    <label for="filter_nama">Filter Nama</label>
                    <input type="text" class="form-control" id="filter_nama" name="filter_nama" value="<?php echo htmlspecialchars($filter_nama, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label for="filter_kelas">Filter Kelas</label>
                    <input type="text" class="form-control" id="filter_kelas" name="filter_kelas" value="<?php echo htmlspecialchars($filter_kelas, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group" style="margin-top: 25px;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="pemilih.php" class="btn btn-secondary" style="margin-left: 10px;">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-container">
            <?php if (count($pemilhs) > 0): ?>
                <?php foreach ($pemilhs as $pemilih): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($pemilih['nama'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><strong>Kelas:</strong> <?php echo htmlspecialchars($pemilih['kelas'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>ID:</strong> <?php echo htmlspecialchars($pemilih['id'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>NIS:</strong> <?php echo htmlspecialchars($pemilih['nis'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($pemilih['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Password:</strong> <?php echo htmlspecialchars($pemilih['username'], ENT_QUOTES, 'UTF-8'); // Password = Username = NIS ?></p>
                        <p><strong>Status Voting:</strong> 
                            <span class="<?php echo $pemilih['status_voting'] ? 'status-voted' : 'status-not-voted'; ?>">
                                <?php echo $pemilih['status_voting'] ? 'Sudah Memilih' : 'Belum Memilih'; ?>
                            </span>
                        </p>
                        <div class="card-actions">
                            <button class="btn btn-warning btn-sm" 
                                    onclick="editPemilih(<?php echo htmlspecialchars(json_encode($pemilih), ENT_QUOTES, 'UTF-8'); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?delete=<?php echo $pemilih['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus pemilih ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card text-center text-muted">
                    <i class="fas fa-users fa-3x" style="margin-bottom: 15px; color: #6b7280;"></i>
                    <p>Belum ada data pemilih atau tidak ada hasil yang sesuai dengan filter</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Tambah Pemilih -->
    <div class="modal-overlay" id="tambahModalOverlay" onclick="closeModal('tambahModal')"></div>
    <div class="modal" id="tambahModal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Tambah Pemilih Baru</h3>
            <button class="close-btn" onclick="closeModal('tambahModal')">&times;</button>
        </div>
        <form method="POST" id="tambahForm">
            <div class="row">
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" required>
                </div>
                <div class="form-group">
                    <label for="kelas">Kelas</label>
                    <input type="text" class="form-control" id="kelas" name="kelas" required>
                </div>
            </div>
            <div class="row">
                <div class="form-group">
                    <label for="nis">NIS (Nomor Induk Siswa)</label>
                    <input type="text" class="form-control" id="nis" name="nis" required maxlength="20">
                    <div class="small-hint">Format: 12345678 (maksimal 20 karakter)</div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" readonly>
                    <div class="small-hint">Otomatis diisi dengan NIS</div>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="text" class="form-control" id="password" name="password" readonly>
                <div class="small-hint">Otomatis diisi dengan NIS</div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('tambahModal')">Batal</button>
                <button type="submit" name="tambah_pemilih" class="btn btn-success" id="tambahSubmit">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Edit Pemilih -->
    <div class="modal-overlay" id="editModalOverlay" onclick="closeModal('editModal')"></div>
    <div class="modal" id="editModal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Pemilih</h3>
            <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" id="editForm">
            <input type="hidden" id="edit_id_pemilih" name="id_pemilih">
            <div class="row">
                <div class="form-group">
                    <label for="edit_nama">Nama Lengkap</label>
                    <input type="text" class="form-control" id="edit_nama" name="nama" required>
                </div>
                <div class="form-group">
                    <label for="edit_kelas">Kelas</label>
                    <input type="text" class="form-control" id="edit_kelas" name="kelas" required>
                </div>
            </div>
            <div class="row">
                <div class="form-group">
                    <label for="edit_nis">NIS (Nomor Induk Siswa)</label>
                    <input type="text" class="form-control" id="edit_nis" name="nis" required maxlength="20">
                    <div class="small-hint">Format: 12345678 (maksimal 20 karakter)</div>
                </div>
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" class="form-control" id="edit_username" name="username" readonly>
                    <div class="small-hint">Otomatis diisi dengan NIS</div>
                </div>
            </div>
            <div class="form-group">
                <label for="edit_password">Password</label>
                <input type="text" class="form-control" id="edit_password" name="password" readonly>
                <div class="small-hint">Otomatis diisi dengan NIS</div>
            </div>
            <div class="form-group">
                <label for="edit_status_voting">Status Voting</label>
                <input type="text" class="form-control" id="edit_status_voting" name="status_voting" readonly 
                       value="<?php echo $edit_pemilih ? ($edit_pemilih['status_voting'] ? 'Sudah Memilih' : 'Belum Memilih') : ''; ?>">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" name="edit_pemilih" class="btn btn-warning">
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
            
            if (modalId === 'tambahModal') {
                document.getElementById('tambahForm').reset();
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
                document.getElementById('nis').style.borderColor = '#e5e7eb';
            }
        }

        // Auto-fill username dan password dari NIS
        document.getElementById('nis').addEventListener('input', function() {
            document.getElementById('username').value = this.value;
            document.getElementById('password').value = this.value;
            checkNis(this.value);
        });

        document.getElementById('edit_nis').addEventListener('input', function() {
            document.getElementById('edit_username').value = this.value;
            document.getElementById('edit_password').value = this.value;
        });

        // Check NIS for duplicates via AJAX
        function checkNis(nis) {
            if (!nis) return;
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'pemilih.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    const nisField = document.getElementById('nis');
                    const submitButton = document.getElementById('tambahSubmit');
                    
                    if (response.exists) {
                        nisField.style.borderColor = '#dc2626';
                        alert('Peringatan: NIS sudah digunakan!');
                        submitButton.disabled = true;
                    } else {
                        nisField.style.borderColor = '#e5e7eb';
                        submitButton.disabled = false;
                    }
                }
            };
            xhr.send('check_nis=true&nis=' + encodeURIComponent(nis));
        }

        // Edit pemilih function
        function editPemilih(pemilih) {
            document.getElementById('edit_id_pemilih').value = pemilih.id;
            document.getElementById('edit_nama').value = pemilih.nama;
            document.getElementById('edit_kelas').value = pemilih.kelas;
            document.getElementById('edit_nis').value = pemilih.nis;
            document.getElementById('edit_username').value = pemilih.nis;
            document.getElementById('edit_password').value = pemilih.nis;
            document.getElementById('edit_status_voting').value = pemilih.status_voting ? 'Sudah Memilih' : 'Belum Memilih';
            
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
                    const nisField = form.querySelector('input[name="nis"]');
                    let hasError = false;
                    
                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            hasError = true;
                            field.style.borderColor = '#dc2626';
                        } else {
                            field.style.borderColor = '#e5e7eb';
                        }
                    });
                    
                    // Validasi NIS (hanya angka)
                    if (nisField && nisField.value && !/^\d+$/.test(nisField.value)) {
                        hasError = true;
                        nisField.style.borderColor = '#dc2626';
                        alert('NIS harus berupa angka saja!');
                    }
                    
                    if (hasError) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>