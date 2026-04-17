<?php
// riwayat.php - Halaman Riwayat dengan Fitur Edit Modal (Responsive)
require_once(__DIR__ . '/../config/db.php');
session_start();

// Cek login - HARUS LOGIN TERLEBIH DAHULU
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;
$success_message = '';
$error_message = '';

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
} else {
    die("Error preparing user query: " . mysqli_error($conn));
}

// Jika user tidak ditemukan, redirect ke login
if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// Tangkap pesan sukses/error dari URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Tentukan tab aktif
$active_tab = $_GET['tab'] ?? 'pengaduan';

// ========== LOGIKA PENGADUAN ==========
if ($active_tab == 'pengaduan') {
    // Filter dan pencarian pengaduan
    $filter_status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where_clause = "WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if (!empty($filter_status)) {
        $where_clause .= " AND status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $where_clause .= " AND (judul LIKE ? OR deskripsi LIKE ? OR lokasi LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }

    $count_query = "SELECT COUNT(*) as total FROM pengaduan $where_clause";
    $count_stmt = mysqli_prepare($conn, $count_query);
    if ($count_stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($count_stmt, $types, ...$params);
        }
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_data = $count_result ? mysqli_fetch_assoc($count_result) : ['total' => 0];
        $total_data = $count_data ? $count_data['total'] : 0;
        $total_pages = ceil($total_data / $limit);
        mysqli_stmt_close($count_stmt);
    }

    $query = "SELECT * FROM pengaduan $where_clause ORDER BY tanggal DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }

    // Statistik lengkap dengan ditolak
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) as baru,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pengaduan WHERE user_id = ?";
    
    $stats_stmt = mysqli_prepare($conn, $stats_query);
    if ($stats_stmt) {
        mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
        mysqli_stmt_execute($stats_stmt);
        $stats_result = mysqli_stmt_get_result($stats_stmt);
        $stats = $stats_result ? mysqli_fetch_assoc($stats_result) : [];
        mysqli_stmt_close($stats_stmt);
    } else {
        $stats = [];
    }
}

// ========== LOGIKA SURAT ==========
else if ($active_tab == 'surat') {
    // Filter dan pencarian surat
    $filter_status_surat = $_GET['status_surat'] ?? '';
    $search_surat = $_GET['search_surat'] ?? '';
    $page_surat = isset($_GET['page_surat']) ? max(1, (int)$_GET['page_surat']) : 1;
    $limit_surat = 10;
    $offset_surat = ($page_surat - 1) * $limit_surat;

    $where_clause_surat = "WHERE user_id = ?";
    $params_surat = [$user_id];
    $types_surat = "i";
    
    if (!empty($filter_status_surat)) {
        $where_clause_surat .= " AND status = ?";
        $params_surat[] = $filter_status_surat;
        $types_surat .= "s";
    }
    
    if (!empty($search_surat)) {
        $where_clause_surat .= " AND (jenis_surat LIKE ? OR keperluan LIKE ?)";
        $search_term_surat = "%$search_surat%";
        $params_surat[] = $search_term_surat;
        $params_surat[] = $search_term_surat;
        $types_surat .= "ss";
    }

    $count_query_surat = "SELECT COUNT(*) as total FROM pengajuan_surat $where_clause_surat";
    $count_stmt_surat = mysqli_prepare($conn, $count_query_surat);
    if ($count_stmt_surat) {
        if (!empty($params_surat)) {
            mysqli_stmt_bind_param($count_stmt_surat, $types_surat, ...$params_surat);
        }
        mysqli_stmt_execute($count_stmt_surat);
        $count_result_surat = mysqli_stmt_get_result($count_stmt_surat);
        $count_data_surat = $count_result_surat ? mysqli_fetch_assoc($count_result_surat) : ['total' => 0];
        $total_data_surat = $count_data_surat ? $count_data_surat['total'] : 0;
        $total_pages_surat = ceil($total_data_surat / $limit_surat);
        mysqli_stmt_close($count_stmt_surat);
    }

    $query_surat = "SELECT * FROM pengajuan_surat $where_clause_surat ORDER BY tanggal_pengajuan DESC LIMIT ? OFFSET ?";
    $params_surat[] = $limit_surat;
    $params_surat[] = $offset_surat;
    $types_surat .= "ii";
    
    $stmt_surat = mysqli_prepare($conn, $query_surat);
    if ($stmt_surat) {
        mysqli_stmt_bind_param($stmt_surat, $types_surat, ...$params_surat);
        mysqli_stmt_execute($stmt_surat);
        $result_surat = mysqli_stmt_get_result($stmt_surat);
    }

    // Statistik lengkap dengan ditolak
    $stats_query_surat = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        FROM pengajuan_surat WHERE user_id = ?";
    
    $stats_stmt_surat = mysqli_prepare($conn, $stats_query_surat);
    if ($stats_stmt_surat) {
        mysqli_stmt_bind_param($stats_stmt_surat, "i", $user_id);
        mysqli_stmt_execute($stats_stmt_surat);
        $stats_result_surat = mysqli_stmt_get_result($stats_stmt_surat);
        $stats_surat = $stats_result_surat ? mysqli_fetch_assoc($stats_result_surat) : [];
        mysqli_stmt_close($stats_stmt_surat);
    } else {
        $stats_surat = [];
    }
}

function safe_output($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Riwayat - e-RT Digital</title>
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
            --info: #90AB8B;
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
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

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

        .tab-navigation {
            display: flex;
            background: #EBF4DD;
            border-radius: 50px;
            padding: 5px;
            margin-bottom: 32px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-sm);
            flex-wrap: wrap;
            animation: fadeInUp 0.5s ease-out;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 16px;
            text-align: center;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #5A7863;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
        }

        .tab-btn:hover {
            background: rgba(144, 171, 139, 0.2);
            color: #3B4953;
        }

        .tab-btn.active {
            background: #90AB8B;
            color: white;
            box-shadow: 0 5px 15px rgba(144, 171, 139, 0.3);
        }

        .tab-btn .badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 20px 15px;
            border: 1px solid #90AB8B;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            animation: scaleIn 0.4s ease-out forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
        .stat-card:nth-child(5) { animation-delay: 0.25s; }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: #5A7863;
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 16px;
            display: inline-block;
            width: 70px;
            height: 70px;
            line-height: 70px;
            border-radius: 50%;
            background: #90AB8B;
            color: #EBF4DD;
            border: 1px solid #5A7863;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }

        .stat-card h3 {
            font-size: 13px;
            color: #5A7863;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 800;
            color: #3B4953;
        }

        .filter-section {
            background: #EBF4DD;
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.1s;
            animation-fill-mode: both;
        }

        .filter-section:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .filter-title {
            font-size: 18px;
            color: #3B4953;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 16px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #3B4953;
            font-size: 13px;
        }

        .required {
            color: #D98A6C;
            font-size: 12px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
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
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #A8BFA0;
        }

        select.form-control option {
            background: white;
            color: #3B4953;
        }

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
        }

        .btn-search {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
            box-shadow: 0 5px 15px rgba(59, 73, 83, 0.3);
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 73, 83, 0.5);
        }

        .btn-reset {
            background: #90AB8B;
            color: #3B4953;
            border: 1px solid #5A7863;
        }

        .btn-reset:hover {
            background: #5A7863;
            color: white;
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px 18px;
            border-radius: 30px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }
        .alert-success {
            background: #7DA06E;
            color: white;
        }
        .alert-danger {
            background: #D98A6C;
            color: white;
        }

        .table-container {
            background: #EBF4DD;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #90AB8B;
            margin-bottom: 32px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.15s;
            animation-fill-mode: both;
        }

        .table-container:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        thead {
            background: #90AB8B;
            color: #3B4953;
        }

        th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #D0E0C0;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, rgba(144, 171, 139, 0.05) 0%, rgba(144, 171, 139, 0.1) 100%);
            transform: scale(1.002);
        }

        td {
            padding: 14px 20px;
            vertical-align: middle;
            font-size: 13px;
            color: #3B4953;
        }

        td small {
            color: #5A7863;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: transform 0.3s ease;
        }

        tr:hover .status-badge {
            transform: scale(1.02);
        }

        .status-badge.status-baru, .status-badge.status-menunggu {
            background: #E0B87A;
            color: #3B4953;
        }
        .status-badge.status-diproses {
            background: #90AB8B;
            color: white;
        }
        .status-badge.status-selesai {
            background: #7DA06E;
            color: white;
        }
        .status-badge.status-ditolak {
            background: #D98A6C;
            color: white;
        }

        .urgensi-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .urgensi-rendah { background: #7DA06E; color: white; }
        .urgensi-sedang { background: #E0B87A; color: #3B4953; }
        .urgensi-tinggi { background: #D98A6C; color: white; }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 11px;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-view { background: #90AB8B; color: white; }
        .btn-view:hover { background: #5A7863; }
        .btn-edit { background: #E0B87A; color: #3B4953; }
        .btn-edit:hover { background: #D4A86A; }
        .btn-download { background: #7DA06E; color: white; }
        .btn-download:hover { background: #6A8F5C; }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 32px 0;
            flex-wrap: wrap;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 30px;
            background: white;
            color: #3B4953;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid #90AB8B;
            transition: 0.3s;
            font-size: 14px;
        }

        .page-link:hover {
            background: #90AB8B;
            color: white;
            border-color: #90AB8B;
            transform: translateY(-2px);
        }

        .page-link.active {
            background: #90AB8B;
            color: white;
            border-color: #90AB8B;
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 60px;
            color: #90AB8B;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .empty-state:hover .empty-icon {
            transform: scale(1.1);
        }

        .empty-state h3 {
            color: #3B4953;
            margin-bottom: 12px;
            font-size: 22px;
            font-weight: 700;
        }

        .empty-state p {
            color: #5A7863;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeInUp 0.3s ease-out;
        }

        .modal-content {
            background: #EBF4DD;
            border-radius: 28px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.3s ease-out;
        }

        .modal-header {
            background: #90AB8B;
            color: #3B4953;
            padding: 20px 24px;
            border-radius: 28px 28px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #5A7863;
            flex-wrap: wrap;
            gap: 10px;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
        }

        .close-modal {
            background: none;
            border: none;
            color: #3B4953;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: 0.3s;
        }

        .close-modal:hover {
            background: rgba(59, 73, 83, 0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
            color: #3B4953;
        }

        .detail-group {
            margin-bottom: 16px;
        }

        .detail-group label {
            font-weight: 700;
            color: #5A7863;
            margin-bottom: 6px;
            display: block;
            font-size: 13px;
        }

        .detail-group p {
            color: #3B4953;
            line-height: 1.5;
            padding: 10px;
            background: white;
            border-radius: 12px;
            font-size: 14px;
            border: 1px solid #90AB8B;
        }

        .photo-preview {
            margin-top: 12px;
        }

        .photo-preview img {
            max-width: 100%;
            border-radius: 12px;
            border: 2px solid #90AB8B;
        }

        .modal-body .form-group {
            margin-bottom: 16px;
        }
        .modal-body label {
            font-weight: 600;
            color: #3B4953;
            margin-bottom: 6px;
            display: block;
            font-size: 13px;
        }
        .modal-body .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #90AB8B;
            border-radius: 20px;
            background: white;
            color: #3B4953;
            font-size: 14px;
        }
        .modal-body .form-control:focus {
            border-color: #5A7863;
            outline: none;
        }
        .modal-body .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .error-message {
            color: #D98A6C;
            font-size: 11px;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

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
        @media (max-width: 1000px) {
            .stats-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
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
            .tab-navigation {
                flex-direction: column;
                border-radius: 30px;
            }
            .tab-btn {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }
            .container {
                padding: 0 16px;
                margin: 20px auto;
            }
            th, td {
                padding: 12px 15px;
            }
            .stat-card {
                padding: 16px;
            }
            .stat-icon {
                width: 55px;
                height: 55px;
                line-height: 55px;
                font-size: 28px;
            }
            .stat-card .number {
                font-size: 26px;
            }
        }

        @media (max-width: 600px) {
            .stats-container {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
                justify-content: center;
            }
            .modal-content {
                width: 95%;
            }
            .modal-header {
                padding: 16px 20px;
            }
            .modal-header h3 {
                font-size: 16px;
            }
            .modal-body {
                padding: 20px;
            }
            .detail-group p {
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
            .avatar {
                width: 34px;
                height: 34px;
                font-size: 0.9rem;
            }
            .page-header-left h1 {
                font-size: 20px;
            }
            .back-btn {
                padding: 6px 14px;
                font-size: 0.8rem;
            }
            .stat-card h3 {
                font-size: 11px;
            }
            .stat-card .number {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
            <a href="riwayat.php" class="active"><i class="fas fa-history"></i> Riwayat</a>
            <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
            <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
        </div>
        <div class="user-section">
            <a href="profil.php" class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo safe_output($user['nama'] ?? 'User'); ?></h4>
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
        <a href="profil.php" class="mobile-user-info">
            <div class="avatar"><?php echo strtoupper(substr($user['nama'] ?? 'U', 0, 1)); ?></div>
            <div class="user-info">
                <h4><?php echo safe_output($user['nama'] ?? 'User'); ?></h4>
                <small><?php echo ucfirst($user['role'] ?? 'warga'); ?></small>
            </div>
        </a>
        <a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
        <a href="riwayat.php" class="active"><i class="fas fa-history"></i> Riwayat</a>
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

    <!-- Main container -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <i class="fas fa-history"></i>
                <h1>Riwayat</h1>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Notifikasi -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo safe_output($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo safe_output($error_message); ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn <?php echo $active_tab == 'pengaduan' ? 'active' : ''; ?>" 
                    onclick="changeTab('pengaduan')">
                <i class="fas fa-comment-medical"></i> Pengaduan
                <?php if ($active_tab == 'pengaduan' && isset($stats['total'])): ?>
                    <span class="badge"><?php echo safe_output($stats['total']); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn <?php echo $active_tab == 'surat' ? 'active' : ''; ?>" 
                    onclick="changeTab('surat')">
                <i class="fas fa-envelope-open-text"></i> Surat
                <?php if ($active_tab == 'surat' && isset($stats_surat['total'])): ?>
                    <span class="badge"><?php echo safe_output($stats_surat['total']); ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- ========== TAB PENGADUAN ========== -->
        <?php if ($active_tab == 'pengaduan'): ?>
            <!-- Statistik Pengaduan -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                    <h3>Total Pengaduan</h3>
                    <div class="number"><?php echo safe_output($stats['total'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Pengaduan Baru</h3>
                    <div class="number"><?php echo safe_output($stats['baru'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    <h3>Sedang Diproses</h3>
                    <div class="number"><?php echo safe_output($stats['diproses'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Selesai</h3>
                    <div class="number"><?php echo safe_output($stats['selesai'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <h3>Ditolak</h3>
                    <div class="number"><?php echo safe_output($stats['ditolak'] ?? 0); ?></div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title"><i class="fas fa-filter"></i> Filter Pengaduan</div>
                <form method="GET" action="" class="filter-form">
                    <input type="hidden" name="tab" value="pengaduan">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Cari Pengaduan</label>
                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan judul, deskripsi, atau lokasi..." value="<?php echo safe_output($search); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Filter Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="baru" <?php echo ($filter_status ?? '') == 'baru' ? 'selected' : ''; ?>>Baru</option>
                            <option value="diproses" <?php echo ($filter_status ?? '') == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="selesai" <?php echo ($filter_status ?? '') == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="ditolak" <?php echo ($filter_status ?? '') == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-search"><i class="fas fa-filter"></i> Terapkan</button>
                    <a href="riwayat.php?tab=pengaduan" class="btn btn-reset"><i class="fas fa-redo"></i> Reset</a>
                </form>
            </div>

            <!-- Tabel Pengaduan -->
            <div class="table-container">
                <?php if (isset($result) && mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Judul Pengaduan</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Urgensi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo safe_output($row['judul']); ?></strong>
                                        <br><small>Lokasi: <?php echo safe_output($row['lokasi'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo safe_output(ucfirst($row['kategori'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><span class="urgensi-badge urgensi-<?php echo $row['urgensi']; ?>"><?php echo ucfirst($row['urgensi']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewPengaduan(<?php echo $row['id']; ?>)"><i class="fas fa-eye"></i> Detail</button>
                                            <?php if ($row['status'] == 'baru'): ?>
                                                <button class="btn-action btn-edit" onclick="editPengaduan(<?php echo $row['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?><a href="?tab=pengaduan&page=<?php echo $page-1; ?>&status=<?php echo safe_output($filter_status ?? ''); ?>&search=<?php echo urlencode($search); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                        <?php for ($i=1; $i<=$total_pages; $i++): if ($i==1 || $i==$total_pages || ($i>=$page-2 && $i<=$page+2)): ?><a href="?tab=pengaduan&page=<?php echo $i; ?>&status=<?php echo safe_output($filter_status ?? ''); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a><?php elseif($i==$page-3 || $i==$page+3): ?><span class="page-link disabled">...</span><?php endif; endfor; ?>
                        <?php if ($page < $total_pages): ?><a href="?tab=pengaduan&page=<?php echo $page+1; ?>&status=<?php echo safe_output($filter_status ?? ''); ?>&search=<?php echo urlencode($search); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                        <h3>Belum Ada Pengaduan</h3>
                        <p>Anda belum mengirimkan pengaduan apapun.</p>
                        <a href="pengaduan.php" class="btn btn-search" style="display:inline-flex;"><i class="fas fa-plus"></i> Buat Pengaduan Pertama</a>
                    </div>
                <?php endif; ?>
            </div>

        <!-- ========== TAB SURAT ========== -->
        <?php elseif ($active_tab == 'surat'): ?>
            <!-- Statistik Surat -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <h3>Total Surat</h3>
                    <div class="number"><?php echo safe_output($stats_surat['total'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Menunggu</h3>
                    <div class="number"><?php echo safe_output($stats_surat['menunggu'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    <h3>Diproses</h3>
                    <div class="number"><?php echo safe_output($stats_surat['diproses'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Selesai</h3>
                    <div class="number"><?php echo safe_output($stats_surat['selesai'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <h3>Ditolak</h3>
                    <div class="number"><?php echo safe_output($stats_surat['ditolak'] ?? 0); ?></div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title"><i class="fas fa-filter"></i> Filter Surat</div>
                <form method="GET" action="" class="filter-form">
                    <input type="hidden" name="tab" value="surat">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Cari Surat</label>
                        <input type="text" name="search_surat" class="form-control" placeholder="Cari berdasarkan jenis surat atau keperluan..." value="<?php echo safe_output($search_surat ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Filter Status</label>
                        <select name="status_surat" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?php echo ($filter_status_surat ?? '') == 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="diproses" <?php echo ($filter_status_surat ?? '') == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="selesai" <?php echo ($filter_status_surat ?? '') == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="ditolak" <?php echo ($filter_status_surat ?? '') == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-search"><i class="fas fa-filter"></i> Terapkan</button>
                    <a href="riwayat.php?tab=surat" class="btn btn-reset"><i class="fas fa-redo"></i> Reset</a>
                </form>
            </div>

            <!-- Tabel Surat -->
            <div class="table-container">
                <?php if (isset($result_surat) && mysqli_num_rows($result_surat) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Jenis Surat</th>
                                    <th>Keperluan</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_surat = $offset_surat + 1; while ($row = mysqli_fetch_assoc($result_surat)): ?>
                                <tr>
                                    <td><?php echo $no_surat++; ?></td>
                                    <td>
                                        <strong><?php echo safe_output(strtoupper($row['jenis_surat'])); ?></strong>
                                        <br><small>No. Surat: <?php echo safe_output($row['nomor_surat'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo safe_output($row['keperluan']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo safe_output($row['keterangan'] ?? '-'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewSurat(<?php echo $row['id']; ?>)"><i class="fas fa-eye"></i> Detail</button>
                                            <?php if ($row['status'] == 'menunggu'): ?>
                                                <button class="btn-action btn-edit" onclick="editSurat(<?php echo $row['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                                            <?php endif; ?>
                                            <?php if ($row['status'] == 'selesai' && !empty($row['file_hasil'])): ?>
                                                <?php 
                                                // Ambil nama file dari path
                                                $file_name = basename($row['file_hasil']);
                                                $file_url = '../uploads/surat_hasil/' . $file_name;
                                                ?>
                                                <a href="<?php echo $file_url; ?>" target="_blank" class="btn-action btn-download">
                                                    <i class="fas fa-download"></i> Unduh Hasil
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages_surat > 1): ?>
                    <div class="pagination">
                        <?php if ($page_surat > 1): ?><a href="?tab=surat&page_surat=<?php echo $page_surat-1; ?>&status_surat=<?php echo safe_output($filter_status_surat ?? ''); ?>&search_surat=<?php echo urlencode($search_surat ?? ''); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                        <?php for ($i=1; $i<=$total_pages_surat; $i++): if ($i==1 || $i==$total_pages_surat || ($i>=$page_surat-2 && $i<=$page_surat+2)): ?><a href="?tab=surat&page_surat=<?php echo $i; ?>&status_surat=<?php echo safe_output($filter_status_surat ?? ''); ?>&search_surat=<?php echo urlencode($search_surat ?? ''); ?>" class="page-link <?php echo $i==$page_surat?'active':''; ?>"><?php echo $i; ?></a><?php elseif($i==$page_surat-3 || $i==$page_surat+3): ?><span class="page-link disabled">...</span><?php endif; endfor; ?>
                        <?php if ($page_surat < $total_pages_surat): ?><a href="?tab=surat&page_surat=<?php echo $page_surat+1; ?>&status_surat=<?php echo safe_output($filter_status_surat ?? ''); ?>&search_surat=<?php echo urlencode($search_surat ?? ''); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-envelope"></i></div>
                        <h3>Belum Ada Pengajuan Surat</h3>
                        <p>Anda belum mengajukan surat apapun.</p>
                        <a href="surat.php" class="btn btn-search" style="display:inline-flex;"><i class="fas fa-plus"></i> Ajukan Surat Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Detail Modal Pengaduan -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Detail Pengaduan</h3><button class="close-modal" onclick="closeModal()">&times;</button></div>
            <div class="modal-body" id="modalContent"></div>
        </div>
    </div>

    <!-- Detail Modal Surat -->
    <div id="detailModalSurat" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Detail Surat</h3><button class="close-modal" onclick="closeModalSurat()">&times;</button></div>
            <div class="modal-body" id="modalContentSurat"></div>
        </div>
    </div>

    <!-- Modal Edit Pengaduan -->
    <div id="editPengaduanModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Edit Pengaduan</h3>
                <button class="close-modal" onclick="closeEditPengaduanModal()">&times;</button>
            </div>
            <div class="modal-body" id="editPengaduanContent"></div>
        </div>
    </div>

    <!-- Modal Edit Surat -->
    <div id="editSuratModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Edit Surat</h3>
                <button class="close-modal" onclick="closeEditSuratModal()">&times;</button>
            </div>
            <div class="modal-body" id="editSuratContent"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo $current_year; ?> e-RT Digital - Warga Guyub, Lingkungan Asri</p>
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

        function changeTab(tab) {
            window.location.href = `?tab=${tab}`;
        }

        function viewPengaduan(id) {
            fetch(`get_pengaduan.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let fotoHTML = data.data.foto ? 
                            `<div class="detail-group"><label><i class="fas fa-camera"></i> Foto Bukti:</label>
                                <div class="photo-preview"><img src="../${data.data.foto}" alt="Foto"></div></div>` : '';
                        document.getElementById('modalContent').innerHTML = `
                            <div class="pengaduan-detail">
                                <div class="detail-group"><label><i class="fas fa-heading"></i> Judul:</label><p>${escapeHtml(data.data.judul)}</p></div>
                                <div class="detail-group"><label><i class="fas fa-tag"></i> Kategori:</label><p>${escapeHtml(data.data.kategori)}</p></div>
                                <div class="detail-group"><label><i class="fas fa-map-marker-alt"></i> Lokasi:</label><p>${escapeHtml(data.data.lokasi||'-')}</p></div>
                                <div class="detail-group"><label><i class="fas fa-align-left"></i> Deskripsi:</label><p>${escapeHtml(data.data.deskripsi)}</p></div>
                                ${fotoHTML}
                                <div class="detail-group"><label><i class="fas fa-tachometer-alt"></i> Urgensi:</label><p><span class="urgensi-badge urgensi-${data.data.urgensi}">${data.data.urgensi}</span></p></div>
                                <div class="detail-group"><label><i class="fas fa-info-circle"></i> Status:</label><p><span class="status-badge status-${data.data.status}">${data.data.status}</span></p></div>
                                <div class="detail-group"><label><i class="far fa-clock"></i> Tanggal:</label><p>${new Date(data.data.tanggal).toLocaleString('id-ID')}</p></div>
                            </div>`;
                        document.getElementById('detailModal').style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    } else alert('Gagal memuat detail');
                }).catch(e => alert('Error: ' + e));
        }

        function viewSurat(id) {
            fetch(`get_surat.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let filePendukungHTML = data.data.file_pendukung ? 
                            `<div class="detail-group"><label><i class="fas fa-paperclip"></i> File Pendukung:</label>
                                <p><a href="../uploads/surat/${data.data.file_pendukung}" class="btn-action btn-download" download style="display:inline-flex;"><i class="fas fa-download"></i> Unduh</a></p></div>` : '';
                        let fileHasilHTML = data.data.file_hasil ? 
                            `<div class="detail-group"><label><i class="fas fa-file-pdf"></i> File Hasil (Surat Jadi):</label>
                                <p><a href="../uploads/surat_hasil/${data.data.file_hasil.split('/').pop()}" class="btn-action btn-download" target="_blank" style="display:inline-flex;"><i class="fas fa-download"></i> Unduh Surat</a></p></div>` : '';
                        document.getElementById('modalContentSurat').innerHTML = `
                            <div class="pengaduan-detail">
                                <div class="detail-group"><label><i class="fas fa-envelope"></i> Jenis Surat:</label><p>${escapeHtml(data.data.jenis_surat)}</p></div>
                                <div class="detail-group"><label><i class="fas fa-hashtag"></i> No. Surat:</label><p>${escapeHtml(data.data.nomor_surat || '-')}</p></div>
                                <div class="detail-group"><label><i class="fas fa-list-alt"></i> Keperluan:</label><p>${escapeHtml(data.data.keperluan)}</p></div>
                                <div class="detail-group"><label><i class="fas fa-info-circle"></i> Keterangan:</label><p>${escapeHtml(data.data.keterangan || '-')}</p></div>
                                ${filePendukungHTML}
                                ${fileHasilHTML}
                                <div class="detail-group"><label><i class="fas fa-info-circle"></i> Status:</label><p><span class="status-badge status-${data.data.status}">${data.data.status}</span></p></div>
                                <div class="detail-group"><label><i class="far fa-clock"></i> Tanggal Pengajuan:</label><p>${new Date(data.data.tanggal_pengajuan).toLocaleString('id-ID')}</p></div>
                            </div>`;
                        document.getElementById('detailModalSurat').style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    } else alert('Gagal memuat detail');
                }).catch(e => alert('Error: ' + e));
        }

        function closeModal() { 
            document.getElementById('detailModal').style.display = 'none';
            document.body.style.overflow = '';
        }
        function closeModalSurat() { 
            document.getElementById('detailModalSurat').style.display = 'none';
            document.body.style.overflow = '';
        }

        // ========== FUNGSI EDIT PENGADUAN ==========
        function editPengaduan(id) {
            fetch(`get_pengaduan.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let formHTML = `
                            <form id="formEditPengaduan" onsubmit="return validateEditPengaduan(event, ${id})">
                                <div class="form-group">
                                    <label>Judul <span class="required">* Wajib</span></label>
                                    <input type="text" name="judul" class="form-control" value="${escapeHtml(data.data.judul)}" required>
                                    <div class="error-message" id="error-judul">Judul tidak boleh kosong</div>
                                </div>
                                <div class="form-group">
                                    <label>Deskripsi <span class="required">* Wajib (Min. 20 karakter)</span></label>
                                    <textarea name="deskripsi" class="form-control" required>${escapeHtml(data.data.deskripsi)}</textarea>
                                    <div class="error-message" id="error-deskripsi">Deskripsi minimal 20 karakter</div>
                                </div>
                                <div class="form-group">
                                    <label>Lokasi <span class="required">* Wajib</span></label>
                                    <input type="text" name="lokasi" class="form-control" value="${escapeHtml(data.data.lokasi || '')}" required>
                                    <div class="error-message" id="error-lokasi">Lokasi tidak boleh kosong</div>
                                </div>
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="kategori" class="form-control">
                                        <option value="infrastruktur" ${data.data.kategori == 'infrastruktur' ? 'selected' : ''}>Infrastruktur</option>
                                        <option value="kebersihan" ${data.data.kategori == 'kebersihan' ? 'selected' : ''}>Kebersihan</option>
                                        <option value="keamanan" ${data.data.kategori == 'keamanan' ? 'selected' : ''}>Keamanan</option>
                                        <option value="sosial" ${data.data.kategori == 'sosial' ? 'selected' : ''}>Sosial</option>
                                        <option value="lainnya" ${data.data.kategori == 'lainnya' ? 'selected' : ''}>Lainnya</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Urgensi</label>
                                    <select name="urgensi" class="form-control">
                                        <option value="rendah" ${data.data.urgensi == 'rendah' ? 'selected' : ''}>Rendah</option>
                                        <option value="sedang" ${data.data.urgensi == 'sedang' ? 'selected' : ''}>Sedang</option>
                                        <option value="tinggi" ${data.data.urgensi == 'tinggi' ? 'selected' : ''}>Tinggi</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Foto Baru (kosongkan jika tidak ingin mengganti)</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*">
                                    ${data.data.foto ? `<p>Foto saat ini: <a href="../${data.data.foto}" target="_blank">Lihat</a></p>` : ''}
                                </div>
                                <div class="btn-group">
                                    <button type="submit" class="btn btn-search">Simpan Perubahan</button>
                                    <button type="button" class="btn btn-reset" onclick="closeEditPengaduanModal()">Batal</button>
                                </div>
                            </form>
                        `;
                        document.getElementById('editPengaduanContent').innerHTML = formHTML;
                        document.getElementById('editPengaduanModal').style.display = 'flex';
                    } else {
                        alert('Gagal memuat data');
                    }
                })
                .catch(e => alert('Error: ' + e));
        }

        function validateEditPengaduan(event, id) {
            event.preventDefault();
            
            const form = document.getElementById('formEditPengaduan');
            const judul = form.querySelector('input[name="judul"]').value.trim();
            const deskripsi = form.querySelector('textarea[name="deskripsi"]').value.trim();
            const lokasi = form.querySelector('input[name="lokasi"]').value.trim();
            
            let isValid = true;
            
            document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
            
            if (!judul) {
                document.getElementById('error-judul').classList.add('show');
                isValid = false;
            }
            
            if (!deskripsi || deskripsi.length < 20) {
                document.getElementById('error-deskripsi').classList.add('show');
                isValid = false;
            }
            
            if (!lokasi) {
                document.getElementById('error-lokasi').classList.add('show');
                isValid = false;
            }
            
            if (!isValid) {
                alert('Harap lengkapi semua field yang wajib diisi!');
                return false;
            }
            
            const formData = new FormData(form);
            formData.append('id', id);
            
            fetch('update_pengaduan.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Data berhasil diperbarui');
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(e => alert('Error: ' + e));
            
            return false;
        }

        function closeEditPengaduanModal() {
            document.getElementById('editPengaduanModal').style.display = 'none';
        }

        // ========== FUNGSI EDIT SURAT ==========
        function editSurat(id) {
            fetch(`get_surat.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let formHTML = `
                            <form id="formEditSurat" onsubmit="return validateEditSurat(event, ${id})">
                                <div class="form-group">
                                    <label>Jenis Surat <span class="required">* Wajib</span></label>
                                    <select name="jenis_surat" class="form-control" required>
                                        <option value="surat pengantar" ${data.data.jenis_surat == 'surat pengantar' ? 'selected' : ''}>Surat Pengantar</option>
                                        <option value="surat keterangan tidak mampu" ${data.data.jenis_surat == 'surat keterangan tidak mampu' ? 'selected' : ''}>Surat Keterangan Tidak Mampu</option>
                                        <option value="surat keterangan" ${data.data.jenis_surat == 'surat keterangan' ? 'selected' : ''}>Surat Keterangan</option>
                                        <option value="surat domisili" ${data.data.jenis_surat == 'surat domisili' ? 'selected' : ''}>Surat Domisili</option>
                                        <option value="surat usaha" ${data.data.jenis_surat == 'surat usaha' ? 'selected' : ''}>Surat Keterangan Usaha</option>
                                    </select>
                                    <div class="error-message" id="error-jenis">Jenis surat harus dipilih</div>
                                </div>
                                <div class="form-group">
                                    <label>Keperluan <span class="required">* Wajib</span></label>
                                    <textarea name="keperluan" class="form-control" required>${escapeHtml(data.data.keperluan)}</textarea>
                                    <div class="error-message" id="error-keperluan">Keperluan tidak boleh kosong</div>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan Tambahan</label>
                                    <textarea name="keterangan" class="form-control">${escapeHtml(data.data.keterangan || '')}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>File Pendukung Baru (kosongkan jika tidak ingin mengganti)</label>
                                    <input type="file" name="file_pendukung" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    ${data.data.file_pendukung ? `<p>File saat ini: <a href="../uploads/surat/${data.data.file_pendukung}" target="_blank">Lihat</a></p>` : ''}
                                </div>
                                <div class="btn-group">
                                    <button type="submit" class="btn btn-search">Simpan Perubahan</button>
                                    <button type="button" class="btn btn-reset" onclick="closeEditSuratModal()">Batal</button>
                                </div>
                            </form>
                        `;
                        document.getElementById('editSuratContent').innerHTML = formHTML;
                        document.getElementById('editSuratModal').style.display = 'flex';
                    } else {
                        alert('Gagal memuat数据');
                    }
                })
                .catch(e => alert('Error: ' + e));
        }

        function validateEditSurat(event, id) {
            event.preventDefault();
            
            const form = document.getElementById('formEditSurat');
            const jenisSurat = form.querySelector('select[name="jenis_surat"]').value;
            const keperluan = form.querySelector('textarea[name="keperluan"]').value.trim();
            
            let isValid = true;
            
            document.querySelectorAll('#editSuratContent .error-message').forEach(el => el.classList.remove('show'));
            
            if (!jenisSurat) {
                document.getElementById('error-jenis').classList.add('show');
                isValid = false;
            }
            
            if (!keperluan) {
                document.getElementById('error-keperluan').classList.add('show');
                isValid = false;
            }
            
            if (!isValid) {
                alert('Harap lengkapi semua field yang wajib diisi! (Jenis Surat dan Keperluan)');
                return false;
            }
            
            const formData = new FormData(form);
            formData.append('id', id);
            
            fetch('update_surat.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Data berhasil diperbarui');
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(e => alert('Error: ' + e));
            
            return false;
        }

        function closeEditSuratModal() {
            document.getElementById('editSuratModal').style.display = 'none';
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('detailModal')) closeModal();
            if (event.target == document.getElementById('detailModalSurat')) closeModalSurat();
            if (event.target == document.getElementById('editPengaduanModal')) closeEditPengaduanModal();
            if (event.target == document.getElementById('editSuratModal')) closeEditSuratModal();
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeModalSurat();
                closeEditPengaduanModal();
                closeEditSuratModal();
            }
        });
    </script>
</body>
</html>
<?php
// Clean up
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($stmt_surat)) mysqli_stmt_close($stmt_surat);
if (isset($result) && is_object($result)) mysqli_free_result($result);
if (isset($result_surat) && is_object($result_surat)) mysqli_free_result($result_surat);
mysqli_close($conn);
?>