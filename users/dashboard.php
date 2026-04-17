<?php
// dashboard.php - Dashboard Warga dengan Desain Premium & Responsive
require_once(__DIR__ . '/../config/db.php');
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// FORCE NO CACHE
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
if (!$result_user) die("Error: " . mysqli_error($conn));
$user = mysqli_fetch_assoc($result_user);
if (!$user) $user = ['nama' => 'Warga', 'email' => 'warga@example.com', 'role' => 'warga', 'no_hp' => '-', 'alamat' => '-'];

// Ambil pengumuman terbaru
$pengumuman = [];
$query_pengumuman = "SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT 6";
$result_pengumuman = mysqli_query($conn, $query_pengumuman);
if ($result_pengumuman) {
    while ($row = mysqli_fetch_assoc($result_pengumuman)) {
        $pengumuman[] = $row;
    }
}

// Ambil foto galeri terbaru
$galeri = [];
$query_galeri = "SELECT * FROM galeri ORDER BY tanggal DESC, id DESC LIMIT 8";
$result_galeri = mysqli_query($conn, $query_galeri);
if ($result_galeri) {
    while ($row = mysqli_fetch_assoc($result_galeri)) {
        $galeri[] = $row;
    }
}

// Statistik untuk warga
$stats = [];

// Total pengaduan user
$query_pengaduan = "SELECT COUNT(*) as total FROM pengaduan WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query_pengaduan);
$stats['total_pengaduan'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Pengaduan yang diproses
$query_pengaduan_proses = "SELECT COUNT(*) as total FROM pengaduan WHERE user_id = '$user_id' AND status = 'diproses'";
$result = mysqli_query($conn, $query_pengaduan_proses);
$stats['pengaduan_diproses'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Total surat user
$query_surat = "SELECT COUNT(*) as total FROM pengajuan_surat WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query_surat);
$stats['total_surat'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Surat yang diproses
$query_surat_proses = "SELECT COUNT(*) as total FROM pengajuan_surat WHERE user_id = '$user_id' AND status = 'diproses'";
$result = mysqli_query($conn, $query_surat_proses);
$stats['surat_diproses'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Ambil data iuran terbaru user
$stats['iuran_terakhir'] = '-';
$stats['iuran_status'] = 'Belum Bayar';
$stats['iuran_nominal'] = '-';
$query_kk = "SELECT id FROM kartu_keluarga WHERE user_id = '$user_id' LIMIT 1";
$result_kk = mysqli_query($conn, $query_kk);
if ($result_kk && mysqli_num_rows($result_kk) > 0) {
    $kk = mysqli_fetch_assoc($result_kk);
    $keluarga_id = $kk['id'];
    $query_iuran = "SELECT week_start, amount, status FROM iuran_payments WHERE keluarga_id = '$keluarga_id' ORDER BY week_start DESC LIMIT 1";
    $result_iuran = mysqli_query($conn, $query_iuran);
    if ($result_iuran && mysqli_num_rows($result_iuran) > 0) {
        $iuran = mysqli_fetch_assoc($result_iuran);
        $stats['iuran_terakhir'] = date('d M Y', strtotime($iuran['week_start']));
        $stats['iuran_status'] = ucfirst($iuran['status']);
        $stats['iuran_nominal'] = 'Rp ' . number_format($iuran['amount'], 0, ',', '.');
    }
}

// Ambil 3 pengaduan terbaru user
$pengaduan_terbaru = [];
$query_pengaduan_user = "SELECT * FROM pengaduan WHERE user_id = '$user_id' ORDER BY tanggal DESC LIMIT 3";
$result_pengaduan_user = mysqli_query($conn, $query_pengaduan_user);
if ($result_pengaduan_user) {
    while ($row = mysqli_fetch_assoc($result_pengaduan_user)) {
        $pengaduan_terbaru[] = $row;
    }
}

// Ambil 3 surat terbaru user
$surat_terbaru = [];
$query_surat_user = "SELECT * FROM pengajuan_surat WHERE user_id = '$user_id' ORDER BY tanggal_pengajuan DESC LIMIT 3";
$result_surat_user = mysqli_query($conn, $query_surat_user);
if ($result_surat_user) {
    while ($row = mysqli_fetch_assoc($result_surat_user)) {
        $surat_terbaru[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>e-RT Digital - Dashboard Warga</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        /* Premium Earthy Color Palette */
        :root {
            --bg-soft: #EBF4DD;
            --primary-light: #90AB8B;
            --primary: #5A7863;
            --primary-dark: #3B4953;
            --white: #FFFFFF;
            --dark: #2D3A3F;
            --gray: #6B7F72;
            --danger: #C97B5E;
            --warning: #D4A76A;
            --success: #6B8C5E;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 12px 28px rgba(0, 0, 0, 0.12);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #EBF4DD 0%, #90AB8B 50%, #5A7863 100%);
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
            position: relative;
        }

        /* Animated gradient background overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 40%, rgba(235, 244, 221, 0.25) 0%, rgba(90, 120, 99, 0.15) 100%);
            pointer-events: none;
            z-index: 0;
            animation: softPulse 12s ease-in-out infinite;
        }

        @keyframes softPulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        /* Subtle grain texture */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='1'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            background-repeat: repeat;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
            position: relative;
            z-index: 1;
        }

        /* ========== NAVBAR - RESPONSIVE ========== */
        .navbar {
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.92), rgba(59, 73, 83, 0.88));
            backdrop-filter: blur(16px);
            padding: 16px 24px;
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
            width: 45px;
            height: 45px;
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
            background: #EBF4DD;
            color: #3B4953;
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background: #EBF4DD;
            color: #3B4953;
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
            background: rgba(201, 123, 94, 0.35);
            border: 1px solid rgba(235, 244, 221, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #C97B5E;
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            background: rgba(235, 244, 221, 0.15);
            border: 1px solid rgba(235, 244, 221, 0.3);
            border-radius: 40px;
            padding: 8px 16px;
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
            top: 72px;
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
            background: #EBF4DD;
            color: #3B4953;
        }

        .dropdown-overlay {
            position: fixed;
            top: 72px;
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

        /* ========== RESPONSIVE NAVBAR ========== */
        @media (max-width: 1024px) {
            .nav-menu a {
                padding: 8px 14px;
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

        /* ========== RT HEADER - RESPONSIVE ========== */
        .rt-header {
            background: linear-gradient(135deg, #3B4953 0%, #5A7863 100%);
            border-radius: 28px;
            padding: 24px 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(235, 244, 221, 0.3);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            animation: fadeUp 0.5s ease-out forwards;
        }

        .rt-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 180px;
            height: 180px;
            background: rgba(235, 244, 221, 0.1);
            border-radius: 50%;
        }

        .rt-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 130px;
            height: 130px;
            background: rgba(235, 244, 221, 0.08);
            border-radius: 50%;
        }

        .rt-header h2 {
            font-size: 2.2rem;
            color: #EBF4DD;
            font-weight: 800;
            margin-bottom: 6px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .rt-header p {
            color: rgba(235, 244, 221, 0.9);
            font-size: 0.95rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .rt-location {
            background: rgba(235, 244, 221, 0.2);
            padding: 8px 16px;
            border-radius: 40px;
            color: #EBF4DD;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(4px);
        }

        /* ========== WELCOME CARD - RESPONSIVE ========== */
        .welcome-card {
            background: linear-gradient(125deg, rgba(235, 244, 221, 0.95), rgba(144, 171, 139, 0.8));
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(235, 244, 221, 0.5);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-md);
            animation: fadeUp 0.5s ease-out forwards;
            animation-delay: 0.05s;
        }

        .welcome-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5A7863, #3B4953);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #EBF4DD;
            font-size: 2rem;
            font-weight: 700;
            border: 3px solid #EBF4DD;
            flex-shrink: 0;
        }

        .welcome-text h3 {
            font-size: 1.4rem;
            color: #3B4953;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .welcome-text p {
            color: #5A7863;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* ========== STATS GRID - RESPONSIVE ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: rgba(235, 244, 221, 0.92);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 18px;
            border: 1px solid rgba(144, 171, 139, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            animation: fadeUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.15s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        .stat-card:nth-child(4) { animation-delay: 0.25s; }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(235, 244, 221, 0.98);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #90AB8B, #5A7863);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            font-size: 1.2rem;
            color: #EBF4DD;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }

        .stat-card h4 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5A7863;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .stat-number {
            font-size: 1.6rem;
            font-weight: 800;
            color: #3B4953;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 6px;
        }

        .stat-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 8px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-badge {
            transform: scale(1.05);
        }

        .badge-lunas {
            background: rgba(107, 140, 94, 0.25);
            color: #4A6B3E;
        }

        .badge-belum {
            background: rgba(201, 123, 94, 0.2);
            color: #C97B5E;
        }

        /* ========== SECTION TITLE ========== */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            margin-top: 6px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-title h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #3B4953;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title a {
            color: #5A7863;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(144, 171, 139, 0.3);
            padding: 6px 14px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .section-title a:hover {
            background: #5A7863;
            color: white;
            transform: translateY(-2px);
        }

        /* ========== LAYANAN GRID - RESPONSIVE ========== */
        .layanan-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .layanan-card {
            background: rgba(235, 244, 221, 0.92);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 20px 12px;
            text-align: center;
            border: 1px solid rgba(144, 171, 139, 0.4);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .layanan-card:nth-child(1) { animation-delay: 0.3s; }
        .layanan-card:nth-child(2) { animation-delay: 0.35s; }
        .layanan-card:nth-child(3) { animation-delay: 0.4s; }

        .layanan-card:hover {
            transform: translateY(-5px);
            background: rgba(235, 244, 221, 0.98);
            box-shadow: var(--shadow-lg);
        }

        .layanan-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #90AB8B, #5A7863);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.5rem;
            color: #EBF4DD;
            transition: transform 0.3s ease;
        }

        .layanan-card:hover .layanan-icon {
            transform: scale(1.1);
        }

        .layanan-card h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #3B4953;
            margin-bottom: 5px;
        }

        .layanan-card p {
            font-size: 0.75rem;
            color: #5A7863;
        }

        /* ========== CARDS ========== */
        .pengumuman-card, .galeri-card, .riwayat-card {
            background: rgba(235, 244, 221, 0.92);
            backdrop-filter: blur(8px);
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid rgba(144, 171, 139, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .pengumuman-card { animation-delay: 0.45s; }
        .galeri-card { animation-delay: 0.5s; }
        .riwayat-card { animation-delay: 0.55s; }

        .pengumuman-card:hover, .galeri-card:hover, .riwayat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .pengumuman-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .pengumuman-item {
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(144, 171, 139, 0.3);
            transition: all 0.3s ease;
        }

        .pengumuman-item:hover {
            transform: translateX(5px);
        }

        .pengumuman-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .pengumuman-item h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #3B4953;
            margin-bottom: 6px;
        }

        .pengumuman-item p {
            font-size: 0.85rem;
            color: #5A7863;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .pengumuman-date {
            font-size: 0.75rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ========== GALERI GRID - RESPONSIVE ========== */
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 16px;
        }

        .galeri-item {
            aspect-ratio: 1;
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid rgba(144, 171, 139, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(145deg, #90AB8B, #5A7863);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .galeri-item:hover {
            transform: scale(1.03);
            border-color: #5A7863;
        }

        .galeri-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .galeri-item:hover img {
            transform: scale(1.05);
        }

        .galeri-item i {
            font-size: 1.5rem;
            color: #EBF4DD;
            opacity: 0.7;
            transition: transform 0.3s ease;
        }

        .galeri-item:hover i {
            transform: scale(1.1);
        }

        /* ========== RIWAYAT ========== */
        .riwayat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(144, 171, 139, 0.3);
            transition: all 0.3s ease;
        }

        .riwayat-item:hover {
            transform: translateX(5px);
        }

        .riwayat-item:last-child {
            border-bottom: none;
        }

        .riwayat-title {
            font-weight: 600;
            color: #3B4953;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .riwayat-meta {
            font-size: 0.75rem;
            color: var(--gray);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .riwayat-item:hover .status-badge {
            transform: scale(1.05);
        }

        .status-baru, .status-menunggu { background: rgba(212, 167, 106, 0.25); color: #B8853A; }
        .status-diproses { background: rgba(144, 171, 139, 0.25); color: #5A7863; }
        .status-selesai, .status-lunas { background: rgba(107, 140, 94, 0.25); color: #4A6B3E; }
        .status-ditolak { background: rgba(201, 123, 94, 0.2); color: #C97B5E; }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #5A7863;
            font-size: 0.85rem;
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.5;
            transition: transform 0.3s ease;
        }

        .empty-state:hover i {
            transform: scale(1.1);
            opacity: 0.8;
        }

        /* ========== FOOTER - RESPONSIVE ========== */
        .footer {
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.95), rgba(59, 73, 83, 0.9));
            backdrop-filter: blur(10px);
            border-radius: 32px 32px 0 0;
            padding: 24px 20px;
            margin-top: 32px;
            text-align: center;
            color: #EBF4DD;
            transition: all 0.3s ease;
        }

        .footer:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .footer h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .footer p {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .footer-contact {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin: 16px 0;
            font-size: 0.8rem;
        }

        /* ========== RESPONSIVE BREAKPOINTS ========== */
        @media (max-width: 1024px) {
            .container {
                padding: 20px;
            }
            .stats-grid {
                gap: 14px;
            }
            .galeri-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }
            .layanan-grid {
                gap: 14px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            .rt-header {
                padding: 20px;
            }
            .rt-header h2 {
                font-size: 1.8rem;
            }
            .welcome-card {
                padding: 20px;
            }
            .welcome-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.6rem;
            }
            .welcome-text h3 {
                font-size: 1.2rem;
            }
            .stat-number {
                font-size: 1.4rem;
            }
            .galeri-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
        }

        @media (max-width: 550px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .layanan-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .galeri-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .rt-header h2 {
                font-size: 1.5rem;
            }
            .rt-header p {
                font-size: 0.85rem;
            }
            .section-title h3 {
                font-size: 1rem;
            }
            .footer-contact {
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (max-width: 380px) {
            .container {
                padding: 12px;
            }
            .welcome-card {
                flex-direction: column;
                text-align: center;
            }
            .galeri-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stat-card {
                padding: 14px;
            }
        }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
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
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Beranda</a>
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

<div class="mobile-dropdown" id="mobileDropdown">
    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Beranda</a>
    <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a>
    <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
    <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
    <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
    <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
</div>

<div class="dropdown-overlay" id="dropdownOverlay"></div>

<div class="container">
    <!-- RT HEADER -->
    <div class="rt-header">
        <div>
            <h2>🏘️ RT 05</h2>
            <p>Kampung Guyub Rukun · Sukamaju</p>
            <div class="rt-location">
                <i class="fas fa-map-marker-alt"></i> Jl. Mawar No.5, Sukamaju
            </div>
        </div>
    </div>

    <!-- WELCOME CARD -->
    <div class="welcome-card">
        <div class="welcome-avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
        <div class="welcome-text">
            <h3>Halo, <?php echo htmlspecialchars($user['nama']); ?>! 👋</h3>
            <p>Selamat datang di portal warga RT 05. Mari bersama menjaga lingkungan yang asri dan harmonis.</p>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <h4>PENGADUAN</h4>
            <div class="stat-number"><?php echo $stats['total_pengaduan']; ?></div>
            <div class="stat-label">Total pengaduan</div>
            <?php if ($stats['pengaduan_diproses'] > 0): ?>
                <div class="stat-badge" style="background: rgba(212, 167, 106, 0.25); color: #B8853A;"><?php echo $stats['pengaduan_diproses']; ?> diproses</div>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            <h4>SURAT</h4>
            <div class="stat-number"><?php echo $stats['total_surat']; ?></div>
            <div class="stat-label">Total pengajuan</div>
            <?php if ($stats['surat_diproses'] > 0): ?>
                <div class="stat-badge" style="background: rgba(212, 167, 106, 0.25); color: #B8853A;"><?php echo $stats['surat_diproses']; ?> diproses</div>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <h4>IURAN</h4>
            <div class="stat-number" style="font-size: 1.1rem;"><?php echo $stats['iuran_terakhir'] != '-' ? $stats['iuran_terakhir'] : 'Belum'; ?></div>
            <div class="stat-badge <?php echo $stats['iuran_status'] == 'Lunas' ? 'badge-lunas' : 'badge-belum'; ?>">
                <?php echo $stats['iuran_status']; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <h4>AKTIVITAS</h4>
            <div class="stat-number" style="font-size: 1.1rem;">Terbaru</div>
            <div class="stat-label">Pantau di bawah</div>
        </div>
    </div>

    <!-- LAYANAN CEPAT -->
    <div class="section-title">
        <h3><i class="fas fa-rocket"></i> Layanan Cepat</h3>
    </div>
    <div class="layanan-grid">
        <a href="surat.php" class="layanan-card">
            <div class="layanan-icon"><i class="fas fa-file-signature"></i></div>
            <h4>Ajukan Surat</h4>
            <p>SKTM, Domisili, dll</p>
        </a>
        <a href="pengaduan.php" class="layanan-card">
            <div class="layanan-icon"><i class="fas fa-bullhorn"></i></div>
            <h4>Buat Pengaduan</h4>
            <p>Sampaikan aspirasi</p>
        </a>
        <a href="iuran.php" class="layanan-card">
            <div class="layanan-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <h4>Bayar Iuran</h4>
            <p>Cek tagihan</p>
        </a>
    </div>

    <!-- PENGUMUMAN -->
    <div class="pengumuman-card">
        <div class="section-title">
            <h3><i class="fas fa-bullhorn"></i> Pengumuman</h3>
            <a href="pengumuman.php">Lihat Semua</a>
        </div>
        <div class="pengumuman-list">
            <?php if (!empty($pengumuman)): ?>
                <?php foreach (array_slice($pengumuman, 0, 4) as $item): ?>
                <div class="pengumuman-item">
                    <h4><?php echo htmlspecialchars($item['judul']); ?></h4>
                    <p><?php echo htmlspecialchars(substr($item['isi'], 0, 100)); ?>...</p>
                    <div class="pengumuman-date"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($item['created_at'])); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="pengumuman-item">
                    <h4>🌱 Kerja Bakti Bulanan</h4>
                    <p>Mari bersama membersihkan lingkungan RT 05. Sabtu, 26 April 2025 pukul 07.00.</p>
                    <div class="pengumuman-date"><i class="far fa-calendar-alt"></i> 20 April 2025</div>
                </div>
                <div class="pengumuman-item">
                    <h4>📢 Posyandu Balita</h4>
                    <p>Posyandu Mawar buka setiap hari Sabtu minggu ke-2. Cek jadwal lengkap.</p>
                    <div class="pengumuman-date"><i class="far fa-calendar-alt"></i> 18 April 2025</div>
                </div>
                <div class="pengumuman-item">
                    <h4>🏆 Lomba 17 Agustus</h4>
                    <p>Pendaftaran lomba untuk warga dibuka hingga 10 Juli. Ayo berpartisipasi!</p>
                    <div class="pengumuman-date"><i class="far fa-calendar-alt"></i> Segera</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GALERI -->
    <div class="galeri-card">
        <div class="section-title">
            <h3><i class="fas fa-images"></i> Galeri Kegiatan</h3>
            <a href="galeri.php">Lihat Semua</a>
        </div>
        <div class="galeri-grid">
            <?php if (!empty($galeri)): ?>
                <?php foreach (array_slice($galeri, 0, 8) as $foto): ?>
                <div class="galeri-item">
                    <img src="../<?php echo htmlspecialchars($foto['foto']); ?>" alt="<?php echo htmlspecialchars($foto['judul'] ?? 'Kegiatan RT'); ?>">
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="galeri-item"><i class="fas fa-tree"></i></div>
                <div class="galeri-item"><i class="fas fa-people-arrows"></i></div>
                <div class="galeri-item"><i class="fas fa-hand-sparkles"></i></div>
                <div class="galeri-item"><i class="fas fa-chalkboard-user"></i></div>
                <div class="galeri-item"><i class="fas fa-mosque"></i></div>
                <div class="galeri-item"><i class="fas fa-bicycle"></i></div>
                <div class="galeri-item"><i class="fas fa-drumstick-bite"></i></div>
                <div class="galeri-item"><i class="fas fa-camera"></i></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIWAYAT AKTIVITAS -->
    <div class="riwayat-card">
        <div class="section-title">
            <h3><i class="fas fa-clock"></i> Aktivitas Terbaru</h3>
            <a href="riwayat.php">Lihat Semua</a>
        </div>
        
        <?php if (!empty($pengaduan_terbaru) || !empty($surat_terbaru)): ?>
            <?php 
            $all_activities = array_merge($pengaduan_terbaru, $surat_terbaru);
            usort($all_activities, function($a, $b) {
                $date_a = isset($a['tanggal']) ? $a['tanggal'] : $a['tanggal_pengajuan'];
                $date_b = isset($b['tanggal']) ? $b['tanggal'] : $b['tanggal_pengajuan'];
                return strtotime($date_b) - strtotime($date_a);
            });
            $all_activities = array_slice($all_activities, 0, 3);
            ?>
            <?php foreach ($all_activities as $item): ?>
                <?php if (isset($item['judul'])): ?>
                <div class="riwayat-item">
                    <div class="riwayat-info">
                        <div class="riwayat-title">📢 <?php echo htmlspecialchars($item['judul']); ?></div>
                        <div class="riwayat-meta">
                            <span><i class="far fa-calendar"></i> <?php echo date('d M Y', strtotime($item['tanggal'])); ?></span>
                            <span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--gray); font-size: 0.8rem;"></i>
                </div>
                <?php elseif (isset($item['jenis_surat'])): ?>
                <div class="riwayat-item">
                    <div class="riwayat-info">
                        <div class="riwayat-title">📄 <?php echo htmlspecialchars(ucfirst($item['jenis_surat'])); ?></div>
                        <div class="riwayat-meta">
                            <span><i class="far fa-calendar"></i> <?php echo date('d M Y', strtotime($item['tanggal_pengajuan'])); ?></span>
                            <span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--gray); font-size: 0.8rem;"></i>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Belum ada aktivitas. Yuk buat pengaduan atau ajukan surat!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    <h4>RT 05 Kelurahan Sukamaju</h4>
    <p>Jalan Mawar No. 5, Sukamaju, Kec. Sukajajaya</p>
    <div class="footer-contact">
        <span><i class="fas fa-phone-alt"></i> 0812-3456-7890</span>
        <span><i class="fas fa-envelope"></i> rt05@sukamaju.id</span>
        <span><i class="fab fa-instagram"></i> @rt05sukamaju</span>
    </div>
    <p style="margin-top: 14px; opacity: 0.8;">&copy; 2025 e-RT Digital — Warga Guyub, Lingkungan Asri</p>
</footer>

<script>
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
<?php mysqli_close($conn); ?>