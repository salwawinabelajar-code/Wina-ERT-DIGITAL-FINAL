<?php
// profil.php - Halaman Profil Admin
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

$message = '';
$error = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profil';

// ========== PROSES EDIT PROFIL ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    if (empty($nama) || empty($email)) {
        $error = "Nama dan email harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        $query = "UPDATE users SET nama='$nama', email='$email', no_hp='$no_hp', alamat='$alamat' WHERE id=$user_id";
        if (mysqli_query($conn, $query)) {
            $message = "Profil berhasil diperbarui!";
            // Refresh data user
            $result_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
            $user = mysqli_fetch_assoc($result_user);
            $_SESSION['nama'] = $user['nama'];
        } else {
            $error = "Gagal memperbarui profil: " . mysqli_error($conn);
        }
    }
}

// ========== PROSES UBAH PASSWORD ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $password_konfirmasi = $_POST['password_konfirmasi'];
    
    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        $error = "Semua field password harus diisi!";
    } elseif (strlen($password_baru) < 8) {
        $error = "Password baru minimal 8 karakter!";
    } elseif ($password_baru !== $password_konfirmasi) {
        $error = "Password baru dan konfirmasi tidak cocok!";
    } elseif (!password_verify($password_lama, $user['password'])) {
        $error = "Password lama salah!";
    } else {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password='$password_hash' WHERE id=$user_id";
        if (mysqli_query($conn, $query)) {
            $message = "Password berhasil diubah!";
        } else {
            $error = "Gagal mengubah password: " . mysqli_error($conn);
        }
    }
}

// ========== AMBIL STATISTIK AKTIVITAS ADMIN ==========
// Total pengaduan yang sudah diproses
$query_total_pengaduan = "SELECT COUNT(*) as total FROM pengaduan";
$result_total_pengaduan = mysqli_query($conn, $query_total_pengaduan);
$total_pengaduan = $result_total_pengaduan ? mysqli_fetch_assoc($result_total_pengaduan)['total'] : 0;

// Total surat yang sudah diproses
$query_total_surat = "SELECT COUNT(*) as total FROM pengajuan_surat";
$result_total_surat = mysqli_query($conn, $query_total_surat);
$total_surat = $result_total_surat ? mysqli_fetch_assoc($result_total_surat)['total'] : 0;

// Total warga terdaftar
$query_total_warga = "SELECT COUNT(*) as total FROM users WHERE role='warga'";
$result_total_warga = mysqli_query($conn, $query_total_warga);
$total_warga = $result_total_warga ? mysqli_fetch_assoc($result_total_warga)['total'] : 0;

// Aktivitas terbaru (pengaduan masuk 7 hari terakhir)
$query_aktivitas = "SELECT COUNT(*) as total FROM pengaduan WHERE tanggal >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$result_aktivitas = mysqli_query($conn, $query_aktivitas);
$aktivitas_terbaru = $result_aktivitas ? mysqli_fetch_assoc($result_aktivitas)['total'] : 0;

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Profil Admin - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #213C51;
            --primary-light: #2A4D67;
            --secondary: #6594B1;
            --secondary-light: #7EA8C2;
            --accent: #5A8FB7;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-100);
            color: var(--gray-800);
            display: flex;
            min-height: 100vh;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 280px;
            background: var(--primary);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar .logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--secondary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .sidebar .logo-text h2 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .sidebar .logo-text p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }

        .sidebar .nav-menu {
            flex: 1;
            padding: 0 12px;
        }

        .sidebar .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 12px;
            margin-bottom: 4px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar .nav-menu a i {
            width: 20px;
            font-size: 16px;
        }

        .sidebar .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-menu a.active {
            background: var(--secondary);
            color: white;
        }

        .sidebar .user-profile {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        .sidebar .user-profile a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar .user-profile .avatar {
            width: 40px;
            height: 40px;
            background: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .sidebar .user-profile .info h4 {
            font-size: 14px;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        .sidebar .user-profile .info p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }

        .sidebar .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-top: 12px;
            transition: all 0.2s;
        }

        .sidebar .logout-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 24px 32px;
            width: calc(100% - 280px);
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header-left i {
            font-size: 28px;
            color: var(--secondary);
        }

        .page-header-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .back-btn {
            background: var(--secondary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #D1FAE5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: white;
            box-shadow: var(--shadow-md);
            border: 4px solid white;
        }

        .profile-info h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 8px;
        }

        .profile-info .role {
            display: inline-block;
            background: #DBEAFE;
            color: #2563EB;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .profile-info p {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary);
        }

        .stat-card i {
            font-size: 32px;
            color: var(--secondary);
            margin-bottom: 12px;
        }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 5px;
        }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            background: white;
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 24px;
            border: 1px solid var(--gray-200);
            gap: 5px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover {
            background: var(--gray-100);
            color: var(--gray-800);
        }

        .tab-btn.active {
            background: var(--secondary);
            color: white;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .form-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
            color: var(--gray-700);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(101, 148, 177, 0.2);
        }

        .btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--gray-200);
            color: var(--gray-600);
        }

        .btn-danger:hover {
            background: var(--gray-300);
            transform: translateY(-2px);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 24px 20px;
            margin-top: 40px;
            text-align: center;
            color: var(--gray-500);
        }

        .footer .registered {
            font-weight: 600;
            font-size: 13px;
            color: var(--gray-600);
        }

        .footer .copyright {
            font-size: 11px;
            margin-top: 6px;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1001;
            background: var(--primary);
            border: none;
            color: white;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .sidebar {
                transform: translateX(-100%);
                z-index: 200;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .sidebar-overlay.active {
                display: block;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 70px 16px 20px 16px;
            }
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .tab-navigation {
                flex-direction: column;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay untuk mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Tombol toggle mobile -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text">
                <h2>e-RT Digital</h2>
                <p>Panel Admin</p>
            </div>
        </div>
        <div class="nav-menu">
            <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
            <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
            <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
            <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
            <a href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div class="info">
                    <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </a>
        </div>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <i class="fas fa-user-circle"></i>
                    <h1>Profil Admin</h1>
                </div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['nama'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
                    <span class="role"><i class="fas fa-shield-alt"></i> Administrator</span>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['no_hp'] ?? '-'); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['alamat'] ?? '-'); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Bergabung sejak <?php echo date('d F Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></p>
                </div>
            </div>

            <!-- Statistik Aktivitas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <div class="stat-number"><?php echo $total_pengaduan; ?></div>
                    <div class="stat-label">Total Pengaduan</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-envelope"></i>
                    <div class="stat-number"><?php echo $total_surat; ?></div>
                    <div class="stat-label">Total Surat</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-number"><?php echo $total_warga; ?></div>
                    <div class="stat-label">Warga Terdaftar</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line"></i>
                    <div class="stat-number"><?php echo $aktivitas_terbaru; ?></div>
                    <div class="stat-label">Aktivitas 7 Hari</div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn <?php echo $active_tab == 'profil' ? 'active' : ''; ?>" onclick="changeTab('profil')">
                    <i class="fas fa-user-edit"></i> Edit Profil
                </button>
                <button class="tab-btn <?php echo $active_tab == 'password' ? 'active' : ''; ?>" onclick="changeTab('password')">
                    <i class="fas fa-lock"></i> Ubah Password
                </button>
                <button class="tab-btn <?php echo $active_tab == 'info' ? 'active' : ''; ?>" onclick="changeTab('info')">
                    <i class="fas fa-info-circle"></i> Informasi Akun
                </button>
            </div>

            <!-- Tab Edit Profil -->
            <div id="tabProfil" class="form-card" <?php echo $active_tab != 'profil' ? 'style="display:none;"' : ''; ?>>
                <h3><i class="fas fa-user-edit"></i> Edit Profil</h3>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nomor Telepon</label>
                            <input type="tel" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>" placeholder="Contoh: 081234567890">
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <input type="text" name="alamat" class="form-control" value="<?php echo htmlspecialchars($user['alamat'] ?? ''); ?>" placeholder="Alamat lengkap">
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="submit" name="update_profil" class="btn"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            <!-- Tab Ubah Password -->
            <div id="tabPassword" class="form-card" <?php echo $active_tab != 'password' ? 'style="display:none;"' : ''; ?>>
                <h3><i class="fas fa-key"></i> Ubah Password</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password lama" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" placeholder="Minimal 8 karakter" required>
                        <small style="color: var(--gray-500);">Password minimal 8 karakter, kombinasi huruf dan angka</small>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="password_konfirmasi" class="form-control" placeholder="Ulangi password baru" required>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="submit" name="update_password" class="btn"><i class="fas fa-save"></i> Ubah Password</button>
                    </div>
                </form>
            </div>

            <!-- Tab Informasi Akun -->
            <div id="tabInfo" class="form-card" <?php echo $active_tab != 'info' ? 'style="display:none;"' : ''; ?>>
                <h3><i class="fas fa-info-circle"></i> Informasi Akun</h3>
                <div style="display: grid; gap: 16px;">
                    <div style="display: flex; padding: 12px; background: var(--gray-50); border-radius: 10px;">
                        <div style="width: 140px; font-weight: 600; color: var(--gray-600);">Username</div>
                        <div style="flex: 1; color: var(--gray-800);"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    <div style="display: flex; padding: 12px; background: var(--gray-50); border-radius: 10px;">
                        <div style="width: 140px; font-weight: 600; color: var(--gray-600);">Role / Hak Akses</div>
                        <div style="flex: 1; color: var(--gray-800);">
                            <span style="background: #DBEAFE; color: #2563EB; padding: 2px 10px; border-radius: 20px; font-size: 12px;">Administrator</span>
                        </div>
                    </div>
                    <div style="display: flex; padding: 12px; background: var(--gray-50); border-radius: 10px;">
                        <div style="width: 140px; font-weight: 600; color: var(--gray-600);">Status Akun</div>
                        <div style="flex: 1; color: var(--gray-800);">
                            <span style="background: #D1FAE5; color: #059669; padding: 2px 10px; border-radius: 20px; font-size: 12px;">Aktif</span>
                        </div>
                    </div>
                    <div style="display: flex; padding: 12px; background: var(--gray-50); border-radius: 10px;">
                        <div style="width: 140px; font-weight: 600; color: var(--gray-600);">ID Pengguna</div>
                        <div style="flex: 1; color: var(--gray-800);"><?php echo $user['id']; ?></div>
                    </div>
                    <div style="display: flex; padding: 12px; background: var(--gray-50); border-radius: 10px;">
                        <div style="width: 140px; font-weight: 600; color: var(--gray-600);">Tanggal Bergabung</div>
                        <div style="flex: 1; color: var(--gray-800);"><?php echo date('d F Y, H:i:s', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></div>
                    </div>
                    <div style="display: flex; padding: 12px; background: var(--gray-50); border-radius: 10px;">
                        <div style="width: 140px; font-weight: 600; color: var(--gray-600);">Terakhir Update</div>
                        <div style="flex: 1; color: var(--gray-800);"><?php echo date('d F Y, H:i:s', strtotime($user['updated_at'] ?? date('Y-m-d'))); ?></div>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', openSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

        const sidebarLinks = document.querySelectorAll('.sidebar .nav-menu a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

        // Tab Navigation
        function changeTab(tab) {
            const tabProfil = document.getElementById('tabProfil');
            const tabPassword = document.getElementById('tabPassword');
            const tabInfo = document.getElementById('tabInfo');
            
            if (tab === 'profil') {
                tabProfil.style.display = 'block';
                tabPassword.style.display = 'none';
                tabInfo.style.display = 'none';
            } else if (tab === 'password') {
                tabProfil.style.display = 'none';
                tabPassword.style.display = 'block';
                tabInfo.style.display = 'none';
            } else {
                tabProfil.style.display = 'none';
                tabPassword.style.display = 'none';
                tabInfo.style.display = 'block';
            }
            
            // Update URL tanpa reload
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }
    </script>
</body>
</html>