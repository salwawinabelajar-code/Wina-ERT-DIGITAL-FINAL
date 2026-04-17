<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Ambil data user untuk sidebar
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// Cek apakah tabel pengumuman ada, jika tidak buat
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'pengumuman'");
if (mysqli_num_rows($table_check) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS pengumuman (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        tanggal DATE NOT NULL,
        penting TINYINT DEFAULT 0,
        tampil TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_table);
} else {
    // Cek kolom penting
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM pengumuman LIKE 'penting'");
    if (mysqli_num_rows($column_check) == 0) {
        mysqli_query($conn, "ALTER TABLE pengumuman ADD penting TINYINT DEFAULT 0 AFTER tanggal");
    }
    // Cek kolom tampil (show/hide)
    $column_check_tampil = mysqli_query($conn, "SHOW COLUMNS FROM pengumuman LIKE 'tampil'");
    if (mysqli_num_rows($column_check_tampil) == 0) {
        mysqli_query($conn, "ALTER TABLE pengumuman ADD tampil TINYINT DEFAULT 1 AFTER penting");
    }
}

$message = '';
$error = '';

// Proses tambah/edit pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $tanggal = !empty($_POST['tanggal']) ? mysqli_real_escape_string($conn, $_POST['tanggal']) : date('Y-m-d');
    $penting = isset($_POST['penting']) ? 1 : 0;
    $tampil = isset($_POST['tampil']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if (empty($judul) || empty($isi)) {
        $error = "Judul dan isi pengumuman harus diisi.";
    } else {
        if ($id > 0) {
            $query = "UPDATE pengumuman SET judul='$judul', isi='$isi', tanggal='$tanggal', penting='$penting', tampil='$tampil' WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $message = "Pengumuman berhasil diperbarui.";
                // Redirect untuk menghindari resubmit
                header("Location: pengumuman.php?success=" . urlencode($message));
                exit();
            } else {
                $error = "Gagal memperbarui pengumuman: " . mysqli_error($conn);
            }
        } else {
            $query = "INSERT INTO pengumuman (judul, isi, tanggal, penting, tampil) VALUES ('$judul', '$isi', '$tanggal', '$penting', '$tampil')";
            if (mysqli_query($conn, $query)) {
                $message = "Pengumuman berhasil ditambahkan.";
                header("Location: pengumuman.php?success=" . urlencode($message));
                exit();
            } else {
                $error = "Gagal menambahkan pengumuman: " . mysqli_error($conn);
            }
        }
    }
}

// Tangkap pesan dari redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

// Proses toggle status tampil (Show/Hide)
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $query = "UPDATE pengumuman SET tampil = NOT tampil WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $message = "Status tampil pengumuman berhasil diubah.";
    } else {
        $error = "Gagal mengubah status tampil: " . mysqli_error($conn);
    }
}

// Ambil data untuk diedit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $query = "SELECT * FROM pengumuman WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_data = mysqli_fetch_assoc($result);
    }
}

// Ambil semua pengumuman (termasuk yang tidak tampil)
$query = "SELECT * FROM pengumuman ORDER BY penting DESC, tanggal DESC, created_at DESC";
$result = mysqli_query($conn, $query);

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Kelola Pengumuman - Admin e-RT Digital</title>
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
            transition: all 0.3s ease;
        }

        .sidebar .logo:hover {
            transform: translateX(5px);
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
            transition: all 0.3s ease;
        }

        .sidebar .logo:hover .logo-icon {
            transform: rotate(5deg) scale(1.05);
            background: var(--secondary-light);
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .sidebar .nav-menu a:hover::before {
            left: 0;
        }

        .sidebar .nav-menu a i {
            width: 20px;
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .sidebar .nav-menu a:hover i {
            transform: translateX(3px);
        }

        .sidebar .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-menu a.active {
            background: var(--secondary);
            color: white;
        }

        .sidebar .user-profile {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            transition: all 0.3s ease;
        }

        .sidebar .user-profile:hover {
            background: rgba(255, 255, 255, 0.05);
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
            transition: all 0.3s ease;
        }

        .sidebar .user-profile:hover .avatar {
            transform: scale(1.1);
            background: var(--secondary-light);
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
            transition: all 0.3s ease;
        }

        .sidebar .logout-btn:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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
            animation: fadeInLeft 0.5s ease-out;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header-left i {
            font-size: 28px;
            color: var(--secondary);
            animation: pulse 2s infinite;
        }

        .page-header-left h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            background: var(--secondary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .back-btn:hover {
            background: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
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

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.1s;
            animation-fill-mode: both;
        }

        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .form-card h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card h2 i {
            color: var(--secondary);
            transition: transform 0.3s ease;
        }

        .form-card:hover h2 i {
            transform: scale(1.1) rotate(5deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: white;
            color: var(--gray-700);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(101, 148, 177, 0.2);
            transform: translateY(-2px);
        }

        textarea.form-control {
            min-height: 140px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--secondary);
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.2s;
            animation-fill-mode: both;
        }

        .table-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .table-container h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container h2 i {
            color: var(--secondary);
            transition: transform 0.3s ease;
        }

        .table-container:hover h2 i {
            transform: scale(1.1) rotate(5deg);
        }

        .pengumuman-item {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border-left: 4px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: scaleIn 0.4s ease-out forwards;
            opacity: 0;
        }

        .pengumuman-item:nth-child(1) { animation-delay: 0.05s; }
        .pengumuman-item:nth-child(2) { animation-delay: 0.1s; }
        .pengumuman-item:nth-child(3) { animation-delay: 0.15s; }
        .pengumuman-item:nth-child(4) { animation-delay: 0.2s; }
        .pengumuman-item:nth-child(5) { animation-delay: 0.25s; }

        .pengumuman-item.penting {
            border-left-color: var(--warning);
            background: linear-gradient(90deg, #FEF3C7 0%, var(--gray-50) 100%);
        }

        .pengumuman-item.tidak-tampil {
            opacity: 0.7;
            background: var(--gray-100);
            border: 1px dashed var(--gray-300);
        }

        .pengumuman-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .pengumuman-item h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .penting-icon {
            color: var(--warning);
            font-size: 16px;
            animation: pulse 2s infinite;
        }

        .penting-badge {
            background: var(--warning);
            color: #92400E;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .pengumuman-item:hover .penting-badge {
            transform: scale(1.05);
        }

        .status-badge {
            background: #E5E7EB;
            color: #4B5563;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }

        .status-badge.tampil {
            background: #D1FAE5;
            color: #059669;
        }

        .status-badge.tidak-tampil {
            background: #FEE2E2;
            color: #DC2626;
        }

        .pengumuman-item:hover .status-badge {
            transform: scale(1.02);
        }

        .date {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pengumuman-item p {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-edit {
            background: #DBEAFE;
            color: #2563EB;
        }

        .btn-edit:hover {
            background: #BFDBFE;
        }

        .btn-toggle {
            background: #E5E7EB;
            color: #4B5563;
        }

        .btn-toggle.tampil {
            background: #D1FAE5;
            color: #059669;
        }

        .btn-toggle.tidak-tampil {
            background: #FEE2E2;
            color: #DC2626;
        }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .empty-state:hover i {
            transform: scale(1.1);
            opacity: 0.8;
        }

        /* Info Box */
        .info-box {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1E40AF;
            animation: slideIn 0.3s ease-out;
        }

        .info-box i {
            font-size: 20px;
        }

        .info-box p {
            font-size: 13px;
            margin: 0;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 24px 20px;
            margin-top: 30px;
            text-align: center;
            color: var(--gray-500);
            transition: all 0.3s ease;
        }

        .footer:hover {
            box-shadow: var(--shadow-md);
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
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            transform: scale(1.05);
            background: var(--secondary);
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
            transition: opacity 0.3s ease;
        }

        /* Responsive */
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
            .form-card {
                padding: 20px;
            }
            .table-container {
                padding: 16px;
            }
            .pengumuman-item {
                padding: 16px;
            }
            .actions {
                justify-content: flex-start;
                margin-top: 12px;
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
            <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Layanan Surat</a>
            <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="pengumuman.php" class="active"><i class="fas fa-bullhorn"></i> Pengumuman</a>
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
                    <i class="fas fa-bullhorn"></i>
                    <h1>Kelola Pengumuman</h1>
                </div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Pengumuman dengan status <strong>"Tidak Tampil"</strong> tidak akan muncul di halaman user. Data pengumuman tetap tersimpan dan dapat diubah statusnya kapan saja.</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Form Tambah/Edit -->
            <div class="form-card">
                <h2><i class="fas fa-<?php echo $edit_data ? 'edit' : 'plus'; ?>"></i> <?php echo $edit_data ? 'Edit Pengumuman' : 'Tambah Pengumuman Baru'; ?></h2>
                <form method="POST" action="">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="judul">Judul Pengumuman</label>
                        <input type="text" name="judul" id="judul" class="form-control" value="<?php echo $edit_data ? htmlspecialchars($edit_data['judul']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="isi">Isi Pengumuman</label>
                        <textarea name="isi" id="isi" class="form-control" required><?php echo $edit_data ? htmlspecialchars($edit_data['isi']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tanggal">Tanggal (opsional, default hari ini)</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?php echo $edit_data ? htmlspecialchars($edit_data['tanggal']) : date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="penting" id="penting" <?php echo ($edit_data && $edit_data['penting'] == 1) ? 'checked' : ''; ?>>
                        <label for="penting">Tandai sebagai pengumuman penting</label>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="tampil" id="tampil" <?php echo ($edit_data && $edit_data['tampil'] == 1) || !$edit_data ? 'checked' : ''; ?>>
                        <label for="tampil">Tampilkan di halaman user (Jika tidak dicentang, pengumuman hanya terlihat di panel admin)</label>
                    </div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_data ? 'Perbarui' : 'Simpan'; ?></button>
                        <?php if ($edit_data): ?>
                            <a href="pengumuman.php" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Daftar Pengumuman -->
            <div class="table-container">
                <h2><i class="fas fa-history"></i> Riwayat Pengumuman</h2>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): 
                        $penting_class = $row['penting'] ? 'penting' : '';
                        $tampil_class = $row['tampil'] == 1 ? 'tampil' : 'tidak-tampil';
                        $item_class = $row['tampil'] == 0 ? 'tidak-tampil' : '';
                    ?>
                        <div class="pengumuman-item <?php echo $penting_class . ' ' . $item_class; ?>">
                            <h3>
                                <?php if ($row['penting']): ?>
                                    <i class="fas fa-exclamation-triangle penting-icon"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($row['judul']); ?>
                                <?php if ($row['penting']): ?>
                                    <span class="penting-badge">PENTING</span>
                                <?php endif; ?>
                                <span class="status-badge <?php echo $tampil_class; ?>">
                                    <i class="fas fa-<?php echo $row['tampil'] == 1 ? 'eye' : 'eye-slash'; ?>"></i>
                                    <?php echo $row['tampil'] == 1 ? 'Tampil' : 'Tidak Tampil'; ?>
                                </span>
                            </h3>
                            <div class="date">
                                <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($row['tanggal'])); ?>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($row['isi'])); ?></p>
                            <div class="actions">
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?toggle=<?php echo $row['id']; ?>" class="btn-action btn-toggle <?php echo $tampil_class; ?>" onclick="return confirm('Ubah status tampil pengumuman ini?')">
                                    <i class="fas fa-<?php echo $row['tampil'] == 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                    <?php echo $row['tampil'] == 1 ? 'Sembunyikan' : 'Tampilkan'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <p>Belum ada pengumuman. Silakan tambahkan pengumuman baru.</p>
                    </div>
                <?php endif; ?>
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
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>