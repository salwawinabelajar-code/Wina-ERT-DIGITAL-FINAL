<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data user untuk sidebar
$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// ========== PROSES PRINT LAPORAN ==========
if (isset($_GET['print_report'])) {
    $filter_tahun_print = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
    $filter_bulan_print = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
    $filter_minggu_print = isset($_GET['minggu']) ? $_GET['minggu'] : '';
    
    // Ambil data untuk print
    $query_kk_print = "SELECT k.*, u.nama as kepala_keluarga, u.id as user_id 
                       FROM kartu_keluarga k 
                       LEFT JOIN users u ON k.user_id = u.id 
                       ORDER BY u.nama";
    $result_kk_print = mysqli_query($conn, $query_kk_print);
    $keluarga_list_print = [];
    while ($row = mysqli_fetch_assoc($result_kk_print)) {
        $keluarga_list_print[] = $row;
    }
    
    if (!empty($filter_minggu_print)) {
        $selected_periode_print = $filter_minggu_print;
    } else {
        $selected_periode_print = "$filter_tahun_print-$filter_bulan_print-01";
    }
    
    // Ambil data pembayaran
    $payments_print = [];
    if (!empty($filter_minggu_print)) {
        $query = "SELECT * FROM iuran_payments WHERE status = 'lunas' AND week_start = '$selected_periode_print'";
    } else {
        $start_date = "$filter_tahun_print-$filter_bulan_print-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        $query = "SELECT * FROM iuran_payments WHERE status = 'lunas' AND week_start BETWEEN '$start_date' AND '$end_date'";
    }
    $result_print = mysqli_query($conn, $query);
    if ($result_print) {
        while ($row = mysqli_fetch_assoc($result_print)) {
            $payments_print[$row['keluarga_id']] = $row;
        }
    }
    
    $total_lunas_print = 0;
    $total_belum_print = 0;
    $total_uang_print = 0;
    foreach ($keluarga_list_print as $kk) {
        if (isset($payments_print[$kk['id']]) && $payments_print[$kk['id']]['status'] == 'lunas') {
            $total_lunas_print++;
            $total_uang_print += $payments_print[$kk['id']]['amount'];
        } else {
            $total_belum_print++;
        }
    }
    $total_kk_print = count($keluarga_list_print);
    
    // Ambil data saldo kas
    $saldo_result_print = mysqli_query($conn, "SELECT saldo FROM iuran_saldo WHERE id = 1");
    $current_saldo_print = $saldo_result_print ? (int)mysqli_fetch_assoc($saldo_result_print)['saldo'] : 0;
    
    // Ambil total iuran keseluruhan
    $total_iuran_result_print = mysqli_query($conn, "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas'");
    $total_iuran_print = $total_iuran_result_print ? (int)mysqli_fetch_assoc($total_iuran_result_print)['total'] : 0;
    
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Iuran - e-RT Digital</title>
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
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                margin-bottom: 30px;
            }
            .stat-card {
                background: #F3F4F6;
                border-radius: 12px;
                padding: 15px;
                text-align: center;
                border: 1px solid #E5E7EB;
            }
            .stat-card .number {
                font-size: 24px;
                font-weight: 800;
                color: #213C51;
            }
            .stat-card .label {
                font-size: 12px;
                color: #6B7280;
                margin-top: 5px;
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
            tr:hover {
                background: #F9FAFB;
            }
            .status-lunas {
                background: #D1FAE5;
                color: #059669;
                padding: 4px 10px;
                border-radius: 20px;
                display: inline-block;
                font-size: 11px;
                font-weight: 600;
            }
            .status-belum {
                background: #FEE2E2;
                color: #DC2626;
                padding: 4px 10px;
                border-radius: 20px;
                display: inline-block;
                font-size: 11px;
                font-weight: 600;
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
                .stats-grid {
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
            <h1>LAPORAN IURAN RT 05 SUKAMAJU</h1>
            <p>Periode: <?php echo empty($filter_minggu_print) ? date('F Y', mktime(0,0,0,$filter_bulan_print,1,$filter_tahun_print)) : date('d M Y', strtotime($filter_minggu_print)); ?></p>
            <p>Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="info-box">
            <div class="info-item">
                <div class="info-label">Total KK Terdaftar</div>
                <div class="info-value"><?php echo $total_kk_print; ?> KK</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Iuran Keseluruhan</div>
                <div class="info-value">Rp <?php echo number_format($total_iuran_print,0,',','.'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Saldo Kas Saat Ini</div>
                <div class="info-value">Rp <?php echo number_format($current_saldo_print,0,',','.'); ?></div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo $total_kk_print; ?></div>
                <div class="label">Total KK</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $total_lunas_print; ?></div>
                <div class="label">Lunas</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $total_belum_print; ?></div>
                <div class="label">Belum Bayar</div>
            </div>
            <div class="stat-card">
                <div class="number">Rp <?php echo number_format($total_uang_print,0,',','.'); ?></div>
                <div class="label">Total Uang Diterima</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. KK</th>
                    <th>Kepala Keluarga</th>
                    <th>Status</th>
                    <th>Tanggal Bayar</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($keluarga_list_print as $kk): 
                    $status = isset($payments_print[$kk['id']]) ? 'lunas' : 'belum';
                    $payment_date = isset($payments_print[$kk['id']]) ? $payments_print[$kk['id']]['payment_date'] : null;
                    $amount = isset($payments_print[$kk['id']]) ? $payments_print[$kk['id']]['amount'] : 10000;
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><strong><?php echo htmlspecialchars($kk['no_kk']); ?></strong></td>
                    <td><?php echo htmlspecialchars($kk['kepala_keluarga'] ?? '-'); ?></td>
                    <td>
                        <span class="status-<?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </td>
                    <td><?php echo $payment_date ? date('d/m/Y', strtotime($payment_date)) : '-'; ?></td>
                    <td>Rp <?php echo number_format($amount,0,',','.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
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

// ========== CEK DAN BUAT TABEL YANG DIPERLUKAN ==========
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

// Ambil semua KK (kartu_keluarga) untuk ditampilkan
$query_kk = "SELECT k.*, u.nama as kepala_keluarga, u.id as user_id 
             FROM kartu_keluarga k 
             LEFT JOIN users u ON k.user_id = u.id 
             ORDER BY u.nama";
$result_kk = mysqli_query($conn, $query_kk);
$keluarga_list = [];
while ($row = mysqli_fetch_assoc($result_kk)) {
    $keluarga_list[] = $row;
}

// ========== FILTER ==========
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_minggu = isset($_GET['minggu']) ? $_GET['minggu'] : '';

if (!empty($filter_minggu)) {
    $selected_periode = $filter_minggu;
} else {
    $selected_periode = "$filter_tahun-$filter_bulan-01";
}

// Ambil data pembayaran berdasarkan filter
$payments = [];
if (!empty($filter_minggu)) {
    $query = "SELECT * FROM iuran_payments WHERE status = 'lunas' AND week_start = '$selected_periode'";
} else {
    $start_date = "$filter_tahun-$filter_bulan-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    $query = "SELECT * FROM iuran_payments WHERE status = 'lunas' AND week_start BETWEEN '$start_date' AND '$end_date'";
}
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[$row['keluarga_id']] = $row;
    }
}

// Hitung statistik untuk periode yang dipilih
$total_kk = count($keluarga_list);
$total_lunas = 0;
$total_belum = 0;
$total_uang = 0;
foreach ($keluarga_list as $kk) {
    if (isset($payments[$kk['id']]) && $payments[$kk['id']]['status'] == 'lunas') {
        $total_lunas++;
        $total_uang += $payments[$kk['id']]['amount'];
    } else {
        $total_belum++;
    }
}

// Dapatkan daftar minggu dalam bulan yang dipilih
$weeks = [];
$start = new DateTime("$filter_tahun-$filter_bulan-01");
$end = new DateTime("$filter_tahun-$filter_bulan-" . $start->format('t'));
$interval = new DateInterval('P1D');
$daterange = new DatePeriod($start, $interval, $end->modify('+1 day'));
foreach ($daterange as $date) {
    if ($date->format('l') == 'Saturday') {
        $weeks[] = $date->format('Y-m-d');
    }
}
$week_query = "SELECT DISTINCT week_start FROM iuran_payments WHERE YEAR(week_start)=$filter_tahun AND MONTH(week_start)=$filter_bulan ORDER BY week_start";
$week_result = mysqli_query($conn, $week_query);
if ($week_result) {
    while ($row = mysqli_fetch_assoc($week_result)) {
        $weeks[] = $row['week_start'];
    }
}
$weeks = array_unique($weeks);
sort($weeks);

// ========== PROSES MARK PAID ==========
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $keluarga_id = (int)$_POST['keluarga_id'];
    $week_start = mysqli_real_escape_string($conn, $_POST['week_start']);
    $payment_date = date('Y-m-d');
    $amount = 10000;
    
    $check = mysqli_query($conn, "SELECT id, status FROM iuran_payments WHERE keluarga_id = '$keluarga_id' AND week_start = '$week_start'");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        if ($row['status'] == 'lunas') {
            $error = "Data sudah lunas sebelumnya.";
        } else {
            $update = mysqli_query($conn, "UPDATE iuran_payments SET status = 'lunas', payment_date = '$payment_date' WHERE keluarga_id = '$keluarga_id' AND week_start = '$week_start'");
            if ($update) {
                $message = "Berhasil menandai lunas.";
                mysqli_query($conn, "INSERT INTO iuran_kas (tanggal, keterangan, pemasukan) VALUES ('$payment_date', 'Iuran mingguan KK periode $week_start', $amount)");
                mysqli_query($conn, "UPDATE iuran_saldo SET saldo = saldo + $amount WHERE id = 1");
            } else {
                $error = "Gagal update: " . mysqli_error($conn);
            }
        }
    } else {
        $insert = mysqli_query($conn, "INSERT INTO iuran_payments (keluarga_id, week_start, amount, status, payment_date) VALUES ('$keluarga_id', '$week_start', $amount, 'lunas', '$payment_date')");
        if ($insert) {
            $message = "Berhasil menandai lunas.";
            mysqli_query($conn, "INSERT INTO iuran_kas (tanggal, keterangan, pemasukan) VALUES ('$payment_date', 'Iuran mingguan KK periode $week_start', $amount)");
            mysqli_query($conn, "UPDATE iuran_saldo SET saldo = saldo + $amount WHERE id = 1");
        } else {
            $error = "Gagal insert: " . mysqli_error($conn);
        }
    }
    
    $query_string = http_build_query($_GET);
    header("Location: iuran.php?" . $query_string . "&message=" . urlencode($message) . "&error=" . urlencode($error));
    exit();
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// ========== DATA UNTUK GRAFIK ==========
$chart_tahun = $filter_tahun ?: date('Y');
$chart_labels = [];
$chart_data = [];
for ($m = 1; $m <= 12; $m++) {
    $chart_labels[] = date('M', mktime(0,0,0,$m,1,$chart_tahun));
    $bulan = sprintf("%04d-%02d", $chart_tahun, $m);
    $sum_result = mysqli_query($conn, "SELECT SUM(amount) as total FROM iuran_payments WHERE status='lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan'");
    $sum = mysqli_fetch_assoc($sum_result);
    $chart_data[] = (int)($sum['total'] ?? 0);
}

$saldo_result = mysqli_query($conn, "SELECT saldo FROM iuran_saldo WHERE id = 1");
$current_saldo = $saldo_result ? (int)mysqli_fetch_assoc($saldo_result)['saldo'] : 0;

$total_iuran_result = mysqli_query($conn, "SELECT SUM(amount) as total FROM iuran_payments WHERE status = 'lunas'");
$total_iuran = $total_iuran_result ? (int)mysqli_fetch_assoc($total_iuran_result)['total'] : 0;

$bulan_ini = date('Y-m');
$iuran_bulan_ini_result = mysqli_query($conn, "SELECT SUM(amount) as total FROM iuran_payments WHERE status='lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'");
$iuran_bulan_ini = $iuran_bulan_ini_result ? (int)mysqli_fetch_assoc($iuran_bulan_ini_result)['total'] : 0;

$keluarga_bayar_bulan_ini_result = mysqli_query($conn, "SELECT COUNT(DISTINCT keluarga_id) as total FROM iuran_payments WHERE status='lunas' AND DATE_FORMAT(week_start, '%Y-%m') = '$bulan_ini'");
$keluarga_bayar_bulan_ini = $keluarga_bayar_bulan_ini_result ? (int)mysqli_fetch_assoc($keluarga_bayar_bulan_ini_result)['total'] : 0;

$total_kk_all = count($keluarga_list);
$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Kelola Iuran - Admin e-RT Digital</title>
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
            max-width: 1400px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: scaleIn 0.4s ease-out forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
        .stat-card:nth-child(5) { animation-delay: 0.25s; }
        .stat-card:nth-child(6) { animation-delay: 0.3s; }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .stat-card .stat-icon {
            font-size: 28px;
            margin-bottom: 12px;
            color: var(--secondary);
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-800);
        }

        .stat-card .small-text {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 5px;
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
            animation-delay: 0.1s;
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
            flex: 1 1 150px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-600);
        }

        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: white;
            color: var(--gray-700);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group select:focus {
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
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-filter:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-reset {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .message {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }

        .message.success {
            background: #D1FAE5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .message.error {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.15s;
            animation-fill-mode: both;
        }

        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .chart-container h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container h3 i {
            color: var(--secondary);
            transition: transform 0.3s ease;
        }

        .chart-container:hover h3 i {
            transform: scale(1.1) rotate(5deg);
        }

        .chart-container canvas {
            max-height: 300px;
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
            min-width: 700px;
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

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }

        tr:hover .status-badge {
            transform: scale(1.02);
        }

        .status-badge.lunas {
            background: #D1FAE5;
            color: #059669;
        }

        .status-badge.belum {
            background: #FEE2E2;
            color: #DC2626;
        }

        .btn-mark {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-mark:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .status-lunas-text {
            color: var(--success);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
            max-width: 400px;
            width: 90%;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.3s ease-out;
        }

        .modal-content h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 16px;
        }

        .modal-content p {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
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
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

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
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            .header-buttons {
                justify-content: center;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .stat-card .number {
                font-size: 20px;
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
            <a href="iuran.php" class="active"><i class="fas fa-money-bill-wave"></i> Iuran</a>
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
                    <i class="fas fa-money-bill-wave"></i>
                    <h1>Kelola Iuran</h1>
                </div>
                <div class="header-buttons">
                    <a href="iuran_kas.php" class="btn-primary"><i class="fas fa-cash-register"></i> Manajemen Kas</a>
                    <a href="export_iuran.php?<?php echo http_build_query($_GET); ?>" class="btn-primary"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
                    <a href="#" onclick="printReport()" class="btn-primary"><i class="fas fa-print"></i> Cetak Laporan</a>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Statistik Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="number">Rp <?php echo number_format($total_iuran,0,',','.'); ?></div>
                    <div class="small-text">Total Iuran Keseluruhan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="number">Rp <?php echo number_format($iuran_bulan_ini,0,',','.'); ?></div>
                    <div class="small-text">Iuran Bulan Ini</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="number"><?php echo $keluarga_bayar_bulan_ini; ?></div>
                    <div class="small-text">KK Bayar Bulan Ini</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-address-card"></i></div>
                    <div class="number"><?php echo $total_kk_all; ?></div>
                    <div class="small-text">Total Kartu Keluarga</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <div class="number">Rp <?php echo number_format($current_saldo,0,',','.'); ?></div>
                    <div class="small-text">Saldo Kas Saat Ini</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-percent"></i></div>
                    <div class="number"><?php echo $total_kk_all > 0 ? round(($keluarga_bayar_bulan_ini / $total_kk_all) * 100, 1) : 0; ?>%</div>
                    <div class="small-text">Partisipasi Bulan Ini</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form" id="filterForm">
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="tahun" id="filterTahun">
                            <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php if($filter_tahun == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Bulan</label>
                        <select name="bulan" id="filterBulan">
                            <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php if($filter_bulan == $m) echo 'selected'; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Minggu (opsional)</label>
                        <select name="minggu" id="filterMinggu">
                            <option value="">-- Semua Minggu --</option>
                            <?php foreach($weeks as $w): ?>
                            <option value="<?php echo $w; ?>" <?php if($filter_minggu == $w) echo 'selected'; ?>><?php echo date('d M Y', strtotime($w)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Terapkan</button>
                    </div>
                    <div class="filter-group">
                        <a href="iuran.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Ringkasan Periode -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="number"><?php echo $total_kk; ?></div>
                    <div class="small-text">Total KK</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number"><?php echo $total_lunas; ?></div>
                    <div class="small-text">Lunas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="number"><?php echo $total_belum; ?></div>
                    <div class="small-text">Belum Bayar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="number">Rp <?php echo number_format($total_uang,0,',','.'); ?></div>
                    <div class="small-text">Total Uang</div>
                </div>
            </div>

            <!-- Grafik Pemasukan per Bulan -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-bar" style="color: var(--secondary);"></i> Grafik Pemasukan Iuran Tahun <?php echo $chart_tahun; ?></h3>
                <canvas id="iuranChart" style="max-height:300px;"></canvas>
            </div>

            <!-- Tabel Iuran per KK -->
            <div class="section-title">
                <i class="fas fa-table"></i> Status Iuran Periode: 
                <strong><?php echo empty($filter_minggu) ? date('F Y', mktime(0,0,0,$filter_bulan,1,$filter_tahun)) : date('d M Y', strtotime($filter_minggu)); ?></strong>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. KK</th>
                            <th>Kepala Keluarga</th>
                            <th>Status</th>
                            <th>Tanggal Bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keluarga_list as $kk): ?>
                        <?php 
                            $status = isset($payments[$kk['id']]) ? 'lunas' : 'belum';
                            $payment_date = isset($payments[$kk['id']]) ? $payments[$kk['id']]['payment_date'] : null;
                            $class = ($status == 'lunas') ? 'lunas' : 'belum';
                            $week_start_value = !empty($filter_minggu) ? $filter_minggu : $selected_periode;
                        ?>
                        <tr class="animate-scaleIn">
                            <td><strong><?php echo htmlspecialchars($kk['no_kk']); ?></strong></td>
                            <td><?php echo htmlspecialchars($kk['kepala_keluarga'] ?? '-'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $class; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo $payment_date ? date('d M Y', strtotime($payment_date)) : '-'; ?></td>
                            <td>
                                <?php if ($status != 'lunas'): ?>
                                <button class="btn-mark" onclick="markPaid(<?php echo $kk['id']; ?>, '<?php echo $week_start_value; ?>')">
                                    <i class="fas fa-check-circle"></i> Tandai Lunas
                                </button>
                                <?php else: ?>
                                <span class="status-lunas-text"><i class="fas fa-check-circle"></i> Lunas</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div id="paidModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Pembayaran</h3>
            <p>Apakah KK ini sudah membayar iuran untuk periode yang dipilih?</p>
            <form method="POST" action="">
                <input type="hidden" name="keluarga_id" id="keluarga_id">
                <input type="hidden" name="week_start" id="week_start">
                <input type="hidden" name="mark_paid" value="1">
                <div class="modal-buttons">
                    <button type="button" class="btn-reset" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-primary">Ya, Lunas</button>
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

        // Print Report Function
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

        // Modal Functions
        function markPaid(keluargaId, weekStart) {
            document.getElementById('keluarga_id').value = keluargaId;
            document.getElementById('week_start').value = weekStart;
            document.getElementById('paidModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('paidModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('paidModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Chart
        const ctx = document.getElementById('iuranChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Total Iuran (Rp)',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: '#6594B1',
                    borderColor: '#213C51',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: '#213C51'
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            },
                            color: '#6B7280'
                        },
                        grid: { color: '#E5E7EB' }
                    },
                    x: {
                        ticks: { color: '#6B7280' },
                        grid: { color: '#E5E7EB' }
                    }
                },
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
                }
            }
        });
    </script>
</body>
</html>