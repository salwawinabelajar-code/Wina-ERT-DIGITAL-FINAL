<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// Ambil saldo
$saldo_result = $conn->query("SELECT saldo FROM iuran_saldo WHERE id = 1");
$saldo = $saldo_result ? $saldo_result->fetch_assoc()['saldo'] : 0;

// ========== PROSES EXPORT EXCEL ==========
if (isset($_GET['export_excel'])) {
    $filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : '';
    $filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : '';
    $filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
    $filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
    
    $where = "1=1";
    if (!empty($filter_tahun) && !empty($filter_bulan)) {
        $where .= " AND YEAR(tanggal) = $filter_tahun AND MONTH(tanggal) = $filter_bulan";
    }
    if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
        $where .= " AND tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
    }
    
    $query = "SELECT * FROM iuran_kas WHERE $where ORDER BY tanggal DESC";
    $result = mysqli_query($conn, $query);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan_kas_' . date('Y-m-d') . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"><title>Laporan Kas</title></head>';
    echo '<body>';
    echo '<h2>LAPORAN KAS RT 05 SUKAMAJU</h2>';
    echo '<p>Periode: ' . (!empty($filter_tanggal_awal) ? $filter_tanggal_awal : 'Semua') . ' - ' . (!empty($filter_tanggal_akhir) ? $filter_tanggal_akhir : 'Semua') . '</p>';
    echo '<p>Tanggal Cetak: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;">';
    echo '<thead>';
    echo '<tr bgcolor="#CCCCCC">';
    echo '<th>No</th>';
    echo '<th>Tanggal</th>';
    echo '<th>Keterangan</th>';
    echo '<th>Pemasukan</th>';
    echo '<th>Pengeluaran</th>';
    echo '</tr>';
    echo '</thead><tbody>';
    
    $no = 1;
    $total_pemasukan = 0;
    $total_pengeluaran = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
        echo '<td>' . htmlspecialchars($row['keterangan']) . '</td>';
        echo '<td>' . ($row['pemasukan'] ? 'Rp ' . number_format($row['pemasukan'], 0, ',', '.') : '-') . '</td>';
        echo '<td>' . ($row['pengeluaran'] ? 'Rp ' . number_format($row['pengeluaran'], 0, ',', '.') : '-') . '</td>';
        echo '</tr>';
        $total_pemasukan += $row['pemasukan'];
        $total_pengeluaran += $row['pengeluaran'];
    }
    
    echo '<tr bgcolor="#EEEEEE">';
    echo '<td colspan="3"><strong>TOTAL</strong></td>';
    echo '<td><strong>Rp ' . number_format($total_pemasukan, 0, ',', '.') . '</strong></td>';
    echo '<td><strong>Rp ' . number_format($total_pengeluaran, 0, ',', '.') . '</strong></td>';
    echo '</tr>';
    echo '<tr bgcolor="#DDDDDD">';
    echo '<td colspan="3"><strong>SALDO AKHIR</strong></td>';
    echo '<td colspan="2"><strong>Rp ' . number_format($saldo, 0, ',', '.') . '</strong></td>';
    echo '</tr>';
    
    echo '</tbody></table>';
    echo '<p style="margin-top:20px;"><strong>Total Data: ' . ($no-1) . ' transaksi</strong></p>';
    echo '<p>Dicetak oleh: ' . htmlspecialchars($user['nama'] ?? 'Admin') . '</p>';
    echo '</body></html>';
    exit();
}

// ========== PROSES PRINT LAPORAN ==========
if (isset($_GET['print_report'])) {
    $filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : '';
    $filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : '';
    $filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
    $filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
    
    $where = "1=1";
    if (!empty($filter_tahun) && !empty($filter_bulan)) {
        $where .= " AND YEAR(tanggal) = $filter_tahun AND MONTH(tanggal) = $filter_bulan";
    }
    if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
        $where .= " AND tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
    }
    
    $query = "SELECT * FROM iuran_kas WHERE $where ORDER BY tanggal DESC";
    $result = mysqli_query($conn, $query);
    $transaksi_print = [];
    $total_pemasukan_print = 0;
    $total_pengeluaran_print = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $transaksi_print[] = $row;
        $total_pemasukan_print += $row['pemasukan'];
        $total_pengeluaran_print += $row['pengeluaran'];
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Kas - e-RT Digital</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Inter', Arial, sans-serif;
                margin: 20px;
                color: #1F2937;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #213C51;
            }
            .header h1 {
                color: #213C51;
                font-size: 24px;
                margin-bottom: 5px;
            }
            .header p {
                color: #6B7280;
                font-size: 12px;
            }
            .info-box {
                background: #F9FAFB;
                border: 1px solid #E5E7EB;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 20px;
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            .info-item {
                text-align: center;
                flex: 1;
                min-width: 150px;
            }
            .info-label {
                font-size: 12px;
                color: #6B7280;
                margin-bottom: 5px;
            }
            .info-value {
                font-size: 18px;
                font-weight: 700;
                color: #213C51;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #E5E7EB;
                padding: 10px 12px;
                text-align: left;
                font-size: 13px;
            }
            th {
                background: #F3F4F6;
                font-weight: 700;
                color: #374151;
            }
            .pemasukan {
                color: #10B981;
                font-weight: 600;
            }
            .pengeluaran {
                color: #EF4444;
                font-weight: 600;
            }
            .total-row {
                background: #F3F4F6;
                font-weight: 700;
            }
            .footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #E5E7EB;
                text-align: center;
                font-size: 11px;
                color: #9CA3AF;
            }
            .print-btn {
                text-align: center;
                margin-bottom: 20px;
            }
            .print-btn button {
                background: #213C51;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
            }
            .print-btn button:hover {
                background: #2A4D67;
            }
            @media print {
                .print-btn {
                    display: none;
                }
                body {
                    margin: 0;
                    padding: 15px;
                }
                .info-box {
                    break-inside: avoid;
                }
                table {
                    break-inside: auto;
                }
                tr {
                    break-inside: avoid;
                }
            }
        </style>
    </head>
    <body>
        <div class="print-btn">
            <button onclick="window.print()"><i class="fas fa-print"></i> Cetak Laporan</button>
            <button onclick="window.close()" style="background: #6B7280; margin-left: 10px;">Tutup</button>
        </div>
        
        <div class="header">
            <h1>LAPORAN KAS RT 05 SUKAMAJU</h1>
            <p>Periode: <?php 
                if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
                    echo date('d/m/Y', strtotime($filter_tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($filter_tanggal_akhir));
                } elseif (!empty($filter_tahun) && !empty($filter_bulan)) {
                    echo date('F Y', mktime(0,0,0,$filter_bulan,1,$filter_tahun));
                } else {
                    echo 'Semua Periode';
                }
            ?></p>
            <p>Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="info-box">
            <div class="info-item">
                <div class="info-label">Total Pemasukan</div>
                <div class="info-value">Rp <?php echo number_format($total_pemasukan_print, 0, ',', '.'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Pengeluaran</div>
                <div class="info-value">Rp <?php echo number_format($total_pengeluaran_print, 0, ',', '.'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Saldo Kas Saat Ini</div>
                <div class="info-value">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Pemasukan</th>
                    <th>Pengeluaran</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($transaksi_print as $row): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                    <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                    <td class="pemasukan"><?php echo $row['pemasukan'] ? 'Rp ' . number_format($row['pemasukan'], 0, ',', '.') : '-'; ?></td>
                    <td class="pengeluaran"><?php echo $row['pengeluaran'] ? 'Rp ' . number_format($row['pengeluaran'], 0, ',', '.') : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($transaksi_print)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Tidak ada transaksi</td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3"><strong>TOTAL</strong></td>
                    <td><strong>Rp <?php echo number_format($total_pemasukan_print, 0, ',', '.'); ?></strong></td>
                    <td><strong>Rp <?php echo number_format($total_pengeluaran_print, 0, ',', '.'); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>Laporan ini dibuat secara otomatis oleh sistem e-RT Digital</p>
            <p>Dicetak oleh: <?php echo htmlspecialchars($user['nama'] ?? 'Admin'); ?></p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Proses tambah transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $tanggal = $_POST['tanggal'];
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    $pemasukan = (int)$_POST['pemasukan'];
    $pengeluaran = (int)$_POST['pengeluaran'];
    
    $insert = $conn->prepare("INSERT INTO iuran_kas (tanggal, keterangan, pemasukan, pengeluaran) VALUES (?, ?, ?, ?)");
    $insert->bind_param("ssii", $tanggal, $keterangan, $pemasukan, $pengeluaran);
    $insert->execute();
    
    // Update saldo
    if ($pemasukan > 0) {
        $conn->query("UPDATE iuran_saldo SET saldo = saldo + $pemasukan WHERE id = 1");
    } else {
        $conn->query("UPDATE iuran_saldo SET saldo = saldo - $pengeluaran WHERE id = 1");
    }
    header("Location: iuran_kas.php");
    exit();
}

// Proses hapus transaksi
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $trans = $conn->query("SELECT pemasukan, pengeluaran FROM iuran_kas WHERE id = $id");
    $row = $trans->fetch_assoc();
    
    if ($row['pemasukan'] > 0) {
        $conn->query("UPDATE iuran_saldo SET saldo = saldo - {$row['pemasukan']} WHERE id = 1");
    } else {
        $conn->query("UPDATE iuran_saldo SET saldo = saldo + {$row['pengeluaran']} WHERE id = 1");
    }
    $conn->query("DELETE FROM iuran_kas WHERE id = $id");
    header("Location: iuran_kas.php");
    exit();
}

// Ambil semua transaksi dengan filter
$filter_tahun = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : '';
$filter_bulan = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : '';
$filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

$where = "1=1";
if (!empty($filter_tahun) && !empty($filter_bulan)) {
    $where .= " AND YEAR(tanggal) = $filter_tahun AND MONTH(tanggal) = $filter_bulan";
}
if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
    $where .= " AND tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
}

$query = "SELECT * FROM iuran_kas WHERE $where ORDER BY tanggal DESC";
$transaksi = $conn->query($query);

// Hitung total pemasukan dan pengeluaran untuk filter
$total_pemasukan_filter = 0;
$total_pengeluaran_filter = 0;
$temp_transaksi = [];
if ($transaksi) {
    while ($row = $transaksi->fetch_assoc()) {
        $temp_transaksi[] = $row;
        $total_pemasukan_filter += $row['pemasukan'];
        $total_pengeluaran_filter += $row['pengeluaran'];
    }
}
$transaksi_data = $temp_transaksi;

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Manajemen Kas - Admin e-RT Digital</title>
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
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
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

        .header-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
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
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #FEE2E2;
            color: #DC2626;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-danger:hover {
            background: #FECACA;
            transform: translateY(-2px);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.05s;
            animation-fill-mode: both;
        }

        .filter-section:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
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
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-600);
        }

        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(101, 148, 177, 0.2);
            transform: translateY(-2px);
        }

        .btn-filter {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-filter:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-excel {
            background: #10B981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-excel:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-print {
            background: #3B82F6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }

        /* Saldo Card */
        .saldo-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            text-align: center;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.1s;
            animation-fill-mode: both;
        }

        .saldo-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .saldo-card h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-500);
            margin-bottom: 12px;
        }

        .saldo-card .saldo-number {
            font-size: 48px;
            font-weight: 800;
            color: var(--gray-800);
            transition: all 0.3s ease;
        }

        .saldo-card:hover .saldo-number {
            transform: scale(1.02);
        }

        /* Button Add */
        .btn-add {
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.15s;
            animation-fill-mode: both;
        }

        /* Table Container */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.2s;
            animation-fill-mode: both;
        }

        .section-title i {
            color: var(--secondary);
        }

        .table-container {
            background: white;
            border-radius: 16px;
            overflow-x: auto;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.25s;
            animation-fill-mode: both;
        }

        .table-container:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-200);
        }

        td {
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700);
            transition: all 0.3s ease;
        }

        tr {
            transition: all 0.3s ease;
        }

        tr:hover td {
            background: linear-gradient(90deg, rgba(101, 148, 177, 0.05) 0%, rgba(101, 148, 177, 0.1) 100%);
            transform: scale(1.002);
        }

        .pemasukan {
            color: var(--success);
            font-weight: 600;
        }

        .pengeluaran {
            color: var(--danger);
            font-weight: 600;
        }

        .total-row {
            background: #F3F4F6;
            font-weight: 700;
        }

        .total-row td {
            border-top: 2px solid var(--gray-300);
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
            transition: transform 0.3s ease;
        }

        .empty-state:hover i {
            transform: scale(1.1);
            opacity: 0.8;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeInUp 0.3s ease-out;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 28px;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.3s ease-out;
        }

        .modal-content h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
            color: var(--gray-600);
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            color: var(--gray-700);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(101, 148, 177, 0.2);
            transform: translateY(-2px);
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 20px 20px;
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
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .saldo-card .saldo-number {
                font-size: 32px;
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
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
            <a href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama']??'A',0,1)); ?></div>
                <div class="info">
                    <h4><?php echo htmlspecialchars($user['nama']??'Admin'); ?></h4>
                    <p>admin</p>
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
                    <i class="fas fa-cash-register"></i>
                    <h1>Manajemen Kas</h1>
                </div>
                <div class="header-buttons">
                    <a href="iuran.php" class="btn-primary"><i class="fas fa-arrow-left"></i> Kembali</a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form" id="filterForm">
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="filter_tahun" id="filter_tahun">
                            <option value="">Semua Tahun</option>
                            <?php for($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php if($filter_tahun == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Bulan</label>
                        <select name="filter_bulan" id="filter_bulan">
                            <option value="">Semua Bulan</option>
                            <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php if($filter_bulan == $m) echo 'selected'; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Dari Tanggal</label>
                        <input type="date" name="tanggal_awal" value="<?php echo $filter_tanggal_awal; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="tanggal_akhir" value="<?php echo $filter_tanggal_akhir; ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Terapkan</button>
                    </div>
                    <div class="filter-group">
                        <a href="iuran_kas.php" class="btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                    <div class="filter-group">
                        <button type="button" class="btn-excel" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Ekspor Excel</button>
                    </div>
                    <div class="filter-group">
                        <button type="button" class="btn-print" onclick="printReport()"><i class="fas fa-print"></i> Cetak Laporan</button>
                    </div>
                </form>
            </div>

            <!-- Saldo Card -->
            <div class="saldo-card">
                <h2><i class="fas fa-wallet"></i> Saldo Kas Saat Ini</h2>
                <div class="saldo-number">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></div>
            </div>

            <!-- Tombol Tambah Transaksi -->
            <div class="btn-add">
                <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Tambah Transaksi</button>
            </div>

            <!-- Tabel Riwayat Transaksi -->
            <div class="section-title">
                <i class="fas fa-history"></i> Riwayat Transaksi Kas
            </div>
            <div class="table-container">
                <?php if (!empty($transaksi_data)): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Pemasukan</th>
                                    <th>Pengeluaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($transaksi_data as $row): ?>
                                <tr class="animate-slideIn">
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    <td class="pemasukan"><?php echo $row['pemasukan'] ? 'Rp ' . number_format($row['pemasukan'], 0, ',', '.') : '-'; ?></td>
                                    <td class="pengeluaran"><?php echo $row['pengeluaran'] ? 'Rp ' . number_format($row['pengeluaran'], 0, ',', '.') : '-'; ?></td>
                                    <td>
                                        <a href="?hapus=<?php echo $row['id']; ?><?php echo !empty($filter_tahun) ? '&filter_tahun='.$filter_tahun : ''; ?><?php echo !empty($filter_bulan) ? '&filter_bulan='.$filter_bulan : ''; ?><?php echo !empty($filter_tanggal_awal) ? '&tanggal_awal='.$filter_tanggal_awal : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir='.$filter_tanggal_akhir : ''; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="2"><strong>TOTAL</strong></td>
                                    <td><strong>Rp <?php echo number_format($total_pemasukan_filter, 0, ',', '.'); ?></strong></td>
                                    <td><strong>Rp <?php echo number_format($total_pengeluaran_filter, 0, ',', '.'); ?></strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Belum ada transaksi kas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Transaksi -->
    <div id="transaksiModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-plus-circle"></i> Tambah Transaksi Kas</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Iuran warga, Pembelian alat kebersihan, dll" required>
                </div>
                <div class="form-group">
                    <label>Pemasukan</label>
                    <input type="number" name="pemasukan" class="form-control" placeholder="Masukkan 0 jika pengeluaran" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Pengeluaran</label>
                    <input type="number" name="pengeluaran" class="form-control" placeholder="Masukkan 0 jika pemasukan" value="0" min="0">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" name="add_transaction" class="btn-primary">Simpan Transaksi</button>
                </div>
            </form>
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

        // Modal Functions
        function openModal() { 
            document.getElementById('transaksiModal').style.display = 'flex'; 
        }
        
        function closeModal() { 
            document.getElementById('transaksiModal').style.display = 'none'; 
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('transaksiModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Export to Excel
        function exportToExcel() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            let params = new URLSearchParams();
            params.append('export_excel', '1');
            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            window.location.href = '?' + params.toString();
        }

        // Print Report
        function printReport() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            let params = new URLSearchParams();
            params.append('print_report', '1');
            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            window.open('?' + params.toString(), '_blank', 'width=1000,height=800');
        }

        // Add animation to table rows on load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
            });
        });
    </script>
</body>
</html>