<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Cari keluarga berdasarkan user_id di tabel kartu_keluarga
$keluarga = $conn->query("SELECT * FROM kartu_keluarga WHERE user_id = $user_id")->fetch_assoc();

// Ambil parameter filter
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;

$iuran_list = [];
$stats = ['total' => 0, 'lunas' => 0, 'belum' => 0, 'total_uang' => 0];

if ($keluarga) {
    $keluarga_id = $keluarga['id'];
    
    // Bangun query dengan filter
    $sql = "SELECT * FROM iuran_payments WHERE keluarga_id = $keluarga_id";
    $params = [];
    $types = "";
    
    if ($filter_tahun > 0) {
        $sql .= " AND YEAR(week_start) = ?";
        $params[] = $filter_tahun;
        $types .= "i";
    }
    if ($filter_bulan > 0) {
        $sql .= " AND MONTH(week_start) = ?";
        $params[] = $filter_bulan;
        $types .= "i";
    }
    $sql .= " ORDER BY week_start DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $iuran = $stmt->get_result();
    
    while ($row = $iuran->fetch_assoc()) {
        $iuran_list[] = $row;
        $stats['total']++;
        if ($row['status'] == 'lunas') {
            $stats['lunas']++;
            $stats['total_uang'] += $row['amount'];
        } else {
            $stats['belum']++;
        }
    }
}

// Ambil daftar tahun yang ada untuk dropdown
$tahun_list = [];
if ($keluarga) {
    $result = $conn->query("SELECT DISTINCT YEAR(week_start) as tahun FROM iuran_payments WHERE keluarga_id = $keluarga_id ORDER BY tahun DESC");
    while ($row = $result->fetch_assoc()) {
        $tahun_list[] = $row['tahun'];
    }
}
if (empty($tahun_list)) {
    // Jika belum ada data, set tahun sekarang
    $tahun_list[] = date('Y');
}

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Iuran Saya - e-RT Digital</title>
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
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        /* Page Header - Responsive */
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

        /* Stats Cards - Responsive */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 20px 15px;
            border: 1px solid #90AB8B;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            animation: fadeUp 0.4s ease-out forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }

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

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: #5A7863;
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: inline-block;
            width: 60px;
            height: 60px;
            line-height: 60px;
            border-radius: 50%;
            background: #90AB8B;
            color: #EBF4DD;
            border: 1px solid #5A7863;
        }

        .stat-card h3 {
            font-size: 12px;
            color: #5A7863;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 800;
            color: #3B4953;
            margin-bottom: 4px;
        }

        /* Kegunaan Box - Responsive */
        .kegunaan-box {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 28px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
            animation: fadeUp 0.4s ease-out forwards;
            animation-delay: 0.25s;
            opacity: 0;
        }

        .kegunaan-title {
            font-size: 18px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #3B4953;
            flex-wrap: wrap;
        }

        .kegunaan-title h2 {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .kegunaan-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
        }

        .kegunaan-item {
            background: white;
            padding: 14px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s ease;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-sm);
        }

        .kegunaan-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: #5A7863;
        }

        .kegunaan-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #90AB8B;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .kegunaan-text h4 {
            font-size: 14px;
            color: #3B4953;
            margin-bottom: 4px;
        }

        .kegunaan-text p {
            font-size: 12px;
            color: #5A7863;
        }

        /* Filter Section - Responsive */
        .filter-section {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
            animation: fadeUp 0.4s ease-out forwards;
            animation-delay: 0.3s;
            opacity: 0;
        }

        .filter-title {
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #3B4953;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1 1 160px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #3B4953;
            font-weight: 500;
        }

        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #90AB8B;
            border-radius: 30px;
            background: white;
            color: #3B4953;
            font-size: 13px;
            transition: 0.3s;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #5A7863;
            box-shadow: 0 0 0 3px rgba(90, 120, 99, 0.2);
        }

        .btn-filter {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            font-size: 13px;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 73, 83, 0.4);
        }

        .btn-reset {
            background: #90AB8B;
            color: #3B4953;
            border: 1px solid #5A7863;
            padding: 10px 20px;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
            width: 100%;
            text-align: center;
            font-size: 13px;
        }

        .btn-reset:hover {
            background: #5A7863;
            color: white;
            transform: translateY(-2px);
        }

        /* Table Container - Responsive */
        .table-container {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 20px;
            border: 1px solid #90AB8B;
            margin-top: 20px;
            overflow-x: auto;
            box-shadow: var(--shadow-md);
            animation: fadeUp 0.4s ease-out forwards;
            animation-delay: 0.35s;
            opacity: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        th {
            background: #90AB8B;
            padding: 14px 12px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #3B4953;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #D0E0C0;
            color: #3B4953;
            font-size: 13px;
        }

        tr:hover td {
            background: rgba(144, 171, 139, 0.15);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.lunas {
            background: #7DA06E;
            color: white;
        }

        .status-badge.belum {
            background: #D98A6C;
            color: white;
        }

        .empty-state {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 40px 20px;
            text-align: center;
            color: #3B4953;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
        }

        .empty-state i {
            color: #90AB8B;
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h2 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 0.9rem;
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
        @media (max-width: 900px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }
            .container {
                padding: 0 16px;
                margin: 20px auto;
            }
            .page-header {
                padding: 12px 20px;
            }
            .page-header-left h1 {
                font-size: 20px;
            }
            .page-header-left i {
                font-size: 22px;
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
            .kegunaan-list {
                grid-template-columns: 1fr;
            }
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            .footer-links {
                justify-content: center;
            }
        }

        @media (max-width: 550px) {
            .stats-container {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .stat-card {
                padding: 16px;
            }
            .stat-icon {
                width: 50px;
                height: 50px;
                line-height: 50px;
                font-size: 24px;
            }
            .stat-card .number {
                font-size: 24px;
            }
            .kegunaan-box {
                padding: 18px;
            }
            .kegunaan-title {
                font-size: 16px;
            }
            .filter-section {
                padding: 16px;
            }
            .table-container {
                padding: 12px;
            }
            th, td {
                padding: 10px 8px;
                font-size: 12px;
            }
            .status-badge {
                padding: 4px 10px;
                font-size: 10px;
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
                padding: 6px 14px;
                font-size: 0.8rem;
            }
            .kegunaan-item {
                padding: 10px;
            }
            .kegunaan-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .kegunaan-text h4 {
                font-size: 13px;
            }
            .kegunaan-text p {
                font-size: 11px;
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
        <a href="iuran.php" class="active"><i class="fas fa-money-bill-wave"></i> Iuran</a>
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
    <a href="iuran.php" class="active"><i class="fas fa-money-bill-wave"></i> Iuran</a>
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
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <i class="fas fa-money-bill-wave"></i>
            <h1>Iuran Saya</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if (!$keluarga): ?>
        <div class="empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <h2>Anda belum terdaftar sebagai kepala keluarga</h2>
            <p>Silakan hubungi admin untuk menambahkan data KK Anda.</p>
        </div>
    <?php else: ?>
        <!-- Statistik -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-list"></i></div>
                <h3>Total Iuran</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <h3>Lunas</h3>
                <div class="number"><?php echo $stats['lunas']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <h3>Belum Bayar</h3>
                <div class="number"><?php echo $stats['belum']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Total Dibayar</h3>
                <div class="number">Rp <?php echo number_format($stats['total_uang'],0,',','.'); ?></div>
            </div>
        </div>

        <!-- Kegunaan Iuran -->
        <div class="kegunaan-box">
            <div class="kegunaan-title">
                <i class="fas fa-hand-holding-heart"></i> 
                <h2>Kegunaan Iuran Warga RT</h2>
            </div>
            <div class="kegunaan-list">
                <div class="kegunaan-item">
                    <div class="kegunaan-icon"><i class="fas fa-road"></i></div>
                    <div class="kegunaan-text"><h4>Perbaikan Jalan Gang</h4><p>Perbaikan jalan gang dan fasilitas umum RT</p></div>
                </div>
                <div class="kegunaan-item">
                    <div class="kegunaan-icon"><i class="fas fa-hand-holding-medical"></i></div>
                    <div class="kegunaan-text"><h4>Bantuan Warga Sakit</h4><p>Bantuan pengobatan untuk warga yang sakit berat</p></div>
                </div>
                <div class="kegunaan-item">
                    <div class="kegunaan-icon"><i class="fas fa-heart"></i></div>
                    <div class="kegunaan-text"><h4>Santunan Wafat</h4><p>Santunan untuk keluarga warga yang meninggal</p></div>
                </div>
                <div class="kegunaan-item">
                    <div class="kegunaan-icon"><i class="fas fa-hands-helping"></i></div>
                    <div class="kegunaan-text"><h4>Bantuan Warga Tidak Mampu</h4><p>Bantuan sembako dan kebutuhan warga tidak mampu</p></div>
                </div>
                <div class="kegunaan-item">
                    <div class="kegunaan-icon"><i class="fas fa-house-damage"></i></div>
                    <div class="kegunaan-text"><h4>Bantuan Kemanusiaan</h4><p>Bencana alam dan kejadian darurat lainnya</p></div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-section">
            <div class="filter-title"><i class="fas fa-filter"></i> Filter Riwayat Iuran</div>
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label for="tahun">Tahun</label>
                    <select name="tahun" id="tahun">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($tahun_list as $th): ?>
                            <option value="<?php echo $th; ?>" <?php if ($filter_tahun == $th) echo 'selected'; ?>><?php echo $th; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="bulan">Bulan</label>
                    <select name="bulan" id="bulan">
                        <option value="">Semua Bulan</option>
                        <option value="1" <?php if ($filter_bulan == 1) echo 'selected'; ?>>Januari</option>
                        <option value="2" <?php if ($filter_bulan == 2) echo 'selected'; ?>>Februari</option>
                        <option value="3" <?php if ($filter_bulan == 3) echo 'selected'; ?>>Maret</option>
                        <option value="4" <?php if ($filter_bulan == 4) echo 'selected'; ?>>April</option>
                        <option value="5" <?php if ($filter_bulan == 5) echo 'selected'; ?>>Mei</option>
                        <option value="6" <?php if ($filter_bulan == 6) echo 'selected'; ?>>Juni</option>
                        <option value="7" <?php if ($filter_bulan == 7) echo 'selected'; ?>>Juli</option>
                        <option value="8" <?php if ($filter_bulan == 8) echo 'selected'; ?>>Agustus</option>
                        <option value="9" <?php if ($filter_bulan == 9) echo 'selected'; ?>>September</option>
                        <option value="10" <?php if ($filter_bulan == 10) echo 'selected'; ?>>Oktober</option>
                        <option value="11" <?php if ($filter_bulan == 11) echo 'selected'; ?>>November</option>
                        <option value="12" <?php if ($filter_bulan == 12) echo 'selected'; ?>>Desember</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Terapkan</button>
                </div>
                <div class="filter-group">
                    <a href="iuran.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Tabel Iuran -->
        <div class="table-container">
            <?php if (count($iuran_list) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Periode Minggu</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Tanggal Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($iuran_list as $i): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($i['week_start'])) . ' - ' . date('d M Y', strtotime($i['week_start'] . ' +6 days')); ?></td>
                            <td>Rp <?php echo number_format($i['amount'],0,',','.'); ?></td>
                            <td><span class="status-badge <?php echo $i['status']; ?>"><?php echo ucfirst($i['status']); ?></span></td>
                            <td><?php echo $i['payment_date'] ? date('d M Y', strtotime($i['payment_date'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 30px;">
                <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.5; margin-bottom: 15px; color: #90AB8B;"></i>
                <p style="color: #5A7863;">Tidak ada data iuran untuk periode yang dipilih.</p>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ========== FOOTER RAPI & LENGKAP ========== -->
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