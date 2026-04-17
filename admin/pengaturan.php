<?php
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

// ========== PROSES AKUN WARGA ==========
// Tambah akun warga
if (isset($_POST['tambah_akun'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'warga';
    $status = 'aktif';
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error_akun = "Username atau email sudah terdaftar!";
    } else {
        $query = "INSERT INTO users (nama, username, email, password, role, status) VALUES ('$nama', '$username', '$email', '$password', '$role', '$status')";
        if (mysqli_query($conn, $query)) {
            $success_akun = "Akun berhasil ditambahkan!";
        } else {
            $error_akun = "Gagal menambahkan akun: " . mysqli_error($conn);
        }
    }
}

// Edit akun warga
if (isset($_POST['edit_akun'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE (username='$username' OR email='$email') AND id != $id");
    if (mysqli_num_rows($check) > 0) {
        $error_edit = "Username atau email sudah digunakan oleh akun lain!";
    } else {
        $query = "UPDATE users SET nama='$nama', username='$username', email='$email', status='$status' WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $success_edit = "Akun berhasil diperbarui!";
        } else {
            $error_edit = "Gagal memperbarui akun: " . mysqli_error($conn);
        }
    }
}

// Reset password akun
if (isset($_POST['reset_password'])) {
    $id = (int)$_POST['id'];
    $new_password = password_hash('warga123', PASSWORD_DEFAULT);
    $query = "UPDATE users SET password='$new_password' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        $success_reset = "Password berhasil direset menjadi 'warga123'";
    } else {
        $error_reset = "Gagal mereset password";
    }
}

// Toggle status akun
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $current_status = mysqli_real_escape_string($conn, $_GET['status']);
    $new_status = $current_status == 'aktif' ? 'nonaktif' : 'aktif';
    
    $query = "UPDATE users SET status='$new_status' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        if ($id == $_SESSION['user_id']) {
            session_destroy();
            header("Location: ../auth/login.php?error=Akun Anda telah dinonaktifkan oleh admin");
            exit();
        }
        $_SESSION['success_message'] = "Status akun berhasil diubah menjadi " . ($new_status == 'aktif' ? 'Aktif' : 'Nonaktif');
    } else {
        $_SESSION['error_message'] = "Gagal mengubah status akun";
    }
    header("Location: pengaturan.php");
    exit();
}

// Hapus akun
if (isset($_GET['hapus_akun'])) {
    $id = (int)$_GET['hapus_akun'];
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "Anda tidak dapat menghapus akun sendiri!";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
        $_SESSION['success_message'] = "Akun berhasil dihapus!";
    }
    header("Location: pengaturan.php");
    exit();
}

$query_warga = "SELECT * FROM users WHERE role='warga' ORDER BY id DESC";
$result_warga = mysqli_query($conn, $query_warga);

// ========== PROSES KATEGORI ==========
if (isset($_POST['tambah_kategori'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    if (!empty($nama)) {
        $query = "INSERT INTO kategori_pengaduan (nama, deskripsi) VALUES ('$nama', '$deskripsi')";
        mysqli_query($conn, $query);
    }
    header("Location: pengaturan.php");
    exit();
}

if (isset($_POST['edit_kategori'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $query = "UPDATE kategori_pengaduan SET nama='$nama', deskripsi='$deskripsi' WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: pengaturan.php");
    exit();
}

if (isset($_GET['hapus_kategori'])) {
    $id = (int)$_GET['hapus_kategori'];
    $check = mysqli_query($conn, "SELECT id FROM pengaduan WHERE kategori='$id'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "DELETE FROM kategori_pengaduan WHERE id=$id");
    }
    header("Location: pengaturan.php");
    exit();
}

$query_kategori = "SELECT * FROM kategori_pengaduan ORDER BY id DESC";
$result_kategori = mysqli_query($conn, $query_kategori);
if (!$result_kategori) {
    $create = "CREATE TABLE IF NOT EXISTS kategori_pengaduan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(50) NOT NULL UNIQUE,
        deskripsi TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create);
    $defaults = [
        ['Kebersihan', 'Laporan terkait kebersihan lingkungan'],
        ['Keamanan', 'Laporan terkait keamanan lingkungan'],
        ['Infrastruktur', 'Laporan terkait infrastruktur dan fasilitas umum'],
        ['Sosial', 'Laporan terkait sosial kemasyarakatan'],
        ['Lainnya', 'Kategori lainnya']
    ];
    foreach ($defaults as $d) {
        mysqli_query($conn, "INSERT IGNORE INTO kategori_pengaduan (nama, deskripsi) VALUES ('$d[0]', '$d[1]')");
    }
    $result_kategori = mysqli_query($conn, $query_kategori);
}

// ========== PROSES FAQ / BANTUAN ==========
$create_faq = "CREATE TABLE IF NOT EXISTS bantuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    kategori VARCHAR(50) DEFAULT 'umum',
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_faq);

if (isset($_POST['tambah_faq'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $urutan = (int)$_POST['urutan'];
    $query = "INSERT INTO bantuan (judul, konten, kategori, urutan) VALUES ('$judul', '$konten', '$kategori', '$urutan')";
    mysqli_query($conn, $query);
    header("Location: pengaturan.php");
    exit();
}

if (isset($_POST['edit_faq'])) {
    $id = (int)$_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $urutan = (int)$_POST['urutan'];
    $query = "UPDATE bantuan SET judul='$judul', konten='$konten', kategori='$kategori', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: pengaturan.php");
    exit();
}

if (isset($_GET['hapus_faq'])) {
    $id = (int)$_GET['hapus_faq'];
    mysqli_query($conn, "DELETE FROM bantuan WHERE id=$id");
    header("Location: pengaturan.php");
    exit();
}

$query_faq = "SELECT * FROM bantuan ORDER BY urutan ASC, id DESC";
$result_faq = mysqli_query($conn, $query_faq);

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Pengaturan Admin - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #213C51; --primary-light: #2A4D67; --secondary: #6594B1;
            --success: #10B981; --warning: #F59E0B; --danger: #EF4444;
            --gray-50: #F9FAFB; --gray-100: #F3F4F6; --gray-200: #E5E7EB;
            --gray-300: #D1D5DB; --gray-400: #9CA3AF; --gray-500: #6B7280;
            --gray-600: #4B5563; --gray-700: #374151; --gray-800: #1F2937;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-800); display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%); position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; display: flex; flex-direction: column; overflow-y: auto; box-shadow: var(--shadow-lg); }
        .sidebar .logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar .logo-icon { width: 40px; height: 40px; background: var(--secondary); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
        .sidebar .logo-text h2 { font-size: 18px; font-weight: 700; color: white; }
        .sidebar .logo-text p { font-size: 12px; color: rgba(255,255,255,0.6); }
        .sidebar .nav-menu { flex: 1; padding: 0 12px; }
        .sidebar .nav-menu a { display: flex; align-items: center; gap: 12px; padding: 12px; margin-bottom: 4px; color: rgba(255,255,255,0.7); text-decoration: none; border-radius: 10px; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .sidebar .nav-menu a:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .sidebar .nav-menu a.active { background: var(--secondary); color: white; }
        .sidebar .user-profile { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto; }
        .sidebar .user-profile a { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .user-profile .avatar { width: 40px; height: 40px; background: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .sidebar .user-profile .info h4 { font-size: 14px; font-weight: 600; color: white; }
        .sidebar .user-profile .info p { font-size: 12px; color: rgba(255,255,255,0.6); }
        .sidebar .logout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; background: rgba(239,68,68,0.2); color: #FCA5A5; padding: 10px; border-radius: 8px; text-decoration: none; margin: 12px; }
        .sidebar .logout-btn:hover { background: var(--danger); color: white; }
        
        .main-content { flex: 1; margin-left: 280px; padding: 24px 32px; width: calc(100% - 280px); }
        .container { max-width: 1400px; width: 100%; margin: 0 auto; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; animation: fadeInLeft 0.5s ease-out; }
        .page-header-left { display: flex; align-items: center; gap: 15px; }
        .page-header-left i { font-size: 28px; color: var(--secondary); animation: pulse 2s infinite; }
        .page-header-left h1 { font-size: 28px; font-weight: 700; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .back-btn { background: var(--secondary); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .back-btn:hover { background: var(--primary); transform: translateY(-3px); }
        
        .alert { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; animation: fadeInUp 0.3s ease-out; }
        .alert-success { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0; }
        .alert-danger { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }
        
        .card { background: white; border-radius: 16px; padding: 24px; margin-bottom: 28px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm); transition: all 0.3s; animation: fadeInUp 0.6s ease-out; animation-fill-mode: both; }
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        
        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid var(--gray-200); }
        .card-header i { font-size: 22px; color: var(--secondary); transition: transform 0.3s; }
        .card:hover .card-header i { transform: scale(1.1) rotate(5deg); }
        .card-header h3 { font-size: 18px; font-weight: 700; color: var(--gray-800); }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: var(--gray-700); }
        input, textarea, select { width: 100%; padding: 10px 14px; border: 1px solid var(--gray-300); border-radius: 8px; background: white; color: var(--gray-700); font-size: 14px; transition: all 0.3s; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(101,148,177,0.2); transform: translateY(-2px); }
        
        .btn { background: var(--secondary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { background: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-danger { background: #FEE2E2; color: #DC2626; }
        .btn-danger:hover { background: #FECACA; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        /* Akun Grid */
        .akun-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; margin-top: 20px; }
        .akun-card { background: var(--gray-50); border-radius: 16px; padding: 20px; border: 1px solid var(--gray-200); transition: all 0.3s; animation: scaleIn 0.4s ease-out forwards; opacity: 0; }
        .akun-card:nth-child(1) { animation-delay: 0.05s; }
        .akun-card:nth-child(2) { animation-delay: 0.1s; }
        .akun-card:nth-child(3) { animation-delay: 0.15s; }
        .akun-card:nth-child(4) { animation-delay: 0.2s; }
        .akun-card:nth-child(5) { animation-delay: 0.25s; }
        .akun-card:nth-child(6) { animation-delay: 0.3s; }
        .akun-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--secondary); }
        .akun-card .avatar-badge { width: 60px; height: 60px; background: linear-gradient(135deg, var(--secondary), var(--primary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
        .akun-card .avatar-badge i { font-size: 28px; color: white; }
        .akun-card .akun-name { font-size: 18px; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
        .akun-card .akun-username { font-size: 13px; color: var(--gray-500); margin-bottom: 12px; }
        .akun-card .akun-info { font-size: 13px; color: var(--gray-600); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .akun-card .akun-info i { width: 18px; color: var(--secondary); }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-bottom: 15px; }
        .status-aktif { background: #D1FAE5; color: #059669; }
        .status-nonaktif { background: #FEE2E2; color: #DC2626; }
        .akun-card .card-actions { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--gray-200); flex-wrap: wrap; }
        
        /* Table Styles - DIPERBAIKI */
        .table-wrapper { overflow-x: auto; margin-top: 20px; border-radius: 12px; border: 1px solid var(--gray-200); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 14px 16px; background: var(--gray-50); font-weight: 700; font-size: 13px; color: var(--gray-600); border-bottom: 1px solid var(--gray-200); }
        td { padding: 12px 16px; border-bottom: 1px solid var(--gray-100); color: var(--gray-700); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: linear-gradient(90deg, rgba(101,148,177,0.05) 0%, rgba(101,148,177,0.1) 100%); }
        
        .two-columns { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        @media (max-width: 900px) { .two-columns { grid-template-columns: 1fr; gap: 20px; } }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; animation: fadeInUp 0.3s ease-out; }
        .modal-content { background: white; border-radius: 20px; width: 90%; max-width: 550px; padding: 28px; box-shadow: var(--shadow-lg); animation: scaleIn 0.3s ease-out; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { font-size: 20px; font-weight: 700; color: var(--gray-800); }
        .close-modal { font-size: 24px; cursor: pointer; color: var(--gray-500); transition: 0.3s; }
        .close-modal:hover { color: var(--danger); transform: rotate(90deg); }
        
        .footer { background: white; border-top: 1px solid var(--gray-200); padding: 24px 20px; margin-top: 40px; text-align: center; color: var(--gray-500); }
        
        .menu-toggle { display: none; position: fixed; top: 16px; left: 16px; z-index: 1001; background: var(--primary); border: none; color: white; width: 44px; height: 44px; border-radius: 10px; font-size: 20px; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99; }
        
        @media (max-width: 768px) {
            .menu-toggle { display: flex; align-items: center; justify-content: center; }
            .sidebar { transform: translateX(-100%); z-index: 200; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; width: 100%; padding: 70px 16px 20px 16px; }
            .page-header { flex-direction: column; text-align: center; }
            .akun-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h2>e-RT Digital</h2><p>Panel Admin</p></div>
        </div>
        <div class="nav-menu">
            <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
            <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
            <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Layanan Surat</a>
            <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
            <a href="pengaturan.php" class="active"><i class="fas fa-cog"></i> Pengaturan</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div class="info"><h4><?php echo htmlspecialchars($user['nama']); ?></h4><p><?php echo htmlspecialchars($user['email']); ?></p></div>
            </a>
        </div>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <div class="page-header-left"><i class="fas fa-cog"></i><h1>Pengaturan Admin</h1></div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if (isset($success_akun)): ?>
                <div class="alert alert-success"><?php echo $success_akun; ?></div>
            <?php endif; ?>
            <?php if (isset($error_akun)): ?>
                <div class="alert alert-danger"><?php echo $error_akun; ?></div>
            <?php endif; ?>
            <?php if (isset($success_edit)): ?>
                <div class="alert alert-success"><?php echo $success_edit; ?></div>
            <?php endif; ?>
            <?php if (isset($error_edit)): ?>
                <div class="alert alert-danger"><?php echo $error_edit; ?></div>
            <?php endif; ?>
            <?php if (isset($success_reset)): ?>
                <div class="alert alert-success"><?php echo $success_reset; ?></div>
            <?php endif; ?>
            <?php if (isset($error_reset)): ?>
                <div class="alert alert-danger"><?php echo $error_reset; ?></div>
            <?php endif; ?>

            <!-- 1. MANAJEMEN AKUN WARGA -->
            <div class="card">
                <div class="card-header"><i class="fas fa-users"></i><h3>Manajemen Akun Warga</h3></div>
                
                <form method="POST" style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--gray-200);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div><label>Nama Lengkap</label><input type="text" name="nama" placeholder="Nama warga" required></div>
                        <div><label>Username</label><input type="text" name="username" placeholder="Username" required></div>
                        <div><label>Email</label><input type="email" name="email" placeholder="Email" required></div>
                        <div><label>Password</label><input type="password" name="password" placeholder="Password" required></div>
                        <div style="display: flex; align-items: flex-end;"><button type="submit" name="tambah_akun" class="btn"><i class="fas fa-user-plus"></i> Tambah Akun</button></div>
                    </div>
                </form>

                <div class="akun-grid">
                    <?php 
                    mysqli_data_seek($result_warga, 0);
                    while ($w = mysqli_fetch_assoc($result_warga)): 
                    ?>
                    <div class="akun-card">
                        <div class="avatar-badge"><i class="fas fa-user"></i></div>
                        <div class="akun-name"><?php echo htmlspecialchars($w['nama']); ?></div>
                        <div class="akun-username">@<?php echo htmlspecialchars($w['username']); ?></div>
                        <div class="akun-info"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($w['email']); ?></div>
                        <div class="akun-info"><i class="fas fa-id-card"></i> ID: <?php echo $w['id']; ?></div>
                        <div><span class="status-badge status-<?php echo $w['status']; ?>"><?php echo ucfirst($w['status']); ?></span></div>
                        <div class="card-actions">
                            <button onclick="editAkun(<?php echo $w['id']; ?>)" class="btn btn-sm" style="background: var(--gray-200); color: var(--gray-700);"><i class="fas fa-edit"></i> Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $w['id']; ?>">
                                <button type="submit" name="reset_password" class="btn btn-sm" style="background: var(--warning); color: white;" onclick="return confirm('Reset password akun ini menjadi \'warga123\'?')"><i class="fas fa-key"></i> Reset Pass</button>
                            </form>
                            <a href="?toggle_status=<?php echo $w['id']; ?>&status=<?php echo $w['status']; ?>" class="btn btn-sm" style="background: var(--gray-200); color: var(--gray-700);" onclick="return confirm('<?php echo $w['status'] == 'aktif' ? 'Nonaktifkan akun ini? User tidak akan bisa login.' : 'Aktifkan akun ini? User bisa login kembali.'; ?>')"><i class="fas fa-<?php echo $w['status'] == 'aktif' ? 'ban' : 'check-circle'; ?>"></i> <?php echo $w['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?></a>
                            <a href="?hapus_akun=<?php echo $w['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus akun ini? Semua data terkait akan ikut terhapus.')"><i class="fas fa-trash"></i> Hapus</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($result_warga) == 0): ?>
                        <p style="grid-column: 1/-1; text-align: center; color: var(--gray-500); padding: 40px;">Belum ada akun warga.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. KATEGORI PENGADUAN -->
            <div class="card">
                <div class="card-header"><i class="fas fa-tags"></i><h3>Kategori Pengaduan</h3></div>
                <div class="two-columns">
                    <div>
                        <label style="margin-bottom: 15px; font-weight: 600; display: block;">Tambah Kategori Baru</label>
                        <form method="POST">
                            <div class="form-group"><input type="text" name="nama" placeholder="Nama kategori" required></div>
                            <div class="form-group"><textarea name="deskripsi" placeholder="Deskripsi (opsional)" rows="2"></textarea></div>
                            <button type="submit" name="tambah_kategori" class="btn"><i class="fas fa-plus"></i> Tambah Kategori</button>
                        </form>
                    </div>
                    <div>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr><th>No</th><th>Nama Kategori</th><th>Deskripsi</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($result_kategori, 0);
                                    $no_kat = 1; 
                                    while ($k = mysqli_fetch_assoc($result_kategori)): 
                                    ?>
                                    <tr>
                                        <td><?php echo $no_kat++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($k['nama']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                                        <td>
                                            <button onclick="editKategori(<?php echo $k['id']; ?>)" class="btn btn-sm" style="background: var(--gray-200); color: var(--gray-700);"><i class="fas fa-edit"></i> Edit</button>
                                            <a href="?hapus_kategori=<?php echo $k['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus? Pastikan tidak ada pengaduan dengan kategori ini.')"><i class="fas fa-trash"></i> Hapus</a>
                                         </td
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. FAQ / BANTUAN -->
            <div class="card">
                <div class="card-header"><i class="fas fa-question-circle"></i><h3>FAQ / Bantuan</h3></div>
                
                <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--gray-200);">
                    <label style="margin-bottom: 15px; font-weight: 600; display: block;">Tambah FAQ Baru</label>
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                            <div><input type="text" name="judul" placeholder="Judul FAQ" required></div>
                            <div>
                                <select name="kategori">
                                    <option value="umum">Umum</option>
                                    <option value="pengaduan">Pengaduan</option>
                                    <option value="surat">Surat</option>
                                    <option value="iuran">Iuran</option>
                                </select>
                            </div>
                            <div><input type="number" name="urutan" placeholder="Urutan (0 = teratas)" value="0"></div>
                        </div>
                        <div class="form-group"><textarea name="konten" placeholder="Konten / Jawaban" rows="2" required></textarea></div>
                        <button type="submit" name="tambah_faq" class="btn"><i class="fas fa-plus"></i> Tambah FAQ</button>
                    </form>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul FAQ</th>
                                <th>Kategori</th>
                                <th>Urutan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($result_faq, 0);
                            $no_faq = 1; 
                            while ($f = mysqli_fetch_assoc($result_faq)): 
                            ?>
                            <tr>
                                <td><?php echo $no_faq++; ?></td>
                                <td><strong><?php echo htmlspecialchars($f['judul']); ?></strong></td>
                                <td><?php echo $f['kategori']; ?></td>
                                <td><?php echo $f['urutan']; ?></td>
                                <td>
                                    <a href="#" onclick="editFaq(<?php echo $f['id']; ?>)" class="btn btn-sm" style="background: var(--gray-200); color: var(--gray-700);"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="?hapus_faq=<?php echo $f['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus FAQ ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                 </td
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Akun -->
    <div id="editAkunModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Akun Warga</h3><span class="close-modal" onclick="closeEditAkun()">&times;</span></div>
            <form method="POST" id="editAkunForm">
                <input type="hidden" name="id" id="edit_akun_id">
                <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" id="edit_akun_nama" required></div>
                <div class="form-group"><label>Username</label><input type="text" name="username" id="edit_akun_username" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_akun_email" required></div>
                <div class="form-group"><label>Status</label><select name="status" id="edit_akun_status"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></div>
                <div style="display:flex; gap:12px; margin-top:20px;"><button type="submit" name="edit_akun" class="btn">Simpan Perubahan</button><button type="button" onclick="closeEditAkun()" class="btn" style="background: var(--gray-200); color: var(--gray-700);">Batal</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kategori -->
    <div id="editKategoriModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Kategori</h3><span class="close-modal" onclick="closeEditKategori()">&times;</span></div>
            <form method="POST" id="editKategoriForm">
                <input type="hidden" name="id" id="edit_kategori_id">
                <div class="form-group"><label>Nama Kategori</label><input type="text" name="nama" id="edit_kategori_nama" required></div>
                <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="edit_kategori_deskripsi" rows="2"></textarea></div>
                <div style="display:flex; gap:12px; margin-top:20px;"><button type="submit" name="edit_kategori" class="btn">Simpan Perubahan</button><button type="button" onclick="closeEditKategori()" class="btn" style="background: var(--gray-200); color: var(--gray-700);">Batal</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Edit FAQ -->
    <div id="editFaqModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit FAQ</h3><span class="close-modal" onclick="closeEditFaq()">&times;</span></div>
            <form method="POST" id="editFaqForm">
                <input type="hidden" name="id" id="edit_faq_id">
                <div class="form-group"><label>Judul</label><input type="text" name="judul" id="edit_faq_judul" required></div>
                <div class="form-group"><label>Konten</label><textarea name="konten" id="edit_faq_konten" rows="3" required></textarea></div>
                <div class="form-group"><label>Kategori</label><select name="kategori" id="edit_faq_kategori"><option value="umum">Umum</option><option value="pengaduan">Pengaduan</option><option value="surat">Surat</option><option value="iuran">Iuran</option></select></div>
                <div class="form-group"><label>Urutan (semakin kecil semakin atas)</label><input type="number" name="urutan" id="edit_faq_urutan"></div>
                <div style="display:flex; gap:12px; margin-top:20px;"><button type="submit" name="edit_faq" class="btn">Simpan</button><button type="button" onclick="closeEditFaq()" class="btn" style="background: var(--gray-200); color: var(--gray-700);">Batal</button></div>
            </form>
        </div>
    </div>

    <script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() { sidebar.classList.add('active'); sidebarOverlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar.classList.remove('active'); sidebarOverlay.classList.remove('active'); document.body.style.overflow = ''; }
    if (menuToggle) menuToggle.addEventListener('click', openSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
    window.addEventListener('resize', function() { if (window.innerWidth > 768) closeSidebar(); });

    // Edit Akun
    function editAkun(id) {
        fetch('get_user.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_akun_id').value = data.data.id;
                    document.getElementById('edit_akun_nama').value = data.data.nama;
                    document.getElementById('edit_akun_username').value = data.data.username;
                    document.getElementById('edit_akun_email').value = data.data.email;
                    document.getElementById('edit_akun_status').value = data.data.status;
                    document.getElementById('editAkunModal').classList.add('active');
                } else {
                    alert('Gagal memuat data: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data. Pastikan file get_user.php ada.');
            });
    }
    function closeEditAkun() { document.getElementById('editAkunModal').classList.remove('active'); }

    // Edit Kategori
    function editKategori(id) {
        fetch('get_kategori.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_kategori_id').value = data.data.id;
                    document.getElementById('edit_kategori_nama').value = data.data.nama;
                    document.getElementById('edit_kategori_deskripsi').value = data.data.deskripsi || '';
                    document.getElementById('editKategoriModal').classList.add('active');
                } else {
                    alert('Gagal memuat data: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data. Pastikan file get_kategori.php ada.');
            });
    }
    function closeEditKategori() { document.getElementById('editKategoriModal').classList.remove('active'); }

    // Edit FAQ
    function editFaq(id) {
        fetch('get_faq.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_faq_id').value = data.data.id;
                    document.getElementById('edit_faq_judul').value = data.data.judul;
                    document.getElementById('edit_faq_konten').value = data.data.konten;
                    document.getElementById('edit_faq_kategori').value = data.data.kategori;
                    document.getElementById('edit_faq_urutan').value = data.data.urutan;
                    document.getElementById('editFaqModal').classList.add('active');
                } else {
                    alert('Gagal memuat data: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data. Pastikan file get_faq.php ada.');
            });
    }
    function closeEditFaq() { document.getElementById('editFaqModal').classList.remove('active'); }

    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        const modalAkun = document.getElementById('editAkunModal');
        const modalKategori = document.getElementById('editKategoriModal');
        const modalFaq = document.getElementById('editFaqModal');
        if (event.target === modalAkun) closeEditAkun();
        if (event.target === modalKategori) closeEditKategori();
        if (event.target === modalFaq) closeEditFaq();
    }
</script>
</body>
</html>