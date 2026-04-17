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

// ========== CEK DAN BUAT TABEL JIKA BELUM ADA ==========
// Tabel kartu_keluarga
$check_kk = mysqli_query($conn, "SHOW TABLES LIKE 'kartu_keluarga'");
if (mysqli_num_rows($check_kk) == 0) {
    $sql_kk = "CREATE TABLE kartu_keluarga (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        no_kk VARCHAR(20) NOT NULL UNIQUE,
        alamat TEXT NOT NULL,
        rt_rw VARCHAR(15) NOT NULL,
        desa_kelurahan VARCHAR(100) NOT NULL,
        kecamatan VARCHAR(100) NOT NULL,
        kabupaten VARCHAR(100) NOT NULL,
        provinsi VARCHAR(100) NOT NULL,
        kode_pos VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql_kk);
} else {
    $check_user_id = mysqli_query($conn, "SHOW COLUMNS FROM kartu_keluarga LIKE 'user_id'");
    if (mysqli_num_rows($check_user_id) == 0) {
        mysqli_query($conn, "ALTER TABLE kartu_keluarga ADD user_id INT NOT NULL AFTER id");
        mysqli_query($conn, "ALTER TABLE kartu_keluarga ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    }
}

// Tabel anggota_keluarga
$check_anggota = mysqli_query($conn, "SHOW TABLES LIKE 'anggota_keluarga'");
if (mysqli_num_rows($check_anggota) == 0) {
    $sql_anggota = "CREATE TABLE anggota_keluarga (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kk_id INT NOT NULL,
        nik VARCHAR(20) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        tempat_lahir VARCHAR(50) NOT NULL,
        tanggal_lahir DATE NOT NULL,
        jenis_kelamin ENUM('L','P') NOT NULL,
        agama VARCHAR(20) NOT NULL,
        pendidikan VARCHAR(50) NOT NULL,
        pekerjaan VARCHAR(50) NOT NULL,
        status_perkawinan VARCHAR(20) NOT NULL,
        status_keluarga VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (kk_id) REFERENCES kartu_keluarga(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql_anggota);
} else {
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM anggota_keluarga LIKE 'kk_id'");
    if (mysqli_num_rows($check_column) == 0) {
        mysqli_query($conn, "ALTER TABLE anggota_keluarga ADD kk_id INT NOT NULL AFTER id");
        mysqli_query($conn, "ALTER TABLE anggota_keluarga ADD FOREIGN KEY (kk_id) REFERENCES kartu_keluarga(id) ON DELETE CASCADE");
    }
}

// Ambil daftar warga untuk dropdown
$query_warga = "SELECT id, nama, username FROM users WHERE role = 'warga' ORDER BY nama";
$result_warga = mysqli_query($conn, $query_warga);
$warga_list = [];
while ($row = mysqli_fetch_assoc($result_warga)) {
    $warga_list[] = $row;
}

// Inisialisasi pesan
$message = '';
$error = '';

// ========== PROSES TAMBAH / EDIT KK ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_kk') {
        $user_id_kk = (int)$_POST['user_id'];
        $no_kk = mysqli_real_escape_string($conn, $_POST['no_kk']);
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
        $rt_rw = mysqli_real_escape_string($conn, $_POST['rt_rw']);
        $desa_kelurahan = mysqli_real_escape_string($conn, $_POST['desa_kelurahan']);
        $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
        $kabupaten = mysqli_real_escape_string($conn, $_POST['kabupaten']);
        $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
        $kode_pos = mysqli_real_escape_string($conn, $_POST['kode_pos']);
        $kk_id = isset($_POST['kk_id']) ? (int)$_POST['kk_id'] : 0;

        if ($user_id_kk == 0 || empty($no_kk) || empty($alamat) || empty($rt_rw) || empty($desa_kelurahan) || empty($kecamatan) || empty($kabupaten) || empty($provinsi) || empty($kode_pos)) {
            $error = "Semua field KK harus diisi, termasuk pilih kepala keluarga.";
        } else {
            mysqli_begin_transaction($conn);
            try {
                if ($kk_id > 0) {
                    $query_kk = "UPDATE kartu_keluarga SET user_id='$user_id_kk', no_kk='$no_kk', alamat='$alamat', rt_rw='$rt_rw', desa_kelurahan='$desa_kelurahan', kecamatan='$kecamatan', kabupaten='$kabupaten', provinsi='$provinsi', kode_pos='$kode_pos' WHERE id=$kk_id";
                    mysqli_query($conn, $query_kk);
                } else {
                    $query_kk = "INSERT INTO kartu_keluarga (user_id, no_kk, alamat, rt_rw, desa_kelurahan, kecamatan, kabupaten, provinsi, kode_pos) VALUES ('$user_id_kk', '$no_kk', '$alamat', '$rt_rw', '$desa_kelurahan', '$kecamatan', '$kabupaten', '$provinsi', '$kode_pos')";
                    mysqli_query($conn, $query_kk);
                    $kk_id = mysqli_insert_id($conn);
                }

                if (isset($_POST['nik']) && is_array($_POST['nik'])) {
                    if ($kk_id > 0 && isset($_POST['kk_id']) && $_POST['kk_id'] > 0) {
                        $delete = "DELETE FROM anggota_keluarga WHERE kk_id = $kk_id";
                        mysqli_query($conn, $delete);
                    }

                    for ($i = 0; $i < count($_POST['nik']); $i++) {
                        $nik = mysqli_real_escape_string($conn, $_POST['nik'][$i]);
                        $nama = mysqli_real_escape_string($conn, $_POST['nama'][$i]);
                        $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir'][$i]);
                        $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir'][$i]);
                        $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'][$i]);
                        $agama = mysqli_real_escape_string($conn, $_POST['agama'][$i]);
                        $pendidikan = mysqli_real_escape_string($conn, $_POST['pendidikan'][$i]);
                        $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan'][$i]);
                        $status_perkawinan = mysqli_real_escape_string($conn, $_POST['status_perkawinan'][$i]);
                        $status_keluarga = mysqli_real_escape_string($conn, $_POST['status_keluarga'][$i]);

                        if (!empty($nik) && !empty($nama)) {
                            $insert = "INSERT INTO anggota_keluarga (kk_id, nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, pendidikan, pekerjaan, status_perkawinan, status_keluarga) 
                                       VALUES ('$kk_id', '$nik', '$nama', '$tempat_lahir', '$tanggal_lahir', '$jenis_kelamin', '$agama', '$pendidikan', '$pekerjaan', '$status_perkawinan', '$status_keluarga')";
                            mysqli_query($conn, $insert);
                        }
                    }
                }

                mysqli_commit($conn);
                $message = $kk_id > 0 ? "Data KK berhasil diperbarui." : "Data KK berhasil ditambahkan.";
                // Redirect untuk menghindari resubmit
                header("Location: kk.php?success=" . urlencode($message));
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Gagal menyimpan data: " . mysqli_error($conn);
            }
        }
    }
}

// Tangkap pesan dari redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

// ========== PROSES HAPUS KK ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $query = "DELETE FROM kartu_keluarga WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $message = "Data KK berhasil dihapus.";
    } else {
        $error = "Gagal menghapus data KK: " . mysqli_error($conn);
    }
}

// ========== AMBIL DATA UNTUK EDIT ==========
$edit_data = null;
$edit_anggota = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $query_kk = "SELECT * FROM kartu_keluarga WHERE id = $id";
    $result_kk = mysqli_query($conn, $query_kk);
    if ($result_kk && mysqli_num_rows($result_kk) > 0) {
        $edit_data = mysqli_fetch_assoc($result_kk);
        $query_anggota = "SELECT * FROM anggota_keluarga WHERE kk_id = $id ORDER BY id";
        $result_anggota = mysqli_query($conn, $query_anggota);
        if ($result_anggota) {
            while ($row = mysqli_fetch_assoc($result_anggota)) {
                $edit_anggota[] = $row;
            }
        }
    }
}

// ========== AMBIL SEMUA KK UNTUK DITAMPILKAN ==========
$query_all = "SELECT k.*, u.nama as nama_kepala,
              (SELECT COUNT(*) FROM anggota_keluarga WHERE kk_id = k.id) as jumlah_anggota 
              FROM kartu_keluarga k 
              LEFT JOIN users u ON k.user_id = u.id
              ORDER BY k.created_at DESC";
$result_all = mysqli_query($conn, $query_all);
if (!$result_all) {
    $error = "Error mengambil data: " . mysqli_error($conn);
    $result_all = false;
}

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Kelola Kartu Keluarga - Admin e-RT Digital</title>
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

        .btn-primary {
            background: var(--secondary);
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
            text-decoration: none;
            margin-bottom: 20px;
            animation: scaleIn 0.4s ease-out;
        }

        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.1s;
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

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th {
            text-align: left;
            padding: 14px 12px;
            font-weight: 600;
            font-size: 13px;
            color: var(--gray-600);
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
            color: var(--gray-700);
            vertical-align: middle;
            transition: all 0.3s ease;
        }

        tr {
            transition: all 0.3s ease;
        }

        tr:hover td {
            background: linear-gradient(90deg, rgba(101, 148, 177, 0.05) 0%, rgba(101, 148, 177, 0.1) 100%);
            transform: scale(1.002);
        }

        .badge {
            background: #E5E7EB;
            color: #4B5563;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            transition: all 0.3s ease;
        }

        tr:hover .badge {
            transform: scale(1.02);
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 5px;
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

        .btn-delete {
            background: #FEE2E2;
            color: #DC2626;
        }

        .btn-delete:hover {
            background: #FECACA;
        }

        .btn-view {
            background: #E5E7EB;
            color: #4B5563;
        }

        .btn-view:hover {
            background: #D1D5DB;
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
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 20px;
            animation: fadeInUp 0.3s ease-out;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            padding: 28px;
            animation: scaleIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-500);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: var(--gray-100);
            color: var(--gray-800);
            transform: rotate(90deg);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
            color: var(--gray-700);
        }

        .form-control, select.form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: white;
            color: var(--gray-700);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, select.form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(101, 148, 177, 0.2);
            transform: translateY(-2px);
        }

        select.form-control option {
            background: white;
            color: var(--gray-700);
        }

        .section-title {
            color: var(--gray-800);
            font-size: 16px;
            font-weight: 700;
            margin: 20px 0 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--secondary);
        }

        .anggota-row {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            position: relative;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            animation: scaleIn 0.3s ease-out;
        }

        .anggota-row:hover {
            box-shadow: var(--shadow-sm);
            border-color: var(--secondary);
        }

        .anggota-row .form-grid {
            margin-bottom: 0;
        }

        .remove-anggota {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #FEE2E2;
            color: #DC2626;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .remove-anggota:hover {
            background: #FECACA;
            transform: scale(1.1);
        }

        .btn-add-anggota {
            background: #DBEAFE;
            color: #2563EB;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-add-anggota:hover {
            background: #BFDBFE;
            transform: translateY(-2px);
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

        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            justify-content: flex-end;
        }

        /* Detail Modal */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .detail-table td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
        }

        .detail-table td:first-child {
            width: 160px;
            font-weight: 600;
            color: var(--gray-600);
        }

        .anggota-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .anggota-table th {
            background: var(--gray-50);
            padding: 10px 8px;
            font-size: 12px;
        }

        .anggota-table td {
            padding: 10px 8px;
            font-size: 13px;
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
            .form-grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                padding: 20px;
            }
            .anggota-row .form-grid {
                grid-template-columns: 1fr;
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
            <a href="kk.php" class="active"><i class="fas fa-address-card"></i> Data KK</a>
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
                    <i class="fas fa-address-card"></i>
                    <h1>Kelola Kartu Keluarga</h1>
                </div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Tombol Tambah -->
            <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Tambah Data KK</button>

            <!-- Tabel Daftar KK -->
            <div class="table-container">
                <h2><i class="fas fa-list"></i> Daftar Kartu Keluarga</h2>
                <?php if ($result_all && mysqli_num_rows($result_all) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. KK</th>
                                    <th>Kepala Keluarga</th>
                                    <th>Alamat</th>
                                    <th>RT/RW</th>
                                    <th>Jumlah Anggota</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result_all)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['no_kk']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nama_kepala'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['rt_rw']); ?></td>
                                    <td><span class="badge"><?php echo $row['jumlah_anggota']; ?> orang</span></td>
                                    <td class="actions">
                                        <a href="?edit=<?php echo $row['id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?hapus=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus KK ini? Semua anggota keluarga akan ikut terhapus.')"><i class="fas fa-trash"></i> Hapus</a>
                                        <a href="#" onclick="viewKK(<?php echo $row['id']; ?>)" class="btn-action btn-view"><i class="fas fa-eye"></i> Detail</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-address-card"></i>
                        <p>Belum ada data Kartu Keluarga. Silakan tambahkan data baru.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit KK -->
    <div id="kkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Kartu Keluarga</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="" id="formKK">
                <input type="hidden" name="action" value="save_kk">
                <input type="hidden" name="kk_id" id="kk_id" value="">

                <!-- Data KK -->
                <div class="section-title"><i class="fas fa-home"></i> Data Kartu Keluarga</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>No. KK *</label>
                        <input type="text" name="no_kk" id="no_kk" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kepala Keluarga (User) *</label>
                        <select name="user_id" id="user_id" class="form-control" required>
                            <option value="">-- Pilih Warga --</option>
                            <?php foreach ($warga_list as $w): ?>
                                <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['nama']) . ' (' . htmlspecialchars($w['username']) . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>RT/RW *</label>
                        <input type="text" name="rt_rw" id="rt_rw" class="form-control" placeholder="001/003" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat *</label>
                        <input type="text" name="alamat" id="alamat" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Desa/Kelurahan *</label>
                        <input type="text" name="desa_kelurahan" id="desa_kelurahan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kecamatan *</label>
                        <input type="text" name="kecamatan" id="kecamatan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kabupaten *</label>
                        <input type="text" name="kabupaten" id="kabupaten" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Provinsi *</label>
                        <input type="text" name="provinsi" id="provinsi" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Pos *</label>
                        <input type="text" name="kode_pos" id="kode_pos" class="form-control" required>
                    </div>
                </div>

                <!-- Data Anggota Keluarga -->
                <div class="section-title"><i class="fas fa-users"></i> Anggota Keluarga</div>
                <div id="anggota-container"></div>
                <button type="button" class="btn-add-anggota" onclick="tambahAnggota()"><i class="fas fa-plus"></i> Tambah Anggota</button>

                <div class="modal-buttons">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail KK -->
    <div id="detailModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Detail Kartu Keluarga</h2>
                <button class="close-modal" onclick="closeDetailModal()">&times;</button>
            </div>
            <div id="detailContent" style="color: var(--gray-700);">
                <!-- Akan diisi dengan AJAX -->
            </div>
        </div>
    </div>

    <script>
        let anggotaCount = 0;

        <?php if ($edit_data): ?>
        var editData = <?php echo json_encode($edit_data); ?>;
        var editAnggota = <?php echo json_encode($edit_anggota); ?>;
        <?php else: ?>
        var editData = null;
        var editAnggota = [];
        <?php endif; ?>

        function openModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Kartu Keluarga';
            document.getElementById('kk_id').value = '';
            document.getElementById('no_kk').value = '';
            document.getElementById('user_id').value = '';
            document.getElementById('rt_rw').value = '';
            document.getElementById('alamat').value = '';
            document.getElementById('desa_kelurahan').value = '';
            document.getElementById('kecamatan').value = '';
            document.getElementById('kabupaten').value = '';
            document.getElementById('provinsi').value = '';
            document.getElementById('kode_pos').value = '';
            document.getElementById('anggota-container').innerHTML = '';
            anggotaCount = 0;
            tambahAnggota();
            document.getElementById('kkModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('kkModal').style.display = 'none';
        }

        function editKK(id) {
            window.location.href = '?edit=' + id;
        }

        <?php if ($edit_data): ?>
        window.onload = function() {
            if (editData) {
                document.getElementById('modalTitle').innerText = 'Edit Kartu Keluarga';
                document.getElementById('kk_id').value = editData.id;
                document.getElementById('no_kk').value = editData.no_kk;
                document.getElementById('user_id').value = editData.user_id;
                document.getElementById('rt_rw').value = editData.rt_rw;
                document.getElementById('alamat').value = editData.alamat;
                document.getElementById('desa_kelurahan').value = editData.desa_kelurahan;
                document.getElementById('kecamatan').value = editData.kecamatan;
                document.getElementById('kabupaten').value = editData.kabupaten;
                document.getElementById('provinsi').value = editData.provinsi;
                document.getElementById('kode_pos').value = editData.kode_pos;
                document.getElementById('anggota-container').innerHTML = '';
                anggotaCount = 0;
                if (editAnggota.length > 0) {
                    editAnggota.forEach(function(anggota) {
                        tambahAnggota(anggota);
                    });
                } else {
                    tambahAnggota();
                }
                document.getElementById('kkModal').style.display = 'flex';
            }
        };
        <?php endif; ?>

        function tambahAnggota(data = null) {
            const container = document.getElementById('anggota-container');
            const index = anggotaCount;
            const row = document.createElement('div');
            row.className = 'anggota-row';
            row.id = 'anggota-' + index;

            let html = `
                <div class="remove-anggota" onclick="hapusAnggota(${index})">
                    <i class="fas fa-times"></i>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>NIK *</label>
                        <input type="text" name="nik[${index}]" class="form-control" value="${data ? data.nik : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Nama *</label>
                        <input type="text" name="nama[${index}]" class="form-control" value="${data ? data.nama : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Tempat Lahir *</label>
                        <input type="text" name="tempat_lahir[${index}]" class="form-control" value="${data ? data.tempat_lahir : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir[${index}]" class="form-control" value="${data ? data.tanggal_lahir : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin *</label>
                        <select name="jenis_kelamin[${index}]" class="form-control" required>
                            <option value="L" ${data && data.jenis_kelamin == 'L' ? 'selected' : ''}>Laki-laki</option>
                            <option value="P" ${data && data.jenis_kelamin == 'P' ? 'selected' : ''}>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Agama *</label>
                        <input type="text" name="agama[${index}]" class="form-control" value="${data ? data.agama : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Pendidikan *</label>
                        <input type="text" name="pendidikan[${index}]" class="form-control" value="${data ? data.pendidikan : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Pekerjaan *</label>
                        <input type="text" name="pekerjaan[${index}]" class="form-control" value="${data ? data.pekerjaan : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Status Perkawinan *</label>
                        <input type="text" name="status_perkawinan[${index}]" class="form-control" value="${data ? data.status_perkawinan : ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Status Keluarga *</label>
                        <input type="text" name="status_keluarga[${index}]" class="form-control" value="${data ? data.status_keluarga : ''}" required>
                    </div>
                </div>
            `;

            row.innerHTML = html;
            container.appendChild(row);
            anggotaCount++;
        }

        function hapusAnggota(index) {
            const row = document.getElementById('anggota-' + index);
            if (row) row.remove();
        }

        function viewKK(id) {
            fetch('get_kk_detail.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <div class="section-title"><i class="fas fa-home"></i> Data Kartu Keluarga</div>
                            <table class="detail-table">
                                <tr><td>No. KK</td><td><strong>${data.kk.no_kk}</strong></td></tr>
                                <tr><td>Alamat</td><td>${data.kk.alamat}</td></tr>
                                <tr><td>RT/RW</td><td>${data.kk.rt_rw}</td></tr>
                                <tr><td>Desa/Kelurahan</td><td>${data.kk.desa_kelurahan}</td></tr>
                                <tr><td>Kecamatan</td><td>${data.kk.kecamatan}</td></tr>
                                <tr><td>Kabupaten</td><td>${data.kk.kabupaten}</td></tr>
                                <tr><td>Provinsi</td><td>${data.kk.provinsi}</td></tr>
                                <tr><td>Kode Pos</td><td>${data.kk.kode_pos}</td></tr>
                            60
                            <div class="section-title"><i class="fas fa-users"></i> Anggota Keluarga</div>
                        `;
                        if (data.anggota.length > 0) {
                            html += `
                                <table class="anggota-table">
                                    <thead>
                                        <tr><th>NIK</th><th>Nama</th><th>Tempat Lahir</th><th>Tanggal Lahir</th><th>JK</th><th>Status Keluarga</th></tr>
                                    </thead>
                                    <tbody>
                            `;
                            data.anggota.forEach(a => {
                                html += `<tr>
                                    <td>${a.nik}</td>
                                    <td><strong>${a.nama}</strong></td>
                                    <td>${a.tempat_lahir}</td>
                                    <td>${a.tanggal_lahir}</td>
                                    <td>${a.jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                                    <td>${a.status_keluarga}</td>
                                </tr>`;
                            });
                            html += `</tbody></table>`;
                        } else {
                            html += '<p style="color: var(--gray-500);">Tidak ada anggota keluarga.</p>';
                        }
                        document.getElementById('detailContent').innerHTML = html;
                        document.getElementById('detailModal').style.display = 'flex';
                    } else {
                        alert('Gagal mengambil data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengambil data.');
                });
        }

        function closeDetailModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

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