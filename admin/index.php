<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data admin
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// ========== CEK TABEL YANG DIPERLUKAN ==========
$check_payments = mysqli_query($conn, "SHOW TABLES LIKE 'iuran_payments'");
if (mysqli_num_rows($check_payments) == 0) {
    $create_payments = "CREATE TABLE iuran_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        keluarga_id INT NOT NULL,
        week_start DATE NOT NULL,
        amount INT NOT NULL DEFAULT 10000,
        status ENUM('lunas','belum','pending') DEFAULT 'belum',
        payment_date DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_payments);
}

$check_kas = mysqli_query($conn, "SHOW TABLES LIKE 'iuran_kas'");
if (mysqli_num_rows($check_kas) == 0) {
    $create_kas = "CREATE TABLE iuran_kas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        keterangan TEXT,
        pemasukan INT DEFAULT 0,
        pengeluaran INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_kas);
}

$check_saldo = mysqli_query($conn, "SHOW TABLES LIKE 'iuran_saldo'");
if (mysqli_num_rows($check_saldo) == 0) {
    $create_saldo = "CREATE TABLE iuran_saldo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        saldo INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_saldo);
    mysqli_query($conn, "INSERT INTO iuran_saldo (id, saldo) VALUES (1, 0)");
}

// ========== STATISTIK ==========
$stats = [];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengaduan");
$stats['total_pengaduan'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

$result = mysqli_query($conn, "SELECT status, COUNT(*) as jumlah FROM pengaduan GROUP BY status");
$pengaduan_status = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pengaduan_status[$row['status']] = $row['jumlah'];
}
$stats['pengaduan_baru'] = $pengaduan_status['baru'] ?? 0;
$stats['pengaduan_diproses'] = $pengaduan_status['diproses'] ?? 0;
$stats['pengaduan_selesai'] = $pengaduan_status['selesai'] ?? 0;
$stats['pengaduan_ditolak'] = $pengaduan_status['ditolak'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan_surat");
$stats['total_surat'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

$result = mysqli_query($conn, "SELECT status, COUNT(*) as jumlah FROM pengajuan_surat GROUP BY status");
$surat_status = [];
while ($row = mysqli_fetch_assoc($result)) {
    $surat_status[$row['status']] = $row['jumlah'];
}
$stats['surat_menunggu'] = $surat_status['menunggu'] ?? 0;
$stats['surat_diproses'] = $surat_status['diproses'] ?? 0;
$stats['surat_selesai'] = $surat_status['selesai'] ?? 0;
$stats['surat_ditolak'] = $surat_status['ditolak'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='warga'");
$stats['total_warga'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM kartu_keluarga");
$stats['total_kk'] = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

// Iuran
$query_total_iuran = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas'";
$result_total_iuran = mysqli_query($conn, $query_total_iuran);
$stats['total_iuran_keseluruhan'] = ($result_total_iuran && mysqli_num_rows($result_total_iuran) > 0) ? (int)mysqli_fetch_assoc($result_total_iuran)['total'] : 0;

$bulan_ini = date('Y-m');
$query_iuran_bulan = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'";
$result_iuran_bulan = mysqli_query($conn, $query_iuran_bulan);
$stats['iuran_bulan_ini'] = ($result_iuran_bulan && mysqli_num_rows($result_iuran_bulan) > 0) ? (int)mysqli_fetch_assoc($result_iuran_bulan)['total'] : 0;

$bulan_lalu = date('Y-m', strtotime('-1 month'));
$query_iuran_bulan_lalu = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_lalu'";
$result_iuran_bulan_lalu = mysqli_query($conn, $query_iuran_bulan_lalu);
$stats['iuran_bulan_lalu'] = ($result_iuran_bulan_lalu && mysqli_num_rows($result_iuran_bulan_lalu) > 0) ? (int)mysqli_fetch_assoc($result_iuran_bulan_lalu)['total'] : 0;

if ($stats['iuran_bulan_lalu'] > 0) {
    $stats['iuran_persen'] = round(($stats['iuran_bulan_ini'] - $stats['iuran_bulan_lalu']) / $stats['iuran_bulan_lalu'] * 100, 1);
} else {
    $stats['iuran_persen'] = $stats['iuran_bulan_ini'] > 0 ? 100 : 0;
}

$query_keluarga_bayar = "SELECT COUNT(DISTINCT keluarga_id) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'";
$result_keluarga_bayar = mysqli_query($conn, $query_keluarga_bayar);
$stats['keluarga_bayar_bulan_ini'] = ($result_keluarga_bayar && mysqli_num_rows($result_keluarga_bayar) > 0) ? (int)mysqli_fetch_assoc($result_keluarga_bayar)['total'] : 0;

$stats['target_iuran'] = $stats['total_kk'] * 40000;
$stats['pencapaian_iuran'] = $stats['target_iuran'] > 0 ? round(($stats['iuran_bulan_ini'] / $stats['target_iuran']) * 100, 1) : 0;

$query_total_transaksi = "SELECT COUNT(*) as total FROM iuran_payments WHERE status = 'lunas'";
$result_total_transaksi = mysqli_query($conn, $query_total_transaksi);
$stats['total_transaksi_iuran'] = ($result_total_transaksi && mysqli_num_rows($result_total_transaksi) > 0) ? (int)mysqli_fetch_assoc($result_total_transaksi)['total'] : 0;

$saldo_result = mysqli_query($conn, "SELECT saldo FROM iuran_saldo WHERE id = 1");
$current_saldo = $saldo_result ? (int)mysqli_fetch_assoc($saldo_result)['saldo'] : 0;

// Grafik
$bulan_labels = [];
$pengaduan_bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $bulan_labels[] = date('M Y', strtotime($bulan . '-01'));
    $query = "SELECT COUNT(*) as total FROM pengaduan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $pengaduan_bulan[] = $row['total'] ?? 0;
}

$surat_chart_data = [
    $stats['surat_menunggu'],
    $stats['surat_diproses'],
    $stats['surat_selesai'],
    $stats['surat_ditolak']
];

$iuran_bulan_labels = [];
$iuran_bulan_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $iuran_bulan_labels[] = date('M Y', strtotime($bulan . '-01'));
    $query = "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $iuran_bulan_data[] = (int)($row['total'] ?? 0);
}

// Data terbaru
$query = "SELECT p.*, u.nama FROM pengaduan p JOIN users u ON p.user_id = u.id ORDER BY p.tanggal DESC LIMIT 5";
$result_pengaduan = mysqli_query($conn, $query);

$query = "SELECT s.*, u.nama FROM pengajuan_surat s JOIN users u ON s.user_id = u.id ORDER BY s.tanggal_pengajuan DESC LIMIT 5";
$result_surat = mysqli_query($conn, $query);

$query_iuran_terbaru = "SELECT ip.*, k.no_kk, u.nama as kepala_keluarga 
                         FROM iuran_payments ip 
                         LEFT JOIN kartu_keluarga k ON ip.keluarga_id = k.id
                         LEFT JOIN users u ON k.user_id = u.id
                         ORDER BY ip.created_at DESC LIMIT 5";
$result_iuran_terbaru = mysqli_query($conn, $query_iuran_terbaru);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Dashboard Admin - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-100);
            color: var(--gray-800);
            min-height: 100vh;
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

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Aplikasi animasi ke elemen */
        .sidebar {
            animation: fadeInLeft var(--transition-slow) ease-out;
        }

        .main-content {
            animation: fadeInRight var(--transition-slow) ease-out;
        }

        .stat-card {
            animation: fadeInUp var(--transition-normal) ease-out;
            animation-fill-mode: both;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
        .stat-card:nth-child(5) { animation-delay: 0.25s; }
        .stat-card:nth-child(6) { animation-delay: 0.3s; }
        .stat-card:nth-child(7) { animation-delay: 0.35s; }
        .stat-card:nth-child(8) { animation-delay: 0.4s; }
        .stat-card:nth-child(9) { animation-delay: 0.45s; }
        .stat-card:nth-child(10) { animation-delay: 0.5s; }
        .stat-card:nth-child(11) { animation-delay: 0.55s; }

        .chart-card {
            animation: fadeInUp var(--transition-normal) ease-out;
            animation-fill-mode: both;
        }
        .chart-card:nth-child(1) { animation-delay: 0.1s; }
        .chart-card:nth-child(2) { animation-delay: 0.2s; }
        .chart-card:nth-child(3) { animation-delay: 0.3s; }

        .recent-card {
            animation: fadeInUp var(--transition-normal) ease-out;
            animation-fill-mode: both;
        }
        .recent-card:nth-child(1) { animation-delay: 0.15s; }
        .recent-card:nth-child(2) { animation-delay: 0.25s; }
        .recent-card:nth-child(3) { animation-delay: 0.35s; }

        .welcome-message {
            animation: fadeInUp var(--transition-slow) ease-out;
        }

        .content-header {
            animation: fadeInUp var(--transition-normal) ease-out;
        }

        /* Layout - Flexbox untuk responsif */
        .app {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Fixed untuk desktop */
        .sidebar {
            width: 280px;
            background: var(--primary);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow-y: auto;
        }

        .sidebar:hover {
            box-shadow: var(--shadow-lg);
        }

        /* Scrollbar styling */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: var(--secondary);
            border-radius: 5px;
        }

        /* Logo */
        .sidebar .logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform var(--transition-fast);
        }

        .sidebar .logo:hover {
            transform: scale(1.02);
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
            flex-shrink: 0;
            transition: all var(--transition-fast);
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
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }

        /* Navigation Menu */
        .sidebar .nav-menu {
            flex: 1;
            padding: 0 12px;
        }

        .sidebar .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            margin-bottom: 4px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all var(--transition-fast);
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
            transition: left var(--transition-normal);
            z-index: 0;
        }

        .sidebar .nav-menu a:hover::before {
            left: 0;
        }

        .sidebar .nav-menu a i {
            width: 20px;
            font-size: 16px;
            transition: transform var(--transition-fast);
            position: relative;
            z-index: 1;
        }

        .sidebar .nav-menu a:hover i {
            transform: translateX(3px);
        }

        .sidebar .nav-menu a span {
            position: relative;
            z-index: 1;
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

        .sidebar .nav-menu a.active i {
            animation: pulse 0.5s ease;
        }

        /* User Profile */
        .sidebar .user-profile {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            transition: background var(--transition-fast);
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
            flex-shrink: 0;
            transition: all var(--transition-fast);
        }

        .sidebar .user-profile .avatar:hover {
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
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }

        /* Logout Button */
        .sidebar .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            margin-top: 12px;
            transition: all var(--transition-fast);
        }

        .sidebar .logout-btn:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        .sidebar .logout-btn:active {
            transform: translateY(0);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 24px 32px;
            width: calc(100% - 280px);
        }

        /* Header */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .content-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-800);
            transition: color var(--transition-fast);
        }

        .content-header h1:hover {
            color: var(--primary);
            transform: translateX(5px);
        }

        .content-header .date {
            background: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            color: var(--gray-600);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-fast);
        }

        .content-header .date:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Welcome Card */
        .welcome-message {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
        }

        .welcome-message:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .welcome-message .avatar-large {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
            flex-shrink: 0;
            transition: all var(--transition-fast);
        }

        .welcome-message:hover .avatar-large {
            transform: scale(1.05);
            background: linear-gradient(135deg, var(--secondary), var(--primary));
        }

        .welcome-message h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 6px;
        }

        .welcome-message p {
            font-size: 14px;
            color: var(--gray-500);
        }

        /* Stats Grid - Responsive */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .stat-card:active {
            transform: translateY(-2px) scale(1.01);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            background: rgba(101, 148, 177, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            transition: all var(--transition-fast);
        }

        .stat-card:hover .stat-icon {
            background: rgba(101, 148, 177, 0.2);
            animation: bounce 0.5s ease;
        }

        .stat-card .stat-icon i {
            font-size: 24px;
            color: var(--secondary);
            transition: transform var(--transition-fast);
        }

        .stat-card:hover .stat-icon i {
            transform: scale(1.1);
        }

        .stat-card h3 {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-800);
            transition: color var(--transition-fast);
        }

        .stat-card:hover .number {
            color: var(--primary);
        }

        .stat-card .trend {
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-card .trend.up { color: var(--success); }
        .stat-card .trend.down { color: var(--danger); }

        .stat-card .small-text {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 4px;
        }

        /* Charts Row - Responsive */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
        }

        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .chart-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-card h3 i {
            color: var(--secondary);
            transition: transform var(--transition-fast);
        }

        .chart-card:hover h3 i {
            transform: rotate(360deg);
        }

        .chart-card canvas {
            max-height: 260px;
            width: 100%;
            transition: all var(--transition-normal);
        }

        .chart-card:hover canvas {
            transform: scale(1.02);
        }

        /* Recent Section - Responsive */
        .recent-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .recent-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
        }

        .recent-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .recent-card .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
            flex-wrap: wrap;
            gap: 8px;
        }

        .recent-card .header h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .recent-card .header h3 i {
            transition: transform var(--transition-fast);
        }

        .recent-card:hover .header h3 i {
            transform: scale(1.1);
        }

        .recent-card .header a {
            font-size: 12px;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-fast);
        }

        .recent-card .header a:hover {
            color: var(--primary);
            transform: translateX(3px);
        }

        .recent-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .recent-item:hover {
            background: var(--gray-50);
            transform: translateX(5px);
            padding-left: 5px;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item .title {
            font-weight: 600;
            color: var(--gray-800);
            font-size: 14px;
            margin-bottom: 6px;
        }

        .recent-item .meta {
            display: flex;
            gap: 12px;
            font-size: 11px;
            color: var(--gray-500);
            flex-wrap: wrap;
        }

        .recent-item .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            transition: all var(--transition-fast);
        }

        .recent-item .status:hover {
            transform: scale(1.05);
        }

        .status-baru, .status-menunggu {
            background: #FEF3C7;
            color: #D97706;
        }
        .status-diproses {
            background: #DBEAFE;
            color: #2563EB;
        }
        .status-selesai, .status-lunas {
            background: #D1FAE5;
            color: #059669;
        }
        .status-ditolak {
            background: #FEE2E2;
            color: #DC2626;
        }

        .empty-state {
            text-align: center;
            padding: 32px;
            color: var(--gray-500);
            transition: all var(--transition-fast);
        }

        .empty-state:hover {
            transform: scale(1.02);
        }

        /* Mobile Menu Toggle Button */
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
            transition: all var(--transition-fast);
        }

        .menu-toggle:hover {
            background: var(--secondary);
            transform: scale(1.05);
        }

        .menu-toggle:active {
            transform: scale(0.95);
        }

        /* Overlay untuk mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            transition: opacity var(--transition-normal);
        }

        /* Loading Animation */
        .loading {
            background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-100) 50%, var(--gray-200) 75%);
            background-size: 1000px 100%;
            animation: shimmer 1.5s infinite;
        }

        /* ========== RESPONSIVE BREAKPOINTS ========== */

        /* Tablet (768px - 1024px) */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .recent-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Mobile (< 768px) */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
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
                padding: 70px 16px 24px 16px;
            }
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .content-header {
                flex-direction: column;
                text-align: center;
            }
            .welcome-message {
                flex-direction: column;
                text-align: center;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .charts-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .recent-section {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .stat-card .number {
                font-size: 24px;
            }
        }

        /* Mobile Small (< 480px) */
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .main-content {
                padding: 70px 12px 20px 12px;
            }
            .welcome-message .avatar-large {
                width: 56px;
                height: 56px;
                font-size: 24px;
            }
            .welcome-message h2 {
                font-size: 18px;
            }
            .welcome-message p {
                font-size: 13px;
            }
            .content-header h1 {
                font-size: 20px;
            }
            .chart-card h3 {
                font-size: 14px;
            }
            .recent-card .header h3 {
                font-size: 14px;
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

    <div class="app">
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
                <a href="index.php" class="active"><i class="fas fa-home"></i><span>Beranda</span></a>
                <a href="pengaduan.php"><i class="fas fa-comment-medical"></i><span>Pengaduan</span></a>
                <a href="surat.php"><i class="fas fa-envelope-open-text"></i><span>Layanan Surat</span></a>
                <a href="iuran.php"><i class="fas fa-money-bill-wave"></i><span>Iuran</span></a>
                <a href="pengumuman.php"><i class="fas fa-bullhorn"></i><span>Pengumuman</span></a>
                <a href="kk.php"><i class="fas fa-address-card"></i><span>Data KK</span></a>
                <a href="galeri.php"><i class="fas fa-images"></i><span>Galeri</span></a>
                <a href="pengaturan.php"><i class="fas fa-cog"></i><span>Pengaturan</span></a>
            </div>

            <div class="user-profile">
                <a href="profil.php">
                    <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                    <div class="info">
                        <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>Dashboard Admin</h1>
                <div class="date">
                    <i class="far fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="welcome-message">
                <div class="avatar-large"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div>
                    <h2>Selamat datang kembali, <?php echo htmlspecialchars($user['nama']); ?>!</h2>
                    <p>Berikut adalah ringkasan aktivitas terkini di sistem e-RT Digital. Anda dapat memantau pengaduan, surat, iuran, dan data warga dengan mudah.</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <h3>Total Pengaduan</h3>
                    <div class="number"><?php echo $stats['total_pengaduan']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Pengaduan Baru</h3>
                    <div class="number"><?php echo $stats['pengaduan_baru']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    <h3>Pengaduan Diproses</h3>
                    <div class="number"><?php echo $stats['pengaduan_diproses']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Pengaduan Selesai</h3>
                    <div class="number"><?php echo $stats['pengaduan_selesai']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <h3>Surat Menunggu</h3>
                    <div class="number"><?php echo $stats['surat_menunggu']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <h3>Total Warga</h3>
                    <div class="number"><?php echo $stats['total_warga']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-address-card"></i></div>
                    <h3>Kartu Keluarga</h3>
                    <div class="number"><?php echo $stats['total_kk']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <h3>Total Iuran</h3>
                    <div class="number">Rp <?php echo number_format($stats['total_iuran_keseluruhan'], 0, ',', '.'); ?></div>
                    <div class="small-text"><?php echo $stats['total_transaksi_iuran']; ?> transaksi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Iuran Bulan Ini</h3>
                    <div class="number">Rp <?php echo number_format($stats['iuran_bulan_ini'], 0, ',', '.'); ?></div>
                    <div class="trend <?php echo $stats['iuran_persen'] >= 0 ? 'up' : 'down'; ?>">
                        <?php if ($stats['iuran_persen'] > 0): ?>
                            <i class="fas fa-arrow-up"></i> <?php echo $stats['iuran_persen']; ?>% dari bulan lalu
                        <?php elseif ($stats['iuran_persen'] < 0): ?>
                            <i class="fas fa-arrow-down"></i> <?php echo abs($stats['iuran_persen']); ?>% dari bulan lalu
                        <?php else: ?>
                            Sama seperti bulan lalu
                        <?php endif; ?>
                    </div>
                    <div class="small-text"><?php echo $stats['keluarga_bayar_bulan_ini']; ?> KK sudah bayar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Saldo Kas</h3>
                    <div class="number">Rp <?php echo number_format($current_saldo, 0, ',', '.'); ?></div>
                    <div class="small-text">Saldo kas saat ini</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
                    <h3>Target Iuran</h3>
                    <div class="number">Rp <?php echo number_format($stats['target_iuran'], 0, ',', '.'); ?></div>
                    <div class="small-text">Pencapaian: <?php echo $stats['pencapaian_iuran']; ?>%</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-row">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Tren Pengaduan (6 Bulan)</h3>
                    <canvas id="pengaduanChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Status Surat</h3>
                    <canvas id="suratChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar"></i> Tren Iuran (6 Bulan)</h3>
                    <canvas id="iuranChart"></canvas>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="recent-section">
                <!-- Pengaduan Terbaru -->
                <div class="recent-card">
                    <div class="header">
                        <h3><i class="fas fa-comment-medical"></i> Pengaduan Terbaru</h3>
                        <a href="pengaduan.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (mysqli_num_rows($result_pengaduan) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_pengaduan)): ?>
                        <div class="recent-item">
                            <div class="title"><?php echo htmlspecialchars($row['judul']); ?></div>
                            <div class="meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['nama']); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('d M H:i', strtotime($row['tanggal'])); ?></span>
                                <span class="status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">Belum ada pengaduan.</div>
                    <?php endif; ?>
                </div>

                <!-- Surat Terbaru -->
                <div class="recent-card">
                    <div class="header">
                        <h3><i class="fas fa-envelope-open-text"></i> Pengajuan Surat</h3>
                        <a href="surat.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (mysqli_num_rows($result_surat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_surat)): ?>
                        <div class="recent-item">
                            <div class="title"><?php echo htmlspecialchars(ucfirst($row['jenis_surat'])); ?></div>
                            <div class="meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['nama']); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('d M H:i', strtotime($row['tanggal_pengajuan'])); ?></span>
                                <span class="status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">Belum ada pengajuan surat.</div>
                    <?php endif; ?>
                </div>

                <!-- Iuran Terbaru -->
                <div class="recent-card">
                    <div class="header">
                        <h3><i class="fas fa-money-bill-wave"></i> Pembayaran Iuran</h3>
                        <a href="iuran.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (mysqli_num_rows($result_iuran_terbaru) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_iuran_terbaru)): ?>
                        <div class="recent-item">
                            <div class="title">Iuran - <?php echo htmlspecialchars($row['kepala_keluarga'] ?? 'KK'); ?></div>
                            <div class="meta">
                                <span><i class="fas fa-address-card"></i> No KK: <?php echo htmlspecialchars($row['no_kk']); ?></span>
                                <span><i class="far fa-calendar"></i> <?php echo date('d M Y', strtotime($row['week_start'])); ?></span>
                                <span class="status status-lunas">Lunas</span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">Belum ada pembayaran iuran.</div>
                    <?php endif; ?>
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

        // Close sidebar on window resize if screen becomes desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

        // Close sidebar when clicking a link (for mobile)
        const sidebarLinks = document.querySelectorAll('.sidebar .nav-menu a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

        // Add ripple effect to buttons
        const buttons = document.querySelectorAll('.stat-card, .btn, .sidebar .nav-menu a, .logout-btn');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                let ripple = document.createElement('span');
                ripple.classList.add('ripple');
                this.appendChild(ripple);
                
                let x = e.clientX - e.target.offsetLeft;
                let y = e.clientY - e.target.offsetTop;
                
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Chart Pengaduan
        const ctx1 = document.getElementById('pengaduanChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($bulan_labels); ?>,
                datasets: [{
                    label: 'Jumlah Pengaduan',
                    data: <?php echo json_encode($pengaduan_bulan); ?>,
                    borderColor: '#6594B1',
                    backgroundColor: 'rgba(101, 148, 177, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#213C51',
                    pointBorderColor: '#6594B1',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#213C51'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#374151' } },
                    tooltip: {
                        backgroundColor: '#213C51',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: { ticks: { color: '#6B7280' }, grid: { color: '#E5E7EB' } },
                    y: { ticks: { color: '#6B7280' }, grid: { color: '#E5E7EB' } }
                },
                animations: {
                    tension: {
                        duration: 1000,
                        easing: 'linear',
                        from: 1,
                        to: 0,
                        loop: true
                    }
                }
            }
        });

        // Chart Surat
        const ctx2 = document.getElementById('suratChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'],
                datasets: [{
                    data: <?php echo json_encode($surat_chart_data); ?>,
                    backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#EF4444'],
                    borderColor: 'white',
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#374151' } },
                    tooltip: {
                        backgroundColor: '#213C51',
                        titleColor: '#fff',
                        bodyColor: '#fff'
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1500
                }
            }
        });

        // Chart Iuran
        const ctx3 = document.getElementById('iuranChart').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($iuran_bulan_labels); ?>,
                datasets: [{
                    label: 'Total Iuran (Rp)',
                    data: <?php echo json_encode($iuran_bulan_data); ?>,
                    backgroundColor: '#6594B1',
                    borderRadius: 8,
                    hoverBackgroundColor: '#213C51',
                    transition: {
                        duration: 1000,
                        easing: 'easeOutBounce'
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#374151' } },
                    tooltip: {
                        backgroundColor: '#213C51',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        ticks: { color: '#6B7280' }, 
                        grid: { color: '#E5E7EB' },
                        animations: {
                            ticks: {
                                duration: 500
                            }
                        }
                    },
                    y: { 
                        ticks: { 
                            color: '#6B7280',
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }, 
                        grid: { color: '#E5E7EB' },
                        animations: {
                            ticks: {
                                duration: 500
                            }
                        }
                    }
                },
                animations: {
                    y: {
                        duration: 1000,
                        easing: 'easeOutBounce'
                    }
                }
            }
        });

        // Add animation to stat cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-card, .chart-card, .recent-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            observer.observe(el);
        });
    </script>
    <style>
        /* Ripple effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .stat-card, .btn, .sidebar .nav-menu a, .logout-btn {
            position: relative;
            overflow: hidden;
        }
    </style>
</body>
</html>
<?php mysqli_close($conn);
?>