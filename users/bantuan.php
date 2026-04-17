<?php
// users/bantuan.php - Halaman Bantuan dan Tutorial dengan FAQ Dinamis
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login - HARUS LOGIN TERLEBIH DAHULU
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Ambil data FAQ dari database
$query_faq = "SELECT * FROM bantuan ORDER BY urutan ASC, id ASC";
$result_faq = mysqli_query($conn, $query_faq);
$faq_list = [];
if ($result_faq) {
    while ($row = mysqli_fetch_assoc($result_faq)) {
        $faq_list[] = $row;
    }
}

// Kelompokkan FAQ berdasarkan kategori
$faq_by_kategori = [
    'umum' => [],
    'pengaduan' => [],
    'surat' => [],
    'iuran' => []
];

foreach ($faq_list as $faq) {
    $kategori = $faq['kategori'];
    if (isset($faq_by_kategori[$kategori])) {
        $faq_by_kategori[$kategori][] = $faq;
    } else {
        $faq_by_kategori['umum'][] = $faq;
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
    <title>Pusat Bantuan - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        /* Premium Color Palette: #EBF4DD, #90AB8B, #5A7863, #3B4953 */
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

        /* ========== ANIMATIONS ========== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
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

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #EBF4DD 0%, #90AB8B 50%, #5A7863 100%);
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
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

        /* ========== NAVBAR - SAMA SEPERTI DASHBOARD ========== */
        .navbar {
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.95), rgba(59, 73, 83, 0.95));
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
            transition: all 0.3s ease;
        }

        .logo-text h1 {
            font-size: 1.3rem;
            color: #FFFFFF;
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.2;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
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

        .container {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        /* Page Header - SOLID */
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
            animation: fadeInLeft 0.5s ease-out;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header-left i {
            font-size: 26px;
            color: #EBF4DD;
            animation: pulse 2s infinite;
        }

        .page-header-left h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            background: white;
            border-radius: 50px;
            padding: 5px;
            margin-bottom: 24px;
            border: 1px solid #90AB8B;
            flex-wrap: wrap;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.05s;
            animation-fill-mode: both;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            background: none;
            border: none;
            font-size: 15px;
            font-weight: 600;
            color: #5A7863;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .tab-btn:hover {
            background: rgba(144, 171, 139, 0.15);
            color: #3B4953;
            transform: translateY(-2px);
        }

        .tab-btn.active {
            background: #90AB8B;
            color: white;
            box-shadow: 0 5px 15px rgba(144, 171, 139, 0.3);
        }

        /* Bantuan Section */
        .bantuan-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Bantuan Card - SOLID */
        .bantuan-card {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 24px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.5s ease-out;
            animation-fill-mode: both;
        }

        .bantuan-card:nth-child(1) { animation-delay: 0.1s; }
        .bantuan-card:nth-child(2) { animation-delay: 0.15s; }
        .bantuan-card:nth-child(3) { animation-delay: 0.2s; }
        .bantuan-card:nth-child(4) { animation-delay: 0.25s; }
        .bantuan-card:nth-child(5) { animation-delay: 0.3s; }
        .bantuan-card:nth-child(6) { animation-delay: 0.35s; }
        .bantuan-card:nth-child(7) { animation-delay: 0.4s; }

        .bantuan-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: #5A7863;
        }

        .bantuan-card h2 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #3B4953;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bantuan-card h2 i {
            font-size: 24px;
            color: #90AB8B;
            transition: transform 0.3s ease;
        }

        .bantuan-card:hover h2 i {
            transform: scale(1.1) rotate(5deg);
        }

        .step-list {
            list-style: none;
            padding: 0;
        }

        .step-list li {
            margin-bottom: 12px;
            padding-left: 32px;
            position: relative;
            font-size: 14px;
            line-height: 1.5;
            color: #5A7863;
            transition: transform 0.3s ease;
        }

        .step-list li:hover {
            transform: translateX(5px);
        }

        .step-list li:before {
            content: '';
            position: absolute;
            left: 0;
            top: 2px;
            width: 22px;
            height: 22px;
            background: #90AB8B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .step-list li:nth-child(1):before { content: '1'; }
        .step-list li:nth-child(2):before { content: '2'; }
        .step-list li:nth-child(3):before { content: '3'; }
        .step-list li:nth-child(4):before { content: '4'; }
        .step-list li:nth-child(5):before { content: '5'; }
        .step-list li:nth-child(6):before { content: '6'; }
        .step-list li:nth-child(7):before { content: '7'; }
        .step-list li:nth-child(8):before { content: '8'; }

        /* FAQ Item */
        .faq-item {
            margin-bottom: 14px;
            border-bottom: 1px solid rgba(144, 171, 139, 0.3);
            padding-bottom: 14px;
            transition: all 0.3s ease;
        }

        .faq-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .faq-question {
            font-weight: 700;
            font-size: 15px;
            color: #3B4953;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            color: #5A7863;
            transform: translateX(3px);
        }

        .faq-question i {
            transition: transform 0.3s ease;
            color: #90AB8B;
        }

        .faq-answer {
            font-size: 13px;
            color: #5A7863;
            line-height: 1.6;
            padding: 0 0 0 20px;
            display: none;
        }

        .faq-answer.active {
            display: block;
            padding-bottom: 10px;
            animation: slideIn 0.3s ease-out;
        }

        .faq-question.active i {
            transform: rotate(180deg);
        }

        .note {
            margin-top: 16px;
            padding: 12px 16px;
            background: white;
            border-radius: 16px;
            border-left: 4px solid #90AB8B;
            color: #5A7863;
            font-size: 13px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .note:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }

        .note i {
            color: #90AB8B;
            margin-right: 8px;
        }

        .btn-link {
            display: inline-block;
            margin-top: 12px;
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
            padding: 8px 18px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-size: 13px;
        }

        .btn-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 73, 83, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #5A7863;
            background: rgba(235, 244, 221, 0.5);
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
            transition: transform 0.3s ease;
        }

        .empty-state:hover i {
            transform: scale(1.1);
            opacity: 0.8;
        }

        /* Footer */
        .footer {
            background: #3B4953;
            border-radius: 40px 40px 0 0;
            padding: 24px 20px;
            margin-top: 40px;
            text-align: center;
            color: white;
            border-top: 1px solid #90AB8B;
            transition: all 0.3s ease;
        }

        .footer:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
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
            transform: translateX(3px);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 1rem;
            }
            .nav-menu {
                justify-content: center;
                width: 100%;
            }
            .user-section {
                width: 100%;
                justify-content: center;
            }
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .tab-navigation {
                flex-direction: column;
                border-radius: 30px;
            }
            .tab-btn {
                justify-content: center;
            }
            .bantuan-card {
                padding: 20px;
            }
            .bantuan-card h2 {
                font-size: 18px;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .step-list li {
                font-size: 12px;
            }
            .btn-link {
                width: 100%;
                text-align: center;
            }
            .faq-question {
                font-size: 13px;
            }
            .bantuan-card {
                padding: 16px;
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
        <a href="bantuan.php" class="active"><i class="fas fa-question-circle"></i> Bantuan</a>
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
        <div class="avatar"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
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
    <a href="bantuan.php" class="active"><i class="fas fa-question-circle"></i> Bantuan</a>
    <a href="../auth/logout.php" style="margin-top: 10px; background: rgba(217, 138, 108, 0.3);">
        <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
</div>

<div class="dropdown-overlay" id="dropdownOverlay"></div>

<!-- ========== MAIN CONTENT ========== -->
<div class="container">
    <div class="page-header">
        <div class="page-header-left">
            <i class="fas fa-question-circle"></i>
            <h1>Pusat Bantuan</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="changeTab('tutorial')">
            <i class="fas fa-graduation-cap"></i> Tutorial
        </button>
        <button class="tab-btn" onclick="changeTab('faq')">
            <i class="fas fa-question-circle"></i> FAQ
        </button>
    </div>

    <!-- Tab Tutorial -->
    <div id="tabTutorial" class="bantuan-section">
        <!-- 1. Cara Membuat Pengaduan -->
        <div class="bantuan-card">
            <h2><i class="fas fa-comment-medical"></i> Cara Membuat Pengaduan</h2>
            <ul class="step-list">
                <li>Klik menu <strong>"Pengaduan"</strong> di navbar.</li>
                <li>Isi formulir dengan judul pengaduan yang jelas (contoh: Jalan berlubang di depan rumah No.15).</li>
                <li>Pilih kategori pengaduan yang sesuai (Kebersihan, Keamanan, Infrastruktur, dll).</li>
                <li>Masukkan lokasi kejadian secara detail (RT, RW, alamat).</li>
                <li>Tulis deskripsi lengkap masalah Anda (minimal 20 karakter).</li>
                <li>Jika ada, upload foto bukti (format JPG, PNG, GIF, maks 20MB).</li>
                <li>Tentukan tingkat urgensi (Rendah, Sedang, Tinggi).</li>
                <li>Klik tombol <strong>"Kirim Pengaduan"</strong>.</li>
            </ul>
            <div class="note">
                <i class="fas fa-info-circle"></i> Setelah dikirim, Anda akan mendapatkan nomor tiket. Status pengaduan dapat dilacak di menu <strong>"Riwayat"</strong>.
            </div>
            <a href="pengaduan.php" class="btn-link"><i class="fas fa-arrow-right"></i> Buat Pengaduan Sekarang</a>
        </div>

        <!-- 2. Cara Mengajukan Surat -->
        <div class="bantuan-card">
            <h2><i class="fas fa-envelope-open-text"></i> Cara Mengajukan Surat</h2>
            <ul class="step-list">
                <li>Klik menu <strong>"Surat"</strong> di navbar.</li>
                <li>Pilih jenis surat yang Anda butuhkan (Surat Pengantar, SKTM, Surat Keterangan, dll).</li>
                <li>Jelaskan keperluan surat secara detail (misal: untuk pembuatan KTP, pendaftaran sekolah, dll).</li>
                <li>Tambahkan keterangan tambahan jika diperlukan.</li>
                <li>Lampirkan file pendukung (KTP, KK, dll) jika diperlukan (opsional).</li>
                <li>Klik tombol <strong>"Kirim Pengajuan Surat"</strong>.</li>
            </ul>
            <div class="note">
                <i class="fas fa-info-circle"></i> Surat akan diproses dalam 1-3 hari kerja. Status pengajuan dapat dicek di menu <strong>"Riwayat"</strong> tab Surat.
            </div>
            <a href="surat.php" class="btn-link"><i class="fas fa-arrow-right"></i> Ajukan Surat Sekarang</a>
        </div>

        <!-- 3. Cara Melihat Riwayat dan Status -->
        <div class="bantuan-card">
            <h2><i class="fas fa-history"></i> Cara Melihat Riwayat & Status</h2>
            <ul class="step-list">
                <li>Klik menu <strong>"Riwayat"</strong> di navbar.</li>
                <li>Anda akan melihat dua tab: <strong>Pengaduan</strong> dan <strong>Surat</strong>.</li>
                <li>Pilih tab yang ingin Anda lihat.</li>
                <li>Setiap pengaduan/surat ditampilkan dengan status (Baru, Diproses, Selesai, Ditolak).</li>
                <li>Klik tombol <strong>"Detail"</strong> untuk melihat informasi lengkap.</li>
                <li>Untuk surat yang sudah selesai, Anda dapat mengunduh file surat (jika ada).</li>
            </ul>
            <a href="riwayat.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Riwayat</a>
        </div>

        <!-- 4. Cara Membayar Iuran -->
        <div class="bantuan-card">
            <h2><i class="fas fa-money-bill-wave"></i> Cara Membayar Iuran</h2>
            <ul class="step-list">
                <li>Klik menu <strong>"Iuran"</strong> di navbar.</li>
                <li>Anda akan melihat daftar iuran yang harus dibayar (per periode).</li>
                <li>Pada baris iuran dengan status <strong>"Belum Bayar"</strong>, klik tombol <strong>"Bayar"</strong>.</li>
                <li>Masukkan jumlah yang harus dibayar (biasanya sudah terisi otomatis).</li>
                <li>Pilih metode pembayaran (Transfer, Tunai, E-Wallet).</li>
                <li>Upload bukti pembayaran (jika metode transfer).</li>
                <li>Klik <strong>"Konfirmasi Pembayaran"</strong>.</li>
            </ul>
            <div class="note">
                <i class="fas fa-info-circle"></i> Status akan berubah menjadi "Diproses" dan admin akan memverifikasi pembayaran.
            </div>
            <a href="iuran.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Iuran Saya</a>
        </div>

        <!-- 5. Cara Melihat Pengumuman -->
        <div class="bantuan-card">
            <h2><i class="fas fa-bullhorn"></i> Cara Melihat Pengumuman</h2>
            <ul class="step-list">
                <li>Klik menu <strong>"Pengumuman"</strong> di navbar.</li>
                <li>Anda akan melihat daftar pengumuman terbaru.</li>
                <li>Pengumuman penting biasanya ditandai dengan label <strong>"PENTING"</strong>.</li>
                <li>Klik <strong>"Baca Selengkapnya"</strong> untuk melihat isi lengkap.</li>
            </ul>
            <a href="pengumuman.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Pengumuman</a>
        </div>

        <!-- 6. Cara Melihat Data KK -->
        <div class="bantuan-card">
            <h2><i class="fas fa-address-card"></i> Cara Melihat Data KK</h2>
            <ul class="step-list">
                <li>Klik menu <strong>"Data KK"</strong> di navbar.</li>
                <li>Anda akan melihat daftar Kartu Keluarga yang terdaftar.</li>
                <li>Gunakan fitur pencarian NIK untuk mencari anggota keluarga tertentu.</li>
                <li>Klik tombol <strong>"Reset"</strong> untuk kembali ke daftar semua KK.</li>
            </ul>
            <div class="note">
                <i class="fas fa-info-circle"></i> Data KK hanya dapat ditambah/diubah oleh admin. Jika ada perubahan data, hubungi pengurus RT.
            </div>
            <a href="kk.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Data KK</a>
        </div>

        <!-- 7. Cara Melihat Galeri -->
        <div class="bantuan-card">
            <h2><i class="fas fa-images"></i> Cara Melihat Galeri Warga</h2>
            <ul class="step-list">
                <li>Klik menu <strong>"Galeri"</strong> di navbar.</li>
                <li>Anda akan melihat foto-foto kegiatan warga yang diupload oleh admin.</li>
                <li>Klik pada foto untuk melihat lebih besar.</li>
            </ul>
            <a href="galeri.php" class="btn-link"><i class="fas fa-arrow-right"></i> Lihat Galeri</a>
        </div>
    </div>

    <!-- Tab FAQ (Dinamis dari Database) -->
    <div id="tabFaq" class="bantuan-section" style="display: none;">
        <?php
        $kategori_labels = [
            'umum' => ['icon' => 'fas fa-info-circle', 'title' => 'Informasi Umum', 'color' => '#90AB8B'],
            'pengaduan' => ['icon' => 'fas fa-comment-medical', 'title' => 'Pengaduan', 'color' => '#E0B87A'],
            'surat' => ['icon' => 'fas fa-envelope-open-text', 'title' => 'Pengajuan Surat', 'color' => '#7DA06E'],
            'iuran' => ['icon' => 'fas fa-money-bill-wave', 'title' => 'Iuran', 'color' => '#5A7863']
        ];
        
        $has_faq = false;
        foreach ($faq_by_kategori as $kategori => $faqs):
            if (empty($faqs)) continue;
            $has_faq = true;
        ?>
        <div class="bantuan-card">
            <h2>
                <i class="<?php echo $kategori_labels[$kategori]['icon']; ?>" style="color: <?php echo $kategori_labels[$kategori]['color']; ?>"></i>
                <?php echo $kategori_labels[$kategori]['title']; ?>
            </h2>
            <div class="faq-list">
                <?php foreach ($faqs as $faq): ?>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <?php echo htmlspecialchars($faq['judul']); ?>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <?php echo nl2br(htmlspecialchars($faq['konten'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (!$has_faq): ?>
        <div class="empty-state">
            <i class="fas fa-question-circle"></i>
            <p>Belum ada pertanyaan yang sering diajukan.</p>
            <p style="font-size: 13px; margin-top: 8px;">Silakan hubungi admin untuk informasi lebih lanjut.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========== FOOTER ========== -->
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

    // Tab Navigation
    function changeTab(tab) {
        const tabTutorial = document.getElementById('tabTutorial');
        const tabFaq = document.getElementById('tabFaq');
        const tutorialBtn = document.querySelector('.tab-btn:first-child');
        const faqBtn = document.querySelector('.tab-btn:last-child');
        
        if (tab === 'tutorial') {
            tabTutorial.style.display = 'flex';
            tabFaq.style.display = 'none';
            tutorialBtn.classList.add('active');
            faqBtn.classList.remove('active');
        } else {
            tabTutorial.style.display = 'none';
            tabFaq.style.display = 'flex';
            tutorialBtn.classList.remove('active');
            faqBtn.classList.add('active');
        }
    }

    // FAQ Toggle
    function toggleFaq(element) {
        element.classList.toggle('active');
        const answer = element.nextElementSibling;
        answer.classList.toggle('active');
    }
</script>

</body>
</html>