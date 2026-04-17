<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login - HARUS LOGIN TERLEBIH DAHULU
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Ambil data user dari database
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

// Deklarasi variabel
$success = "";
$error = "";

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $urgensi = mysqli_real_escape_string($conn, $_POST['urgensi'] ?? 'sedang');
    
    // Validasi - LOKASI SEKARANG WAJIB
    if (empty($judul) || empty($kategori) || empty($lokasi) || empty($deskripsi)) {
        $error = "Harap isi semua field yang wajib diisi! (Judul, Kategori, Lokasi, dan Deskripsi)";
    } elseif (strlen($deskripsi) < 20) {
        $error = "Deskripsi terlalu pendek. Minimal 20 karakter!";
    } elseif (strlen($lokasi) < 5) {
        $error = "Lokasi terlalu pendek. Minimal 5 karakter! Contoh: RT 05, Jalan Merdeka No. 15";
    } else {
        $foto_path = null;
        
        // Proses upload foto jika ada
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $maxFileSize = 20 * 1024 * 1024; // 20MB
            
            if ($_FILES['foto']['size'] > $maxFileSize) {
                $error = "Ukuran file foto melebihi batas maksimal 20MB!";
            } else {
                // Validasi tipe file
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                $file_type = mime_content_type($_FILES['foto']['tmp_name']);
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Format file tidak didukung. Gunakan format JPG, PNG, atau GIF.";
                } else {
                    // Buat nama file unik
                    $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'foto_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_dir = '../uploads/pengaduan/';
                    
                    // Buat folder jika belum ada
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $target_file = $upload_dir . $new_filename;
                    
                    // Upload file
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                        $foto_path = 'uploads/pengaduan/' . $new_filename;
                    } else {
                        $error = "Gagal mengupload file foto.";
                    }
                }
            }
        } elseif ($_FILES['foto']['error'] !== 4 && $_FILES['foto']['error'] !== 0) {
            // Error selain "no file uploaded"
            $error = "Terjadi kesalahan saat mengupload file: Error code " . $_FILES['foto']['error'];
        }
        
        // Jika tidak ada error, simpan ke database
        if (empty($error)) {
            // Generate nomor tiket unik
            $no_tiket = 'TKT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Query untuk menyimpan pengaduan
            $query = "INSERT INTO pengaduan (user_id, no_tiket, judul, kategori, lokasi, deskripsi, foto, urgensi, status, tanggal) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'baru', NOW())";
            
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $no_tiket, $judul, $kategori, $lokasi, $deskripsi, $foto_path, $urgensi);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Pengaduan berhasil dikirim! Nomor tiket: " . $no_tiket;
                    
                    // Reset form values
                    $_POST = [];
                    
                    // Redirect ke riwayat setelah 3 detik (opsional)
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "riwayat.php?tab=pengaduan";
                        }, 3000);
                    </script>';
                } else {
                    $error = "Gagal menyimpan pengaduan ke database: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Error preparing query: " . mysqli_error($conn);
            }
        }
    }
}

// Pastikan tabel pengaduan ada di database dengan pengecekan yang aman
$table_exists = false;
$check_table = "SHOW TABLES LIKE 'pengaduan'";
$table_result = mysqli_query($conn, $check_table);
if ($table_result) {
    $table_exists = mysqli_num_rows($table_result) > 0;
} else {
    error_log("Error checking table existence: " . mysqli_error($conn));
}

if (!$table_exists) {
    // Buat tabel jika belum ada
    $create_table = "CREATE TABLE pengaduan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        no_tiket VARCHAR(20) NOT NULL UNIQUE,
        judul VARCHAR(255) NOT NULL,
        kategori VARCHAR(50) NOT NULL,
        lokasi VARCHAR(255) NOT NULL,
        deskripsi TEXT NOT NULL,
        foto VARCHAR(255),
        urgensi ENUM('rendah', 'sedang', 'tinggi') DEFAULT 'sedang',
        status ENUM('baru', 'diproses', 'selesai', 'ditolak') DEFAULT 'baru',
        tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        $error = "Error creating table: " . mysqli_error($conn);
    } else {
        $table_exists = true;
    }
}

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Buat Pengaduan - e-RT Digital</title>
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
        }

        /* Overlay ringan untuk efek */
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
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.92), rgba(59, 73, 83, 0.88));
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
                padding: 8px 14px;
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

        /* ===== KONTAINER UTAMA - RESPONSIVE ===== */
        .container {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 20px;
        }

        /* ===== HEADER HALAMAN - RESPONSIVE ===== */
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            background: rgba(235, 244, 221, 0.15);
            border: 1px solid rgba(235, 244, 221, 0.3);
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
            background: #90AB8B;
            border-color: #90AB8B;
            transform: translateY(-2px);
        }

        /* ===== ALERT ===== */
        .alert {
            padding: 14px 18px;
            border-radius: 30px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(235, 244, 221, 0.3);
            font-size: 0.9rem;
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

        .alert-info {
            background: #5A7863;
            color: white;
        }

        /* ===== FORM CONTAINER - RESPONSIVE ===== */
        .form-container {
            background: #EBF4DD;
            border-radius: 32px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.1s;
            animation-fill-mode: both;
        }

        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 2px solid #90AB8B;
            flex-wrap: wrap;
        }

        .form-icon {
            width: 60px;
            height: 60px;
            background: #90AB8B;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #EBF4DD;
            transition: transform 0.3s ease;
        }

        .form-container:hover .form-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .form-title h2 {
            font-size: 24px;
            font-weight: 700;
            color: #3B4953;
        }

        .form-title p {
            color: #5A7863;
            font-size: 0.9rem;
        }

        /* ===== FORM GRID - RESPONSIVE ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #3B4953;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .required {
            color: #D98A6C;
            font-size: 12px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #90AB8B;
            border-radius: 18px;
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

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%233B4953' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 14px;
            padding-right: 40px;
        }

        /* ===== FILE UPLOAD ===== */
        .file-upload {
            position: relative;
            border: 2px dashed #90AB8B;
            border-radius: 18px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
            background: rgba(144, 171, 139, 0.1);
            cursor: pointer;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.2s;
            animation-fill-mode: both;
        }

        .file-upload:hover {
            border-color: #5A7863;
            background: rgba(144, 171, 139, 0.2);
            transform: translateY(-2px);
        }

        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 40px;
            color: #5A7863;
            margin-bottom: 12px;
            transition: transform 0.3s ease;
        }

        .file-upload:hover .upload-icon {
            transform: scale(1.1);
        }

        .upload-text h4 {
            color: #3B4953;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .upload-text p {
            color: #5A7863;
            font-size: 0.8rem;
        }

        .file-preview {
            margin-top: 12px;
            display: none;
        }

        .file-preview img {
            max-width: 180px;
            border-radius: 12px;
            border: 2px solid #90AB8B;
        }

        /* ===== URGENSI RADIO - RESPONSIVE ===== */
        .urgensi-options {
            display: flex;
            gap: 12px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .urgensi-option {
            flex: 1;
            min-width: 100px;
            text-align: center;
            animation: scaleIn 0.4s ease-out forwards;
            opacity: 0;
        }

        .urgensi-option:nth-child(1) { animation-delay: 0.25s; }
        .urgensi-option:nth-child(2) { animation-delay: 0.3s; }
        .urgensi-option:nth-child(3) { animation-delay: 0.35s; }

        .urgensi-option input {
            display: none;
        }

        .urgensi-label {
            display: block;
            padding: 12px;
            border: 2px solid #90AB8B;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            color: #3B4953;
            font-size: 0.85rem;
        }

        .urgensi-label:hover {
            background: rgba(144, 171, 139, 0.1);
            transform: translateY(-2px);
        }

        .urgensi-option input:checked + .urgensi-label {
            border-color: #5A7863;
            background: rgba(90, 120, 99, 0.15);
            box-shadow: 0 5px 15px rgba(90, 120, 99, 0.2);
        }

        .urgensi-icon {
            font-size: 20px;
            margin-bottom: 6px;
        }

        .urgensi-rendah .urgensi-icon { color: #7DA06E; }
        .urgensi-sedang .urgensi-icon { color: #E0B87A; }
        .urgensi-tinggi .urgensi-icon { color: #D98A6C; }

        /* ===== FORM ACTIONS - RESPONSIVE ===== */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 2px solid #90AB8B;
            flex-wrap: wrap;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.4s;
            animation-fill-mode: both;
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
            min-width: 130px;
        }

        .btn-reset {
            background: #90AB8B;
            color: #3B4953;
            border: 1px solid #5A7863;
        }

        .btn-reset:hover {
            background: #A8BF9A;
            transform: translateY(-2px);
        }

        .btn-submit {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
            box-shadow: 0 8px 20px rgba(59, 73, 83, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(59, 73, 83, 0.5);
        }

        /* ===== INFO BOX - RESPONSIVE ===== */
        .info-box {
            background: rgba(59, 73, 83, 0.85);
            backdrop-filter: blur(8px);
            border-radius: 24px;
            padding: 20px;
            border-left: 5px solid #90AB8B;
            color: white;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.45s;
            animation-fill-mode: both;
        }

        .info-box:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .info-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 600;
        }

        .info-title i {
            transition: transform 0.3s ease;
        }

        .info-box:hover .info-title i {
            transform: scale(1.1);
        }

        .info-content ul {
            list-style: none;
        }

        .info-content li {
            margin-bottom: 8px;
            padding-left: 22px;
            position: relative;
            color: rgba(235, 244, 221, 0.9);
            font-size: 0.85rem;
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
        }

        /* ===== FOOTER - RESPONSIVE ===== */
        .footer {
            background: #3B4953;
            border-radius: 40px 40px 0 0;
            padding: 24px 20px;
            margin-top: 32px;
            text-align: center;
            color: white;
            border-top: 1px solid rgba(235, 244, 221, 0.2);
            transition: all 0.3s ease;
        }

        .footer:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
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

        /* ===== CHARACTER COUNTER ===== */
        .char-counter {
            font-size: 11px;
            color: #5A7863;
            margin-top: 5px;
            text-align: right;
        }

        .char-counter.warning {
            color: #D98A6C;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
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
            .form-container {
                padding: 24px;
            }
            .form-header {
                justify-content: center;
                text-align: center;
            }
        }

        @media (max-width: 600px) {
            .urgensi-options {
                flex-direction: column;
            }
            .urgensi-option {
                min-width: auto;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
            .form-icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            .form-title h2 {
                font-size: 20px;
            }
            .page-header-left h1 {
                font-size: 20px;
            }
            .page-header-left i {
                font-size: 22px;
            }
            .back-btn {
                padding: 6px 14px;
                font-size: 0.8rem;
            }
            .info-box {
                padding: 16px;
            }
            .info-content li {
                font-size: 0.8rem;
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
            .form-container {
                padding: 18px;
            }
            .form-control {
                padding: 10px 14px;
                font-size: 13px;
            }
            label {
                font-size: 0.85rem;
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
        <a href="pengaduan.php" class="active"><i class="fas fa-comment-medical"></i> Pengaduan</a>
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
    
    <!-- Mobile Menu Button -->
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
    <a href="pengaduan.php" class="active"><i class="fas fa-comment-medical"></i> Pengaduan</a>
    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a>
    <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
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
            <i class="fas fa-comment-medical"></i>
            <h1>Buat Pengaduan</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i>  Kembali</a>
    </div>

    <!-- Alert Messages -->
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
    <?php endif; ?>

    <?php if (!$table_exists): ?>
    <div class="alert alert-info">
        <i class="fas fa-database"></i>
        <div>
            <strong>Perhatian:</strong> Tabel pengaduan belum ada di database. 
            Sistem akan membuat tabel secara otomatis saat pertama kali mengirim pengaduan.
        </div>
    </div>
    <?php endif; ?>

    <!-- Form Container -->
    <div class="form-container">
        <div class="form-header">
            <div class="form-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="form-title">
                <h2>Form Pengaduan Warga</h2>
                <p>Isi dengan lengkap dan jelas</p>
            </div>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="pengaduanForm">
            <input type="hidden" name="MAX_FILE_SIZE" value="20971520">
            
            <div class="form-grid">
                <!-- Judul -->
                <div class="form-group full-width">
                    <label><i class="fas fa-heading"></i> Judul Pengaduan <span class="required">* Wajib</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Contoh: Jalan Berlubang di Depan Rumah No. 15" value="<?php echo htmlspecialchars($_POST['judul'] ?? ''); ?>" required>
                </div>

                <!-- Kategori -->
                <div class="form-group">
                    <label><i class="fas fa-tags"></i> Kategori <span class="required">* Wajib</span></label>
                    <?php
                    $query_kategori = "SELECT * FROM kategori_pengaduan ORDER BY nama";
                    $result_kategori = mysqli_query($conn, $query_kategori);
                    ?>
                    <select id="kategori" name="kategori" class="form-control" required>
                        <option value="">Pilih Kategori</option>
                        <?php while ($kategori = mysqli_fetch_assoc($result_kategori)): ?>
                            <option value="<?php echo htmlspecialchars($kategori['nama']); ?>" 
                                <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == $kategori['nama']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kategori['nama']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Lokasi - WAJIB -->
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Lokasi Kejadian <span class="required">* Wajib</span></label>
                    <input type="text" name="lokasi" class="form-control" placeholder="Contoh: RT 05, Jalan Merdeka No. 15" value="<?php echo htmlspecialchars($_POST['lokasi'] ?? ''); ?>" required>
                    <small style="color: #5A7863; margin-top: 5px; display: block; font-size: 0.75rem;">Isi dengan alamat lengkap agar petugas bisa segera menindaklanjuti</small>
                </div>

                <!-- Deskripsi -->
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Deskripsi Lengkap <span class="required">* Wajib (Min. 20 karakter)</span></label>
                    <textarea name="deskripsi" class="form-control" id="deskripsi" placeholder="Jelaskan masalah Anda secara detail. Kapan terjadi? Bagaimana kondisi saat ini? Apa yang Anda harapkan?" required><?php echo htmlspecialchars($_POST['deskripsi'] ?? ''); ?></textarea>
                    <div class="char-counter" id="charCounter">0 karakter (Minimal 20)</div>
                </div>

                <!-- Upload Foto -->
                <div class="form-group full-width">
                    <label><i class="fas fa-camera"></i> Upload Foto Bukti <span class="required">(Opsional)</span></label>
                    <div class="file-upload">
                        <input type="file" name="foto" accept="image/*">
                        <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="upload-text">
                            <h4>Klik untuk Upload Foto</h4>
                            <p>Format: JPG, PNG, GIF (Maks. 20MB)</p>
                        </div>
                        <div class="file-preview" id="filePreview"></div>
                    </div>
                </div>

                <!-- Tingkat Urgensi -->
                <div class="form-group full-width">
                    <label><i class="fas fa-exclamation-triangle"></i> Tingkat Urgensi</label>
                    <div class="urgensi-options">
                        <div class="urgensi-option urgensi-rendah">
                            <input type="radio" id="urgensi_rendah" name="urgensi" value="rendah" <?php echo ($_POST['urgensi'] ?? 'sedang') == 'rendah' ? 'checked' : ''; ?>>
                            <label for="urgensi_rendah" class="urgensi-label">
                                <div class="urgensi-icon"><i class="far fa-smile"></i></div>
                                <div>Rendah</div>
                                <small>Bisa ditunda</small>
                            </label>
                        </div>
                        <div class="urgensi-option urgensi-sedang">
                            <input type="radio" id="urgensi_sedang" name="urgensi" value="sedang" <?php echo ($_POST['urgensi'] ?? 'sedang') == 'sedang' ? 'checked' : ''; ?> checked>
                            <label for="urgensi_sedang" class="urgensi-label">
                                <div class="urgensi-icon"><i class="far fa-meh"></i></div>
                                <div>Sedang</div>
                                <small>Perlu penanganan</small>
                            </label>
                        </div>
                        <div class="urgensi-option urgensi-tinggi">
                            <input type="radio" id="urgensi_tinggi" name="urgensi" value="tinggi" <?php echo ($_POST['urgensi'] ?? 'sedang') == 'tinggi' ? 'checked' : ''; ?>>
                            <label for="urgensi_tinggi" class="urgensi-label">
                                <div class="urgensi-icon"><i class="far fa-frown"></i></div>
                                <div>Tinggi</div>
                                <small>Harus segera</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="reset" class="btn btn-reset"><i class="fas fa-redo"></i> Reset Form</button>
                <button type="submit" class="btn btn-submit"><i class="fas fa-paper-plane"></i> Kirim Pengaduan</button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <div class="info-title"><i class="fas fa-info-circle"></i> Informasi Penting</div>
        <div class="info-content">
            <ul>
                <li><strong>Lokasi wajib diisi</strong> agar petugas dapat segera menindaklanjuti pengaduan</li>
                <li>Pengaduan Anda akan diproses maksimal 3 hari kerja</li>
                <li>Pastikan data yang diisi akurat dan dapat dipertanggungjawabkan</li>
                <li>Foto bukti akan membantu mempercepat proses penanganan</li>
                <li>Anda dapat melacak status pengaduan di menu "Riwayat"</li>
                <li>Pengaduan palsu atau tidak bertanggung jawab akan dikenai sanksi</li>
                <li>Simpan nomor tiket untuk referensi dan pengecekan status</li>
            </ul>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo $current_year; ?> e-RT Digital - Sistem Pengaduan Warga</p>
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

    // File upload preview
    const fileInput = document.querySelector('.file-upload input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('filePreview');
            
            if (file) {
                if (file.size > 20 * 1024 * 1024) {
                    alert('Ukuran file maksimal 20MB!');
                    this.value = '';
                    preview.style.display = 'none';
                    return;
                }
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan format JPG, PNG, atau GIF.');
                    this.value = '';
                    preview.style.display = 'none';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    }

    // Character counter untuk deskripsi
    const deskripsiTextarea = document.getElementById('deskripsi');
    const charCounter = document.getElementById('charCounter');
    
    if (deskripsiTextarea && charCounter) {
        function updateCharCounter() {
            const length = deskripsiTextarea.value.length;
            charCounter.textContent = length + ' karakter (Minimal 20)';
            if (length < 20 && length > 0) {
                charCounter.classList.add('warning');
            } else {
                charCounter.classList.remove('warning');
            }
        }
        
        deskripsiTextarea.addEventListener('input', updateCharCounter);
        updateCharCounter();
    }

    // Form validation
    document.getElementById('pengaduanForm').addEventListener('submit', function(e) {
        const judul = document.querySelector('input[name="judul"]').value.trim();
        const kategori = document.querySelector('select[name="kategori"]').value;
        const lokasi = document.querySelector('input[name="lokasi"]').value.trim();
        const deskripsi = document.querySelector('textarea[name="deskripsi"]').value.trim();
        
        if (!judul || !kategori || !lokasi || !deskripsi) {
            e.preventDefault();
            let missingFields = [];
            if (!judul) missingFields.push('Judul');
            if (!kategori) missingFields.push('Kategori');
            if (!lokasi) missingFields.push('Lokasi');
            if (!deskripsi) missingFields.push('Deskripsi');
            alert('Harap isi semua field yang wajib diisi: ' + missingFields.join(', '));
            return false;
        }
        
        if (deskripsi.length < 20) {
            e.preventDefault();
            alert('Deskripsi terlalu pendek. Minimal 20 karakter! Saat ini: ' + deskripsi.length + ' karakter');
            return false;
        }
        
        if (lokasi.length < 5) {
            e.preventDefault();
            alert('Lokasi terlalu pendek. Minimal 5 karakter! Contoh: RT 05, Jalan Merdeka No. 15');
            return false;
        }
        
        // Konfirmasi sebelum submit
        return confirm('Pastikan data yang Anda isi sudah benar. Lanjutkan kirim pengaduan?');
    });

    // Auto-resize textarea
    const textarea = document.querySelector('textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // Highlight required fields yang kosong saat reset
    const resetBtn = document.querySelector('.btn-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            setTimeout(() => {
                const requiredFields = document.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    field.style.borderColor = '#90AB8B';
                });
            }, 100);
        });
    }
</script>

</body>
</html>