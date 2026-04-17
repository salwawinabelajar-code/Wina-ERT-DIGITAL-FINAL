<?php
session_start();

// Konfigurasi database
require_once(__DIR__ . '/../config/db.php');

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $query_user);
if ($stmt_user) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);
}

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$role = $user['role'] ?? 'warga';
$success_msg = '';
$error_msg = '';

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');

    if (empty($nama) || empty($email)) {
        $error_msg = 'Nama dan email harus diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Format email tidak valid.';
    } else {
        $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error_msg = 'Email sudah digunakan oleh pengguna lain.';
        } else {
            $update = "UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $update);
            mysqli_stmt_bind_param($stmt_update, "sssssssi", $nama, $email, $no_hp, $alamat, $tempat_lahir, $tanggal_lahir, $jenis_kelamin, $user_id);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $success_msg = 'Profil berhasil diperbarui.';
                // Refresh data user
                $stmt_user = mysqli_prepare($conn, $query_user);
                mysqli_stmt_bind_param($stmt_user, "i", $user_id);
                mysqli_stmt_execute($stmt_user);
                $result_user = mysqli_stmt_get_result($stmt_user);
                $user = mysqli_fetch_assoc($result_user);
                mysqli_stmt_close($stmt_user);
            } else {
                $error_msg = 'Gagal memperbarui profil.';
            }
            mysqli_stmt_close($stmt_update);
        }
        mysqli_stmt_close($stmt_check);
    }
}

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error_msg = 'Semua field password harus diisi.';
    } elseif ($new !== $confirm) {
        $error_msg = 'Konfirmasi password baru tidak cocok.';
    } elseif (strlen($new) < 6) {
        $error_msg = 'Password baru minimal 6 karakter.';
    } else {
        if (password_verify($current, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $update = "UPDATE users SET password = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $update);
            mysqli_stmt_bind_param($stmt_update, "si", $hash, $user_id);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $success_msg = 'Password berhasil diubah.';
            } else {
                $error_msg = 'Gagal mengubah password.';
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error_msg = 'Password lama salah.';
        }
    }
}

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Profil Saya - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary: #5A7863;
            --primary-dark: #3B4953;
            --primary-light: #90AB8B;
            --secondary: #90AB8B;
            --accent: #A8BF9A;
            --bg-soft: #EBF4DD;
            --light: #F8F9FA;
            --dark: #3B4953;
            --gray: #7A8E7A;
            --danger: #D98A6C;
            --warning: #E0B87A;
            --success: #7DA06E;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 12px 28px rgba(0, 0, 0, 0.12);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #EBF4DD 0%, #90AB8B 50%, #5A7863 100%);
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 40%, rgba(235, 244, 221, 0.15) 0%, rgba(90, 120, 99, 0.1) 100%);
            pointer-events: none;
            z-index: -1;
        }

        /* ========== NAVBAR ========== */
        .navbar {
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.92), rgba(59, 73, 83, 0.88));
            backdrop-filter: blur(16px);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-md);
            border-bottom: 1px solid rgba(235, 244, 221, 0.3);
            flex-wrap: wrap;
            gap: 12px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .logo-icon {
            background: linear-gradient(135deg, #EBF4DD, #90AB8B);
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3B4953;
            font-size: 22px;
            box-shadow: var(--shadow-sm);
        }

        .logo-text h1 {
            font-size: 1.3rem;
            color: #FFFFFF;
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .logo-text p {
            font-size: 0.7rem;
            color: rgba(235, 244, 221, 0.85);
            margin-top: 2px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 40px;
            transition: all 0.25s ease;
            background: rgba(235, 244, 221, 0.15);
            border: 1px solid rgba(235, 244, 221, 0.25);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu a i {
            font-size: 0.9rem;
        }

        .nav-menu a:hover {
            background: #90AB8B;
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background: #90AB8B;
            color: white;
            border-color: transparent;
            font-weight: 600;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(235, 244, 221, 0.15);
            padding: 6px 14px 6px 10px;
            border-radius: 60px;
            border: 1px solid rgba(235, 244, 221, 0.3);
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .user-profile:hover {
            background: rgba(235, 244, 221, 0.25);
            transform: translateY(-2px);
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #90AB8B, #5A7863);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #EBF4DD;
            font-weight: 700;
            font-size: 1rem;
            border: 2px solid #EBF4DD;
        }

        .avatar-img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #EBF4DD;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info h4 {
            font-size: 0.85rem;
            color: white;
            font-weight: 600;
        }

        .user-info small {
            font-size: 0.65rem;
            color: rgba(235, 244, 221, 0.85);
        }

        .logout-btn {
            background: rgba(217, 138, 108, 0.35);
            border: 1px solid rgba(235, 244, 221, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #D98A6C;
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            background: rgba(235, 244, 221, 0.15);
            border: 1px solid rgba(235, 244, 221, 0.3);
            border-radius: 40px;
            padding: 8px 14px;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.2s;
            align-items: center;
            gap: 8px;
        }

        .mobile-menu-btn span {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .mobile-dropdown {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, rgba(90, 120, 99, 0.98), rgba(59, 73, 83, 0.98));
            backdrop-filter: blur(20px);
            flex-direction: column;
            padding: 16px 20px;
            gap: 8px;
            transition: 0.3s ease;
            z-index: 999;
            box-shadow: var(--shadow-lg);
            border-radius: 0 0 24px 24px;
            display: none;
        }

        .mobile-dropdown.active {
            display: flex;
        }

        .mobile-dropdown a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 12px 16px;
            border-radius: 20px;
            transition: 0.2s;
            background: rgba(235, 244, 221, 0.12);
            border: 1px solid rgba(235, 244, 221, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }

        .mobile-dropdown a i {
            width: 24px;
        }

        .mobile-dropdown a:hover,
        .mobile-dropdown a.active {
            background: #90AB8B;
            color: white;
        }

        .mobile-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 10px;
            background: rgba(235, 244, 221, 0.1);
            border-radius: 20px;
            text-decoration: none;
        }

        .dropdown-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(3px);
            z-index: 998;
            display: none;
        }

        .dropdown-overlay.active {
            display: block;
        }

        @media (max-width: 1024px) {
            .nav-menu a {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            .nav-menu a i {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .navbar {
                padding: 12px 16px;
            }
            .nav-menu {
                display: none;
            }
            .mobile-menu-btn {
                display: flex;
            }
            .user-info {
                display: none;
            }
            .user-profile {
                padding: 6px 10px;
            }
            .logout-btn span {
                display: none;
            }
            .logout-btn {
                padding: 8px 12px;
            }
        }

        @media (min-width: 901px) {
            .mobile-dropdown, .dropdown-overlay {
                display: none !important;
            }
        }

        /* ===== KONTAINER UTAMA ===== */
        .container {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 20px;
        }

        /* ===== HEADER HALAMAN ===== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 14px 24px;
            background: #3B4953;
            border-radius: 50px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header-left i {
            font-size: 26px;
            color: #EBF4DD;
        }

        .page-header-left h1 {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }

        .back-btn {
            background: #90AB8B;
            border: 1px solid #5A7863;
            color: white;
            padding: 8px 18px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: #5A7863;
            transform: translateY(-2px);
        }

        /* ===== MESSAGE ===== */
        .message {
            padding: 12px 18px;
            border-radius: 30px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            background: #EBF4DD;
            border-left: 6px solid;
            border: 1px solid #90AB8B;
            color: #3B4953;
        }

        .message.success {
            border-left-color: #7DA06E;
        }

        .message.error {
            border-left-color: #D98A6C;
        }

        /* ===== GRID ===== */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }

        /* ===== CARD ===== */
        .settings-card {
            background: #EBF4DD;
            border-radius: 28px;
            padding: 28px;
            border: 1px solid #90AB8B;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .settings-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: #5A7863;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #90AB8B;
            color: #3B4953;
        }

        .card-icon {
            width: 55px;
            height: 55px;
            border-radius: 18px;
            background: #90AB8B;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #EBF4DD;
            border: 1px solid #5A7863;
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 700;
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #3B4953;
            font-size: 13px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: #90AB8B;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px 12px 45px;
            border: 2px solid #90AB8B;
            border-radius: 30px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
            color: #3B4953;
        }

        .form-control:focus {
            outline: none;
            border-color: #5A7863;
            box-shadow: 0 0 0 3px rgba(90, 120, 99, 0.2);
        }

        .form-control::placeholder {
            color: #A8BFA0;
        }

        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            cursor: pointer;
        }

        /* ===== BUTTON ===== */
        .btn {
            padding: 12px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
            box-shadow: 0 8px 20px rgba(59, 73, 83, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(59, 73, 83, 0.5);
        }

        .btn-secondary {
            background: #90AB8B;
            color: #3B4953;
            border: 1px solid #5A7863;
        }

        .btn-secondary:hover {
            background: #5A7863;
            color: white;
            transform: translateY(-2px);
        }

        /* ===== INFO ROW ===== */
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #D0E0C0;
        }

        .info-label {
            font-weight: 600;
            color: #5A7863;
            width: 40%;
        }

        .info-value {
            color: #3B4953;
            width: 60%;
            text-align: right;
        }

        /* ===== DATA TABLE ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table tr {
            border-bottom: 1px solid #D0E0C0;
        }

        .data-table td {
            padding: 12px 8px;
            color: #3B4953;
        }

        .data-table td:first-child {
            font-weight: 600;
            color: #5A7863;
            width: 35%;
        }

        .data-table td:last-child {
            text-align: right;
        }

        .empty-data {
            text-align: center;
            padding: 30px;
            color: #90AB8B;
            font-style: italic;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: #3B4953;
            border-radius: 40px 40px 0 0;
            padding: 24px 20px;
            margin-top: 40px;
            text-align: center;
            color: white;
            border-top: 1px solid #90AB8B;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer p {
            opacity: 0.9;
            font-size: 13px;
        }

        .footer-links {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }

        .footer-links a:hover {
            opacity: 1;
            color: #EBF4DD;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                border-radius: 30px;
            }
            .page-header-left {
                justify-content: center;
            }
            .container {
                padding: 0 16px;
                margin: 20px auto;
            }
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .settings-card {
                padding: 20px;
            }
            .card-header h2 {
                font-size: 18px;
            }
            .card-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }
            .page-header-left h1 {
                font-size: 20px;
            }
            .info-label, .info-value {
                font-size: 13px;
            }
        }

        @media (max-width: 380px) {
            .navbar {
                padding: 10px 12px;
            }
            .logo-icon {
                width: 38px;
                height: 38px;
                font-size: 18px;
            }
            .logo-text h1 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fas fa-leaf"></i></div>
        <div class="logo-text">
            <h1>e-RT Digital</h1>
            <p>RT 05 Sukamaju</p>
        </div>
    </div>
    
    <div class="nav-menu">
        <a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
        <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a>
        <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
        <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
        <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
    </div>
    
    <div class="user-section">
        <a href="profil.php" class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($user['nama'] ?? 'User'); ?></h4>
                <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
            </div>
        </a>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </a>
    </div>
    
    <div class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </div>
</nav>

<!-- ========== MOBILE DROPDOWN ========== -->
<div class="mobile-dropdown" id="mobileDropdown">
    <a href="profil.php" class="mobile-user-info">
        <div class="avatar" style="width:45px;height:45px;"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
        <div class="user-info">
            <h4><?php echo htmlspecialchars($user['nama'] ?? 'User'); ?></h4>
            <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
        </div>
    </a>
    <a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a>
    <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a>
    <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
    <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
    <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
    <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
    <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
    <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
    <a href="../auth/logout.php" style="margin-top: 10px; background: rgba(217, 138, 108, 0.3);">
        <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
</div>

<div class="dropdown-overlay" id="dropdownOverlay"></div>

<!-- ========== MAIN CONTENT ========== -->
<div class="container">
    <div class="page-header">
        <div class="page-header-left">
            <i class="fas fa-user-circle"></i>
            <h1>Profil Saya</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- Pesan -->
    <?php if ($success_msg): ?>
        <div class="message success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="message error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Edit Profil -->
        <div class="settings-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-user-edit"></i></div>
                <h2>Tambah / Edit Profil</h2>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="nama" name="nama" class="form-control" value="" placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" value="" placeholder="Masukkan email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="no_hp">No. HP</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone-alt input-icon"></i>
                        <input type="text" id="no_hp" name="no_hp" class="form-control" value="" placeholder="Contoh: 08123456789">
                    </div>
                </div>
                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt input-icon"></i>
                        <textarea id="alamat" name="alamat" class="form-control" rows="2" placeholder="Jl. ... RT/RW ..."></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <div class="input-wrapper">
                        <i class="fas fa-calendar-alt input-icon"></i>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" value="" placeholder="Contoh: Jakarta">
                    </div>
                </div>
                <div class="form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <div class="input-wrapper">
                        <i class="fas fa-birthday-cake input-icon"></i>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="">
                    </div>
                </div>
                <div class="form-group">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <div class="input-wrapper">
                        <i class="fas fa-venus-mars input-icon"></i>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-control">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Profil
                </button>
            </form>
        </div>

        <!-- Data Profil yang Tersimpan -->
        <div class="settings-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-database"></i></div>
                <h2>Data Profil Tersimpan</h2>
            </div>
            
            <table class="data-table">
                <tr>
                    <td>Nama Lengkap</td>
                    <td><?php echo htmlspecialchars($user['nama'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>No. HP</td>
                    <td><?php echo htmlspecialchars($user['no_hp'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td><?php echo htmlspecialchars($user['alamat'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>Tempat Lahir</td>
                    <td><?php echo htmlspecialchars($user['tempat_lahir'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>Tanggal Lahir</td>
                    <td><?php echo !empty($user['tanggal_lahir']) ? date('d M Y', strtotime($user['tanggal_lahir'])) : '-'; ?></td>
                </tr>
                <tr>
                    <td>Jenis Kelamin</td>
                    <td><?php echo htmlspecialchars($user['jenis_kelamin'] ?? '-'); ?></td>
                </tr>
            </table>
            
            <?php if (empty($user['nama']) && empty($user['email'])): ?>
                <div class="empty-data">
                    <i class="fas fa-info-circle"></i> Belum ada data profil. Silakan isi form di samping.
                </div>
            <?php endif; ?>
        </div>

        <!-- Ganti Password + Info Akun -->
        <div class="settings-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-lock"></i></div>
                <h2>Ganti Password</h2>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Password Lama</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Masukkan password lama" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <div class="input-wrapper">
                        <i class="fas fa-check-circle input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Ubah Password
                </button>
            </form>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #90AB8B;">
                <div class="card-header" style="margin-bottom: 15px; padding-bottom: 0; border-bottom: none;">
                    <div class="card-icon" style="width: 45px; height: 45px;"><i class="fas fa-info-circle"></i></div>
                    <h2 style="font-size: 18px;">Informasi Akun</h2>
                </div>
                <div class="info-row">
                    <span class="info-label">NIK</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['nik'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Username</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role Akun</span>
                    <span class="info-value"><?php echo ucfirst($user['role'] ?? 'Warga'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value"><?php echo ucfirst($user['status'] ?? 'Aktif'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">ID Pengguna</span>
                    <span class="info-value">#<?php echo $user['id']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Bergabung</span>
                    <span class="info-value"><?php echo isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Terakhir Login</span>
                    <span class="info-value"><?php echo isset($user['last_login']) ? date('d M Y H:i', strtotime($user['last_login'])) : '-'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo $current_year; ?> e-RT Digital - Sistem Informasi RT 05 Sukamaju</p>
        <div class="footer-links">
            <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
            <a href="privacy.php"><i class="fas fa-shield-alt"></i> Kebijakan Privasi</a>
            <a href="terms.php"><i class="fas fa-file-alt"></i> Syarat & Ketentuan</a>
        </div>
    </div>
</footer>

<script>
    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileDropdown = document.getElementById('mobileDropdown');
    const dropdownOverlay = document.getElementById('dropdownOverlay');
    
    function openDropdown() {
        mobileDropdown.classList.add('active');
        dropdownOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDropdown() {
        mobileDropdown.classList.remove('active');
        dropdownOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', openDropdown);
    }
    
    if (dropdownOverlay) {
        dropdownOverlay.addEventListener('click', closeDropdown);
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 901) {
            closeDropdown();
        }
    });
    
    const mobileLinks = document.querySelectorAll('.mobile-dropdown a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', closeDropdown);
    });
</script>
</body>
</html>