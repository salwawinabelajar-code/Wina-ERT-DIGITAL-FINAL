<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// ========== PROSES UPDATE STATUS ==========
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE pengaduan SET status='$status' WHERE id=$id");
    header("Location: pengaduan.php");
    exit();
}

// ========== PROSES HAPUS ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM pengaduan WHERE id=$id");
    header("Location: pengaduan.php");
    exit();
}

// ========== PROSES EXPORT EXCEL ==========
if (isset($_GET['export_excel'])) {
    $search_export = isset($_GET['search']) ? $_GET['search'] : '';
    $status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    $where_export = "1=1";
    if (!empty($status_filter)) {
        $where_export .= " AND p.status = '$status_filter'";
    }
    if (!empty($search_export)) {
        $search_export = mysqli_real_escape_string($conn, $search_export);
        $where_export .= " AND (p.judul LIKE '%$search_export%' OR u.nama LIKE '%$search_export%')";
    }
    if (!empty($start_date)) {
        $where_export .= " AND DATE(p.tanggal) >= '$start_date'";
    }
    if (!empty($end_date)) {
        $where_export .= " AND DATE(p.tanggal) <= '$end_date'";
    }
    
    $query_export = "SELECT p.id, u.nama, p.judul, p.kategori, p.lokasi, p.deskripsi, p.tanggal, p.status, p.urgensi 
                     FROM pengaduan p 
                     JOIN users u ON p.user_id = u.id 
                     WHERE $where_export 
                     ORDER BY p.tanggal DESC";
    $result_export = mysqli_query($conn, $query_export);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan_pengaduan_' . date('Y-m-d') . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"><title>Laporan Pengaduan</title></head>';
    echo '<body>';
    echo '<h2>Laporan Pengaduan Warga</h2>';
    echo '<p>Periode: ' . (!empty($start_date) ? $start_date : 'Semua') . ' - ' . (!empty($end_date) ? $end_date : 'Semua') . '</p>';
    echo '<p>Status: ' . (!empty($status_filter) ? ucfirst($status_filter) : 'Semua') . '</p>';
    echo '<p>Tanggal Cetak: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;">';
    echo '<thead>';
    echo '<tr bgcolor="#CCCCCC">';
    echo '<th>No</th>';
    echo '<th>ID</th>';
    echo '<th>Warga</th>';
    echo '<th>Judul</th>';
    echo '<th>Kategori</th>';
    echo '<th>Lokasi</th>';
    echo '<th>Tanggal</th>';
    echo '<th>Status</th>';
    echo '<th>Urgensi</th>';
    echo '</tr>';
    echo '</thead><tbody>';
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($result_export)) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['nama']) . '</td>';
        echo '<td>' . htmlspecialchars($row['judul']) . '</td>';
        echo '<td>' . htmlspecialchars($row['kategori']) . '</td>';
        echo '<td>' . htmlspecialchars($row['lokasi'] ?? '-') . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
        echo '<td>' . ucfirst($row['status']) . '</td>';
        echo '<td>' . ucfirst($row['urgensi']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '<p style="margin-top:20px;"><strong>Total Data: ' . ($no-1) . ' pengaduan</strong></p>';
    echo '</body></html>';
    exit();
}

// ========== PROSES PRINT LAPORAN ==========
if (isset($_GET['print_report'])) {
    $search_print = isset($_GET['search']) ? $_GET['search'] : '';
    $status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    $where_print = "1=1";
    if (!empty($status_filter)) {
        $where_print .= " AND p.status = '$status_filter'";
    }
    if (!empty($search_print)) {
        $search_print = mysqli_real_escape_string($conn, $search_print);
        $where_print .= " AND (p.judul LIKE '%$search_print%' OR u.nama LIKE '%$search_print%')";
    }
    if (!empty($start_date)) {
        $where_print .= " AND DATE(p.tanggal) >= '$start_date'";
    }
    if (!empty($end_date)) {
        $where_print .= " AND DATE(p.tanggal) <= '$end_date'";
    }
    
    $query_print = "SELECT p.id, u.nama, p.judul, p.kategori, p.lokasi, p.deskripsi, p.tanggal, p.status, p.urgensi 
                    FROM pengaduan p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE $where_print 
                    ORDER BY p.tanggal DESC";
    $result_print = mysqli_query($conn, $query_print);
    
    $data_print = [];
    $total_data = 0;
    if ($result_print) {
        while ($row = mysqli_fetch_assoc($result_print)) {
            $data_print[] = $row;
        }
        $total_data = count($data_print);
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Laporan Pengaduan</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; color: #213C51; margin-bottom: 5px; }
            .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
            .info { margin-bottom: 20px; padding: 10px; background: #f5f5f5; border-radius: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #213C51; color: white; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .footer { margin-top: 30px; text-align: right; font-size: 12px; color: #666; }
            @media print {
                .no-print { display: none; }
                button { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #213C51; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                Tutup
            </button>
        </div>
        
        <h1>LAPORAN PENGADUAN WARGA</h1>
        <div class="subtitle">Sistem Informasi RT 05 Sukamaju</div>
        
        <div class="info">
            <strong>Periode:</strong> <?php echo !empty($start_date) ? date('d/m/Y', strtotime($start_date)) : 'Semua'; ?> - <?php echo !empty($end_date) ? date('d/m/Y', strtotime($end_date)) : 'Semua'; ?><br>
            <strong>Status:</strong> <?php echo !empty($status_filter) ? ucfirst($status_filter) : 'Semua'; ?><br>
            <strong>Tanggal Cetak:</strong> <?php echo date('d/m/Y H:i:s'); ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID</th>
                    <th>Warga</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Urgensi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_data > 0): ?>
                    <?php $no = 1; foreach ($data_print as $row): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                        <td><?php echo htmlspecialchars($row['kategori']); ?></td>
                        <td><?php echo htmlspecialchars($row['lokasi'] ?? '-'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                        <td>
                            <?php 
                            $status_text = '';
                            if ($row['status'] == 'baru') $status_text = 'Baru';
                            elseif ($row['status'] == 'diproses') $status_text = 'Diproses';
                            elseif ($row['status'] == 'selesai') $status_text = 'Selesai';
                            else $status_text = 'Ditolak';
                            echo $status_text;
                            ?>
                        </td>
                        <td><?php echo ucfirst($row['urgensi']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align: center;">Tidak ada数据</td?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Total Data: <?php echo $total_data; ?> pengaduan</p>
            <p>Dicetak oleh: <?php echo htmlspecialchars($user['nama']); ?></p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Ambil input pencarian dan filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// ========== QUERY TABEL AKTIF ==========
$where_aktif = "p.status IN ('baru', 'diproses')";
if (!empty($search)) {
    $where_aktif .= " AND (p.judul LIKE '%$search%' OR u.nama LIKE '%$search%')";
}
if (!empty($start_date)) {
    $where_aktif .= " AND DATE(p.tanggal) >= '$start_date'";
}
if (!empty($end_date)) {
    $where_aktif .= " AND DATE(p.tanggal) <= '$end_date'";
}
if (!empty($status_filter) && $status_filter != '') {
    $where_aktif .= " AND p.status = '$status_filter'";
}

$query_aktif = "SELECT p.*, u.nama 
                FROM pengaduan p 
                JOIN users u ON p.user_id = u.id 
                WHERE $where_aktif 
                ORDER BY FIELD(p.status, 'baru', 'diproses'), p.tanggal DESC";
$result_aktif = mysqli_query($conn, $query_aktif);

// ========== QUERY TABEL RIWAYAT ==========
$where_riwayat = "p.status IN ('selesai', 'ditolak')";
if (!empty($search)) {
    $where_riwayat .= " AND (p.judul LIKE '%$search%' OR u.nama LIKE '%$search%')";
}
if (!empty($start_date)) {
    $where_riwayat .= " AND DATE(p.tanggal) >= '$start_date'";
}
if (!empty($end_date)) {
    $where_riwayat .= " AND DATE(p.tanggal) <= '$end_date'";
}
if (!empty($status_filter) && $status_filter != '') {
    $where_riwayat .= " AND p.status = '$status_filter'";
}

$query_riwayat = "SELECT p.*, u.nama 
                  FROM pengaduan p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE $where_riwayat 
                  ORDER BY p.tanggal DESC LIMIT 10";
$result_riwayat = mysqli_query($conn, $query_riwayat);

$current_year = date('Y');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Kelola Pengaduan - Admin e-RT Digital</title>
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
            --transition-fast: 0.15s ease;
            --transition-normal: 0.25s ease;
            --transition-slow: 0.4s ease;
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
        }

        .sidebar .logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition-normal);
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
            transition: all var(--transition-normal);
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
            z-index: -1;
        }

        .sidebar .nav-menu a:hover::before {
            left: 0;
        }

        .sidebar .nav-menu a i {
            width: 20px;
            font-size: 16px;
            transition: transform var(--transition-fast);
        }

        .sidebar .nav-menu a:hover i {
            transform: translateX(3px);
        }

        .sidebar .nav-menu a:hover,
        .sidebar .nav-menu a.active {
            background: var(--secondary);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .user-profile {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            transition: var(--transition-normal);
        }

        .sidebar .user-profile:hover {
            background: rgba(255, 255, 255, 0.05);
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

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 24px 32px;
            width: calc(100% - 280px);
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
            animation: slideInLeft 0.4s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-800);
            transition: var(--transition-fast);
        }

        .page-header h1:hover {
            transform: translateX(5px);
            color: var(--primary);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1 1 160px;
            min-width: 130px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 12px;
            color: var(--gray-600);
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: white;
            color: var(--gray-700);
            font-size: 13px;
            transition: all 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(101, 148, 177, 0.2);
        }

        .btn-filter {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-filter:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .btn-reset {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-reset:hover {
            background: var(--gray-200);
        }

        /* Export Buttons */
        .export-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-200);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-excel {
            background: #10B981;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
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
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }

        /* Card */
        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
            animation: fadeInUp 0.5s ease-out;
            animation-fill-mode: both;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-header i {
            font-size: 20px;
            color: var(--secondary);
            transition: transform var(--transition-fast);
        }

        .card:hover .card-header i {
            transform: scale(1.1) rotate(5deg);
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .badge-count {
            background: var(--secondary);
            color: white;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
            transition: all var(--transition-fast);
        }

        .card:hover .badge-count {
            transform: scale(1.05);
            background: var(--primary);
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 12px;
            background: var(--gray-50);
            font-weight: 600;
            font-size: 13px;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-200);
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
            vertical-align: middle;
            transition: background var(--transition-fast);
        }

        tr {
            transition: all var(--transition-fast);
        }

        tr:hover td {
            background: var(--gray-50);
            transform: scale(1.002);
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            transition: all var(--transition-fast);
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .status-baru {
            background: #FEF3C7;
            color: #D97706;
        }
        .status-diproses {
            background: #DBEAFE;
            color: #2563EB;
        }
        .status-selesai {
            background: #D1FAE5;
            color: #059669;
        }
        .status-ditolak {
            background: #FEE2E2;
            color: #DC2626;
        }

        /* Select Status */
        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            background: white;
            font-size: 13px;
            cursor: pointer;
            outline: none;
            transition: all var(--transition-fast);
        }

        .status-select:hover {
            border-color: var(--secondary);
            transform: translateY(-1px);
        }

        .status-select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(101, 148, 177, 0.2);
        }

        /* Buttons */
        .btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn:active {
            transform: scale(0.95);
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--gray-500);
            transition: all var(--transition-normal);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
            transition: transform var(--transition-normal);
        }

        .empty-state:hover i {
            transform: scale(1.1);
            opacity: 0.8;
        }

        /* Modal Detail */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: var(--shadow-lg);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-500);
            transition: all var(--transition-fast);
        }

        .close-modal:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }

        .modal-body p {
            margin-bottom: 12px;
            line-height: 1.6;
            color: var(--gray-600);
            animation: fadeInUp 0.3s ease;
        }

        .modal-body strong {
            color: var(--gray-800);
        }

        /* Foto Preview */
        .foto-preview {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-200);
        }
        .foto-preview label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 10px;
            display: block;
        }
        .foto-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            cursor: pointer;
            transition: all 0.3s;
        }
        .foto-preview img:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }
        .no-foto {
            background: var(--gray-100);
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            color: var(--gray-500);
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 20px 20px;
            margin-top: 30px;
            text-align: center;
            color: var(--gray-500);
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
            transition: all var(--transition-fast);
        }

        .menu-toggle:hover {
            background: var(--secondary);
            transform: scale(1.05);
        }

        .menu-toggle:active {
            transform: scale(0.95);
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
            transition: opacity var(--transition-normal);
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
                align-items: flex-start;
            }
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .export-section {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
            .table-responsive {
                overflow-x: auto;
            }
            th, td {
                white-space: nowrap;
            }
            .modal-content {
                width: 95%;
                padding: 16px;
            }
            .foto-preview img {
                max-height: 200px;
            }
        }

        @media (max-width: 480px) {
            .card {
                padding: 16px;
            }
            .btn {
                padding: 4px 10px;
                font-size: 11px;
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
        <a href="pengaduan.php" class="active"><i class="fas fa-comment-medical"></i> Pengaduan</a>
        <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Layanan Surat</a>
        <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
        <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
        <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
        <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
        <a href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a>
    </div>

    <div class="user-profile">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
            <div class="info">
                <h4><?php echo htmlspecialchars($user['nama']); ?></h4>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-comment-medical" style="margin-right: 12px; color: var(--secondary);"></i> Kelola Pengaduan</h1>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form" id="filterForm">
            <div class="filter-group">
                <label>Cari</label>
                <input type="text" name="search" placeholder="Warga / Judul" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Dari Tanggal</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="filter-group">
                <label>Sampai Tanggal</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status_filter">
                    <option value="">Semua Status</option>
                    <option value="baru" <?php if ($status_filter == 'baru') echo 'selected'; ?>>Baru</option>
                    <option value="diproses" <?php if ($status_filter == 'diproses') echo 'selected'; ?>>Diproses</option>
                    <option value="selesai" <?php if ($status_filter == 'selesai') echo 'selected'; ?>>Selesai</option>
                    <option value="ditolak" <?php if ($status_filter == 'ditolak') echo 'selected'; ?>>Ditolak</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Terapkan</button>
            </div>
            <div class="filter-group">
                <a href="pengaduan.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
        
        <!-- Export Buttons -->
        <div class="export-section">
            <button type="button" class="btn-excel" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Ekspor ke Excel
            </button>
            <button type="button" class="btn-print" onclick="printReport()">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Card: Laporan Aktif -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-spinner fa-pulse"></i>
            <h2>Laporan Dalam Antrean & Proses</h2>
            <span class="badge-count"><?php echo mysqli_num_rows($result_aktif); ?></span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Warga</th>
                        <th>Judul Laporan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Update Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_aktif) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_aktif)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo strtoupper($row['status']); ?></span></td>
                            <td>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="baru" <?php echo $row['status'] == 'baru' ? 'selected' : ''; ?>>Baru</option>
                                        <option value="diproses" <?php echo $row['status'] == 'diproses' ? 'selected' : ''; ?>>Proses</option>
                                        <option value="selesai">Selesaikan</option>
                                        <option value="ditolak">Tolak</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-primary" onclick="showDetail(<?php echo $row['id']; ?>, '<?php echo addslashes($row['judul']); ?>', '<?php echo addslashes($row['deskripsi']); ?>', '<?php echo addslashes($row['lokasi']); ?>', '<?php echo $row['tanggal']; ?>', '<?php echo $row['status']; ?>', '<?php echo addslashes($row['nama']); ?>', '<?php echo addslashes($row['foto']); ?>')">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <p>Tidak ada laporan aktif saat ini.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Card: Riwayat Selesai (TANPA TOMBOL HAPUS) -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-check-circle"></i>
            <h2>Riwayat Laporan Selesai / Ditolak</h2>
            <span class="badge-count"><?php echo mysqli_num_rows($result_riwayat); ?></span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Warga</th>
                        <th>Judul Laporan</th>
                        <th>Status Akhir</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_riwayat)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                            <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo strtoupper($row['status']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td>
                                <button class="btn btn-primary" onclick="showDetail(<?php echo $row['id']; ?>, '<?php echo addslashes($row['judul']); ?>', '<?php echo addslashes($row['deskripsi']); ?>', '<?php echo addslashes($row['lokasi']); ?>', '<?php echo $row['tanggal']; ?>', '<?php echo $row['status']; ?>', '<?php echo addslashes($row['nama']); ?>', '<?php echo addslashes($row['foto']); ?>')">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                             </td
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-archive"></i>
                                    <p>Belum ada riwayat laporan.</p>
                                </div>
                            </td
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Detail Pengaduan</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Dinamis -->
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

    // Modal Functions with Foto
    function showDetail(id, judul, deskripsi, lokasi, tanggal, status, nama, foto) {
        const modalBody = document.getElementById('modalBody');
        let statusText = '';
        
        switch(status) {
            case 'baru': statusText = 'Baru'; break;
            case 'diproses': statusText = 'Diproses'; break;
            case 'selesai': statusText = 'Selesai'; break;
            case 'ditolak': statusText = 'Ditolak'; break;
            default: statusText = status;
        }
        
        // Tampilkan foto jika ada
        let fotoHTML = '';
        if (foto && foto !== '' && foto !== 'null' && foto !== 'NULL') {
            let fotoPath = '../uploads/pengaduan/' + foto;
            fotoHTML = `
                <div class="foto-preview">
                    <label><i class="fas fa-image"></i> Foto Bukti:</label><br>
                    <img src="${fotoPath}" alt="Foto Bukti" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'no-foto\'><i class=\'fas fa-camera-slash\'></i><p>Foto tidak ditemukan</p><small>File: ${foto}</small></div>';">
                </div>
            `;
        } else {
            fotoHTML = `<div class="foto-preview"><div class="no-foto"><i class="fas fa-camera-slash"></i><p>Tidak ada foto bukti</p></div></div>`;
        }
        
        modalBody.innerHTML = `
            <p><strong><i class="fas fa-user"></i> Pengadu:</strong> ${escapeHtml(nama)}</p>
            <p><strong><i class="fas fa-heading"></i> Judul:</strong> ${escapeHtml(judul)}</p>
            <p><strong><i class="fas fa-align-left"></i> Deskripsi:</strong><br>${escapeHtml(deskripsi)}</p>
            <p><strong><i class="fas fa-map-marker-alt"></i> Lokasi:</strong> ${escapeHtml(lokasi) || '-'}</p>
            <p><strong><i class="fas fa-calendar"></i> Tanggal:</strong> ${new Date(tanggal).toLocaleDateString('id-ID')}</p>
            <p><strong><i class="fas fa-tag"></i> Status:</strong> <span class="status-badge status-${status}">${statusText}</span></p>
            ${fotoHTML}
        `;
        document.getElementById('detailModal').classList.add('active');
    }
    
    function closeModal() {
        document.getElementById('detailModal').classList.remove('active');
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
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('detailModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

</body>
</html>
<?php
if (isset($result_aktif) && is_object($result_aktif)) mysqli_free_result($result_aktif);
if (isset($result_riwayat) && is_object($result_riwayat)) mysqli_free_result($result_riwayat);
mysqli_close($conn);
?>