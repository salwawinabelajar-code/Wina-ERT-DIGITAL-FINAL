<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Cek apakah kolom 'tampil' ada, jika tidak tambahkan
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM galeri LIKE 'tampil'");
if (mysqli_num_rows($column_check) == 0) {
    mysqli_query($conn, "ALTER TABLE galeri ADD tampil TINYINT DEFAULT 1 AFTER tanggal");
}

// Hanya ambil foto yang tampil = 1 (disetujui admin)
$query = "SELECT * FROM galeri WHERE tampil = 1 ORDER BY tanggal DESC, id DESC";
$result = mysqli_query($conn, $query);
if (!$result) die("Query error: " . mysqli_error($conn));

$galeri = mysqli_fetch_all($result, MYSQLI_ASSOC);

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Galeri Warga - e-RT Digital</title>
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

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #EBF4DD 0%, #A8BF9A 40%, #90AB8B 70%, #5A7863 100%);
            min-height: 100vh;
            color: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Animated gradient overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 40%, rgba(235, 244, 221, 0.2) 0%, rgba(90, 120, 99, 0.15) 100%);
            pointer-events: none;
            z-index: -1;
            animation: softPulse 8s ease-in-out infinite;
        }

        @keyframes softPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* ========== NAVBAR - RESPONSIVE ========== */
        .navbar {
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.95), rgba(59, 73, 83, 0.95));
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

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info h4 {
            font-size: 0.85rem;
            color: white;
            font-weight: 600;
            line-height: 1.2;
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
            border-color: transparent;
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

        .mobile-user-info .avatar {
            width: 45px;
            height: 45px;
            font-size: 1.1rem;
        }

        .mobile-user-info .user-info h4 {
            color: white;
            font-size: 0.9rem;
        }

        .mobile-user-info .user-info small {
            color: rgba(235, 244, 221, 0.8);
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

        /* Container - Responsive */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 20px;
            flex: 1;
        }

        /* Page Header - Responsive */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: #3B4953;
            border-radius: 50px;
            padding: 14px 28px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .page-header-left i {
            font-size: 28px;
            color: #EBF4DD;
        }

        .page-header-left h1 {
            font-size: 24px;
            font-weight: 800;
            color: white;
        }

        .back-btn {
            background: #90AB8B;
            border: 1px solid #5A7863;
            color: white;
            padding: 8px 20px;
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

        /* Gallery Grid - Responsive (4 -> 3 -> 2 -> 1 kolom) */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        /* Card - SOLID */
        .gallery-card {
            background: #EBF4DD;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #90AB8B;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-md);
            animation: fadeUp 0.4s ease-out forwards;
            opacity: 0;
        }

        .gallery-card:nth-child(1) { animation-delay: 0.05s; }
        .gallery-card:nth-child(2) { animation-delay: 0.1s; }
        .gallery-card:nth-child(3) { animation-delay: 0.15s; }
        .gallery-card:nth-child(4) { animation-delay: 0.2s; }
        .gallery-card:nth-child(5) { animation-delay: 0.25s; }
        .gallery-card:nth-child(6) { animation-delay: 0.3s; }
        .gallery-card:nth-child(7) { animation-delay: 0.35s; }
        .gallery-card:nth-child(8) { animation-delay: 0.4s; }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .gallery-card:hover {
            transform: translateY(-6px);
            border-color: #5A7863;
            box-shadow: var(--shadow-lg);
        }

        .card-image {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            background: linear-gradient(145deg, #90AB8B, #5A7863);
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .gallery-card:hover .card-image img {
            transform: scale(1.05);
        }

        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding: 20px;
        }

        .gallery-card:hover .card-overlay {
            opacity: 1;
        }

        .preview-icon {
            background: rgba(144, 171, 139, 0.9);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transform: translateY(20px);
            transition: transform 0.3s;
            cursor: pointer;
            color: white;
        }

        .gallery-card:hover .preview-icon {
            transform: translateY(0);
        }

        .card-content {
            padding: 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-content h3 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #3B4953;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .date {
            font-size: 10px;
            color: #5A7863;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
        }

        .desc-preview {
            font-size: 11px;
            color: #5A7863;
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more-btn {
            background: none;
            border: none;
            color: #90AB8B;
            cursor: pointer;
            font-size: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0;
            margin-bottom: 10px;
            width: fit-content;
            transition: 0.2s;
        }

        .read-more-btn:hover {
            color: #5A7863;
            transform: translateX(3px);
        }

        .download-btn {
            background: #90AB8B;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 10px;
            text-decoration: none;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            margin-top: auto;
        }

        .download-btn:hover {
            background: #5A7863;
            transform: translateY(-2px);
        }

        /* Modal - Responsive */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        .modal-content {
            background: #EBF4DD;
            border-radius: 28px;
            max-width: 800px;
            width: 90%;
            padding: 24px;
            border: 1px solid #90AB8B;
            position: relative;
            cursor: default;
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-image {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            border-radius: 20px;
            margin-bottom: 20px;
            border: 2px solid #90AB8B;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #3B4953;
            margin: 0;
        }

        .modal-download {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
            padding: 6px 16px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            font-size: 13px;
        }

        .modal-download:hover {
            background: #5A7863;
            transform: translateY(-2px);
        }

        .modal-date {
            font-size: 12px;
            color: #5A7863;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 16px;
        }

        .modal-desc {
            font-size: 14px;
            line-height: 1.6;
            color: #3B4953;
            margin-bottom: 16px;
            white-space: pre-wrap;
        }

        .close-modal {
            position: absolute;
            top: 16px;
            right: 20px;
            font-size: 26px;
            color: #3B4953;
            cursor: pointer;
            transition: 0.2s;
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal:hover {
            background: rgba(59, 73, 83, 0.1);
            color: #D98A6C;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #EBF4DD;
            border-radius: 40px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
        }

        .empty-state i {
            font-size: 64px;
            color: #90AB8B;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state p {
            font-size: 18px;
            color: #3B4953;
        }

        .empty-state p:last-child {
            font-size: 14px;
            margin-top: 10px;
            color: #5A7863;
        }

        /* Footer - Responsive */
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
            max-width: 1400px;
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
            gap: 20px;
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

        /* ========== RESPONSIVE BREAKPOINTS ========== */
        @media (max-width: 1200px) {
            .gallery-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            .container {
                padding: 20px 16px;
            }
        }

        @media (max-width: 900px) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            .page-header {
                padding: 12px 20px;
            }
            .page-header-left h1 {
                font-size: 20px;
            }
            .page-header-left i {
                font-size: 24px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                border-radius: 30px;
            }
            .page-header-left {
                justify-content: center;
            }
            .container {
                padding: 16px 12px;
            }
            .modal-content {
                padding: 20px;
                width: 95%;
            }
            .modal-header h3 {
                font-size: 18px;
            }
            .modal-desc {
                font-size: 13px;
            }
        }

        @media (max-width: 550px) {
            .gallery-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .modal-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            .footer-links {
                justify-content: center;
            }
            .card-content h3 {
                font-size: 14px;
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
            .avatar {
                width: 34px;
                height: 34px;
                font-size: 0.9rem;
            }
            .page-header-left h1 {
                font-size: 18px;
            }
            .back-btn {
                padding: 6px 16px;
                font-size: 0.8rem;
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
    <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
    <a href="galeri.php" class="active"><i class="fas fa-images"></i> Galeri</a>
    <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
    <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
    <a href="../auth/logout.php" style="margin-top: 10px; background: rgba(217, 138, 108, 0.3);">
        <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
</div>

<div class="dropdown-overlay" id="dropdownOverlay"></div>

<!-- ========== MAIN CONTENT ========== -->
<div class="container">
    <div class="page-header">
        <div class="page-header-left">
            <i class="fas fa-images"></i>
            <h1>Galeri Warga</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if (empty($galeri)): ?>
        <div class="empty-state">
            <i class="fas fa-camera"></i>
            <p>Belum ada foto di galeri.</p>
            <p>Tunggu update dari pengurus RT ya!</p>
        </div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($galeri as $item): 
                $short_desc = strlen($item['deskripsi']) > 100 ? substr($item['deskripsi'], 0, 100) . '...' : $item['deskripsi'];
            ?>
            <div class="gallery-card" data-id="<?php echo $item['id']; ?>" data-title="<?php echo htmlspecialchars($item['judul']); ?>" data-desc="<?php echo htmlspecialchars($item['deskripsi']); ?>" data-date="<?php echo date('d M Y', strtotime($item['tanggal'])); ?>" data-img="<?php echo htmlspecialchars($item['foto']); ?>">
                <div class="card-image">
                    <img src="../<?php echo htmlspecialchars($item['foto']); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>" loading="lazy">
                    <div class="card-overlay">
                        <div class="preview-icon" onclick="openModal(this)"><i class="fas fa-expand"></i> Lihat Detail</div>
                    </div>
                </div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($item['judul']); ?></h3>
                    <div class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($item['tanggal'])); ?></div>
                    <p class="desc-preview"><?php echo nl2br(htmlspecialchars($short_desc)); ?></p>
                    <?php if (strlen($item['deskripsi']) > 100): ?>
                        <button class="read-more-btn" onclick="openModalFromButton(this)">Selengkapnya <i class="fas fa-chevron-right"></i></button>
                    <?php endif; ?>
                    <a href="download_galeri.php?id=<?php echo $item['id']; ?>" class="download-btn" download><i class="fas fa-download"></i> Unduh</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal untuk deskripsi lengkap -->
<div id="descModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal()">&times;</button>
        <img id="modalImage" class="modal-image" src="" alt="">
        <div class="modal-header">
            <h3 id="modalTitle"></h3>
            <a href="#" id="modalDownload" class="modal-download" download><i class="fas fa-download"></i> Unduh</a>
        </div>
        <div class="modal-date" id="modalDate"></div>
        <div class="modal-desc" id="modalDesc"></div>
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

    // Modal functions
    function openModal(trigger) {
        const card = trigger.closest('.gallery-card');
        const title = card.dataset.title;
        const date = card.dataset.date;
        const desc = card.dataset.desc;
        const imgSrc = "../" + card.dataset.img;
        const id = card.dataset.id;
        
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + date;
        document.getElementById('modalDesc').innerText = desc;
        document.getElementById('modalImage').src = imgSrc;
        document.getElementById('modalDownload').href = `download_galeri.php?id=${id}`;
        document.getElementById('descModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function openModalFromButton(btn) {
        const card = btn.closest('.gallery-card');
        const title = card.dataset.title;
        const date = card.dataset.date;
        const desc = card.dataset.desc;
        const imgSrc = "../" + card.dataset.img;
        const id = card.dataset.id;
        
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + date;
        document.getElementById('modalDesc').innerText = desc;
        document.getElementById('modalImage').src = imgSrc;
        document.getElementById('modalDownload').href = `download_galeri.php?id=${id}`;
        document.getElementById('descModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('descModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('descModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

</body>
</html>