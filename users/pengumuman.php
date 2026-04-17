<?php
// pengumuman.php - Halaman Pengumuman User dengan Modal Detail
require_once(__DIR__ . '/../config/db.php');
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $query_user);
if ($stmt_user) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    if ($result_user && mysqli_num_rows($result_user) > 0) {
        $user = mysqli_fetch_assoc($result_user);
    }
    mysqli_stmt_close($stmt_user);
}

// Cek apakah tabel pengumuman ada
$table_check = "SHOW TABLES LIKE 'pengumuman'";
$table_result = mysqli_query($conn, $table_check);
$table_exists = mysqli_num_rows($table_result) > 0;

// Cek apakah kolom 'tampil' ada, jika tidak tambahkan
if ($table_exists) {
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM pengumuman LIKE 'tampil'");
    if (mysqli_num_rows($column_check) == 0) {
        mysqli_query($conn, "ALTER TABLE pengumuman ADD tampil TINYINT DEFAULT 1 AFTER penting");
    }
}

$result_important = null;
$result = null;
$total_data = 0;
$total_pages = 0;
$pengumuman_data = [];

if ($table_exists) {
    // Pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 9;
    $offset = ($page - 1) * $limit;

    // Hitung total - HANYA yang tampil = 1
    $count_query = "SELECT COUNT(*) as total FROM pengumuman WHERE tampil = 1";
    $count_result = mysqli_query($conn, $count_query);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $total_data = $count_row['total'] ?? 0;
        $total_pages = ceil($total_data / $limit);
    }

    // Ambil data pengumuman - HANYA yang tampil = 1
    $query = "SELECT * FROM pengumuman WHERE tampil = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $pengumuman_data[] = $row;
        }
    }

    // Ambil pengumuman penting - HANYA yang tampil = 1
    $query_important = "SELECT * FROM pengumuman WHERE penting = 1 AND tampil = 1 ORDER BY created_at DESC LIMIT 3";
    $result_important = mysqli_query($conn, $query_important);
    $pengumuman_penting = [];
    if ($result_important) {
        while ($row = mysqli_fetch_assoc($result_important)) {
            $pengumuman_penting[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Pengumuman - e-RT Digital</title>
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
            transition: transform 0.3s ease;
        }

        .logo:hover .logo-icon {
            transform: scale(1.05) rotate(5deg);
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
            transition: all 0.3s ease;
            background: rgba(235, 244, 221, 0.15);
            border: 1px solid rgba(235, 244, 221, 0.25);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu a i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .nav-menu a:hover {
            background: #90AB8B;
            color: white;
            transform: translateY(-3px);
        }

        .nav-menu a:hover i {
            transform: translateX(3px);
        }

        .nav-menu a.active {
            background: #90AB8B;
            color: white;
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
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .user-profile:hover {
            background: rgba(235, 244, 221, 0.25);
            transform: translateY(-3px);
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
            transition: transform 0.3s ease;
        }

        .user-profile:hover .avatar {
            transform: scale(1.1);
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
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #D98A6C;
            transform: translateY(-3px);
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
            transition: 0.3s ease;
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
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-dropdown a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 12px 16px;
            border-radius: 20px;
            transition: 0.3s ease;
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
            transform: translateX(5px);
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

        /* ===== ANIMATIONS ===== */
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

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shine {
            0% {
                background-position: -100px;
            }
            40%, 100% {
                background-position: 400px;
            }
        }

        /* ===== KONTAINER UTAMA ===== */
        .container {
            max-width: 1400px;
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
            background: rgba(59, 73, 83, 0.7);
            backdrop-filter: blur(8px);
            border-radius: 50px;
            border: 1px solid rgba(235, 244, 221, 0.2);
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
            color: white;
        }

        .back-btn {
            background: rgba(235, 244, 221, 0.15);
            border: 1px solid rgba(235, 244, 221, 0.3);
            color: white;
            padding: 8px 18px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: #90AB8B;
            border-color: #90AB8B;
            transform: translateY(-3px);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 22px;
            color: white;
            font-weight: 700;
            flex-wrap: wrap;
            animation: fadeInLeft 0.5s ease-out;
        }

        .section-title i {
            color: #90AB8B;
            font-size: 24px;
        }

        /* ===== IMPORTANT CARDS - SOLID BACKGROUND ===== */
        .important-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .important-card {
            background: linear-gradient(135deg, #3B4953 0%, #2A3A42 100%);
            border-radius: 24px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: var(--shadow-md);
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .important-card:nth-child(1) { animation-delay: 0.1s; }
        .important-card:nth-child(2) { animation-delay: 0.2s; }
        .important-card:nth-child(3) { animation-delay: 0.3s; }

        .important-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .important-card::before {
            content: 'PENTING';
            position: absolute;
            top: 12px;
            right: -30px;
            background: linear-gradient(135deg, #E0B87A, #D4A86A);
            color: #3B4953;
            padding: 5px 40px;
            font-size: 11px;
            font-weight: 800;
            transform: rotate(45deg);
            letter-spacing: 1px;
        }

        .important-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.6s ease;
        }

        .important-card:hover::after {
            left: 100%;
        }

        .important-card h3 {
            color: white;
            margin-bottom: 16px;
            font-size: 18px;
            font-weight: 700;
            padding-right: 50px;
        }

        .important-card p {
            color: rgba(235, 244, 221, 0.9);
            margin-bottom: 16px;
            line-height: 1.6;
            font-size: 14px;
        }

        .important-date {
            color: #A8BF9A;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        /* ===== ANNOUNCEMENT GRID - SOLID BACKGROUND ===== */
        .announcement-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .announcement-card {
            background: linear-gradient(135deg, #FFFFFF 0%, #F5F7F0 100%);
            border-radius: 20px;
            padding: 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .announcement-card:nth-child(1) { animation-delay: 0.1s; }
        .announcement-card:nth-child(2) { animation-delay: 0.15s; }
        .announcement-card:nth-child(3) { animation-delay: 0.2s; }
        .announcement-card:nth-child(4) { animation-delay: 0.25s; }
        .announcement-card:nth-child(5) { animation-delay: 0.3s; }
        .announcement-card:nth-child(6) { animation-delay: 0.35s; }
        .announcement-card:nth-child(7) { animation-delay: 0.4s; }
        .announcement-card:nth-child(8) { animation-delay: 0.45s; }
        .announcement-card:nth-child(9) { animation-delay: 0.5s; }

        .announcement-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .announcement-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(144, 171, 139, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .announcement-card:hover::after {
            left: 100%;
        }

        .announcement-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: #3B4953;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .announcement-card .content {
            color: #5A7863;
            margin-bottom: 16px;
            line-height: 1.6;
            flex: 1;
            font-size: 13px;
        }

        .announcement-card .content p {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .announcement-card .date {
            color: #90AB8B;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-top: 1px solid #E5E9E0;
            padding-top: 12px;
            margin-top: auto;
        }

        .read-more-btn {
            background: none;
            border: none;
            color: #90AB8B;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 0 0 0;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            transition: all 0.3s ease;
            margin-top: 8px;
            align-self: flex-start;
        }

        .read-more-btn:hover {
            color: #5A7863;
            gap: 10px;
        }

        /* ===== MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
            animation: fadeInUp 0.3s ease;
        }

        .modal-content {
            background: linear-gradient(135deg, #FFFFFF 0%, #F5F7F0 100%);
            border-radius: 28px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: scaleIn 0.3s ease;
        }

        .modal-header {
            background: linear-gradient(135deg, #3B4953 0%, #2A3A42 100%);
            padding: 20px 24px;
            border-radius: 28px 28px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: white;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
            color: #3B4953;
        }

        .modal-body .detail-date {
            color: #90AB8B;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .modal-body .detail-content {
            line-height: 1.8;
            font-size: 14px;
            white-space: pre-wrap;
        }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: #3B4953;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
            box-shadow: var(--shadow-sm);
        }

        .page-link:hover {
            background: #90AB8B;
            color: white;
            transform: translateY(-3px);
        }

        .page-link.active {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 28px;
            box-shadow: var(--shadow-md);
            animation: scaleIn 0.5s ease-out;
        }

        .empty-icon {
            font-size: 60px;
            color: #90AB8B;
            margin-bottom: 20px;
            opacity: 0.6;
            transition: transform 0.3s ease;
        }

        .empty-state:hover .empty-icon {
            transform: scale(1.1);
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 12px;
            color: #3B4953;
        }

        .empty-state p {
            color: #5A7863;
            margin-bottom: 12px;
            font-size: 16px;
        }

        /* ===== INFO BOX ===== */
        .info-box {
            background: linear-gradient(135deg, #FFFFFF 0%, #F5F7F0 100%);
            border-radius: 24px;
            padding: 24px;
            border-left: 5px solid #90AB8B;
            margin-top: 32px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.3s;
            animation-fill-mode: both;
        }

        .info-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .info-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 16px;
            font-weight: 600;
            color: #3B4953;
            flex-wrap: wrap;
        }

        .info-title i {
            color: #90AB8B;
            font-size: 20px;
        }

        .info-content ul {
            list-style: none;
        }

        .info-content li {
            margin-bottom: 10px;
            padding-left: 24px;
            position: relative;
            color: #5A7863;
            font-size: 13px;
            transition: transform 0.3s ease;
        }

        .info-content li:hover {
            transform: translateX(5px);
        }

        .info-content li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #90AB8B;
            font-weight: bold;
            font-size: 14px;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: #3B4953;
            border-radius: 40px 40px 0 0;
            padding: 24px 20px;
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid rgba(235, 244, 221, 0.2);
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
            gap: 16px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .footer-links a:hover {
            opacity: 1;
            transform: translateY(-2px);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .announcement-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

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
        }

        @media (max-width: 768px) {
            .announcement-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .important-cards {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .section-title {
                font-size: 18px;
            }
            .section-title i {
                font-size: 20px;
            }
            .important-card h3 {
                font-size: 16px;
            }
            .important-card p {
                font-size: 13px;
            }
        }

        @media (max-width: 550px) {
            .important-card {
                padding: 18px;
            }
            .announcement-card {
                padding: 16px;
            }
            .modal-content {
                width: 95%;
            }
            .page-link {
                width: 36px;
                height: 36px;
                font-size: 12px;
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
        <a href="pengumuman.php" class="active"><i class="fas fa-bullhorn"></i> Pengumuman</a>
        <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
        <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
    </div>
    
    <div class="user-section">
        <a href="profil.php" class="user-profile">
            <div class="avatar"><?php echo isset($user['nama']) ? strtoupper(substr($user['nama'], 0, 1)) : 'U'; ?></div>
            <div class="user-info">
                <h4><?php echo isset($user['nama']) ? htmlspecialchars($user['nama']) : 'User'; ?></h4>
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
        <div class="avatar"><?php echo isset($user['nama']) ? strtoupper(substr($user['nama'], 0, 1)) : 'U'; ?></div>
        <div class="user-info">
            <h4><?php echo isset($user['nama']) ? htmlspecialchars($user['nama']) : 'User'; ?></h4>
            <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
        </div>
    </a>
    <a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a>
    <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a>
    <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
    <a href="pengumuman.php" class="active"><i class="fas fa-bullhorn"></i> Pengumuman</a>
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
            <i class="fas fa-bullhorn"></i>
            <h1>Pengumuman RT</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
    </div>

    <?php if (!$table_exists): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-database"></i></div>
            <h3>Tabel Pengumuman Tidak Ditemukan</h3>
            <p>Database tidak berisi tabel 'pengumuman'. Hubungi administrator.</p>
        </div>
    <?php else: ?>

        <!-- Pengumuman Penting -->
        <?php if (!empty($pengumuman_penting)): ?>
        <div class="section-title">
            <i class="fas fa-exclamation-triangle"></i> Pengumuman Penting
        </div>
        <div class="important-cards" id="importantCards">
            <?php foreach ($pengumuman_penting as $index => $important): ?>
            <div class="important-card" data-id="<?php echo $important['id']; ?>" style="animation-delay: <?php echo 0.1 + ($index * 0.05); ?>s">
                <h3><?php echo htmlspecialchars($important['judul']); ?></h3>
                <p><?php echo strlen($important['isi']) > 120 ? substr(htmlspecialchars($important['isi']), 0, 120) . '...' : htmlspecialchars($important['isi']); ?></p>
                <div class="important-date">
                    <i class="far fa-clock"></i> <?php echo date('d M Y H:i', strtotime($important['created_at'])); ?>
                </div>
                <button class="read-more-btn" data-id="<?php echo $important['id']; ?>">
                    <span>Baca Selengkapnya</span> <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Semua Pengumuman -->
        <div class="section-title">
            <i class="fas fa-newspaper"></i> Semua Pengumuman
        </div>

        <?php if (!empty($pengumuman_data)): ?>
            <div class="announcement-grid" id="announcementGrid">
                <?php foreach ($pengumuman_data as $index => $announcement): ?>
                <div class="announcement-card" data-id="<?php echo $announcement['id']; ?>" style="animation-delay: <?php echo 0.1 + ($index * 0.03); ?>s">
                    <h3><?php echo htmlspecialchars($announcement['judul']); ?></h3>
                    <div class="content">
                        <p><?php echo strlen($announcement['isi']) > 150 ? substr(htmlspecialchars($announcement['isi']), 0, 150) . '...' : htmlspecialchars($announcement['isi']); ?></p>
                    </div>
                    <div class="date">
                        <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($announcement['created_at'])); ?>
                    </div>
                    <button class="read-more-btn" data-id="<?php echo $announcement['id']; ?>">
                        <span>Baca Selengkapnya</span> <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page-2 && $i <= $page+2)): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php elseif ($i == $page-3 || $i == $page+3): ?>
                        <span class="page-link disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-newspaper"></i></div>
                <h3>Belum Ada Pengumuman</h3>
                <p>Belum ada pengumuman yang diterbitkan oleh pengurus RT.</p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="info-box">
        <div class="info-title"><i class="fas fa-info-circle"></i> Informasi Penting</div>
        <div class="info-content">
            <ul>
                <li>Pengumuman dengan tanda "PENTING" berisi informasi yang harus dibaca oleh semua warga</li>
                <li>Pastikan membaca semua pengumuman untuk mendapatkan informasi terkini</li>
                <li>Pengumuman biasanya diterbitkan setiap Senin sore</li>
                <li>Informasi perubahan mendadak akan diumumkan melalui grup WhatsApp RT</li>
                <li>Untuk pengumuman yang memerlukan konfirmasi, silakan hubungi ketua RT</li>
            </ul>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> e-RT Digital - Sistem Informasi RT 05 Sukamaju</p>
        <div class="footer-links">
            <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
            <a href="privacy.php"><i class="fas fa-shield-alt"></i> Kebijakan Privasi</a>
            <a href="terms.php"><i class="fas fa-file-alt"></i> Syarat & Ketentuan</a>
        </div>
    </div>
</footer>

<!-- Modal Detail -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"></h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-date" id="modalDate"></div>
            <div class="detail-content" id="modalContent"></div>
        </div>
    </div>
</div>

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

    // Data pengumuman dalam bentuk JSON
    var announcements = <?php 
        $allData = array_merge($pengumuman_penting, $pengumuman_data);
        $jsonData = [];
        foreach ($allData as $item) {
            $jsonData[] = [
                'id' => $item['id'],
                'judul' => $item['judul'],
                'isi' => nl2br(htmlspecialchars($item['isi'])),
                'tanggal' => date('d M Y H:i', strtotime($item['created_at'])),
                'tanggal_only' => date('d M Y', strtotime($item['created_at']))
            ];
        }
        echo json_encode($jsonData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    ?>;
    
    // Buat map untuk akses cepat
    var announcementMap = {};
    for (var i = 0; i < announcements.length; i++) {
        announcementMap[announcements[i].id] = announcements[i];
    }
    
    function showDetail(id) {
        var data = announcementMap[id];
        if (data) {
            document.getElementById('modalTitle').innerHTML = data.judul;
            document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + data.tanggal;
            document.getElementById('modalContent').innerHTML = data.isi;
            document.getElementById('detailModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            fetch('get_pengumuman.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').innerHTML = data.judul;
                        document.getElementById('modalDate').innerHTML = '<i class="far fa-calendar-alt"></i> ' + data.tanggal;
                        document.getElementById('modalContent').innerHTML = data.isi;
                        document.getElementById('detailModal').classList.add('active');
                        document.body.style.overflow = 'hidden';
                    } else {
                        alert('Gagal memuat pengumuman');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat pengumuman');
                });
        }
    }
    
    function closeModal() {
        document.getElementById('detailModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Event listener untuk semua kartu dan tombol
    document.addEventListener('DOMContentLoaded', function() {
        // Kartu penting
        var importantCards = document.querySelectorAll('.important-card');
        importantCards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.classList.contains('read-more-btn') || e.target.closest('.read-more-btn')) {
                    return;
                }
                var id = this.getAttribute('data-id');
                if (id) showDetail(id);
            });
        });
        
        // Kartu biasa
        var announcementCards = document.querySelectorAll('.announcement-card');
        announcementCards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.classList.contains('read-more-btn') || e.target.closest('.read-more-btn')) {
                    return;
                }
                var id = this.getAttribute('data-id');
                if (id) showDetail(id);
            });
        });
        
        // Tombol "Baca Selengkapnya"
        var readMoreBtns = document.querySelectorAll('.read-more-btn');
        readMoreBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var id = this.getAttribute('data-id');
                if (id) showDetail(id);
            });
        });
    });
    
    window.onclick = function(event) {
        var modal = document.getElementById('detailModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
</script>
</body>
</html>
<?php
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($result)) mysqli_free_result($result);
if (isset($result_important) && $result_important) mysqli_free_result($result_important);
mysqli_close($conn);
?>