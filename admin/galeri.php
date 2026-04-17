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

// Buat tabel galeri jika belum ada
$create_table = "CREATE TABLE IF NOT EXISTS galeri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    foto VARCHAR(255) NOT NULL,
    tanggal DATE,
    tampil TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table);

// Cek apakah kolom 'tampil' ada
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM galeri LIKE 'tampil'");
if (mysqli_num_rows($column_check) == 0) {
    mysqli_query($conn, "ALTER TABLE galeri ADD tampil TINYINT DEFAULT 1 AFTER tanggal");
}

// Pastikan folder uploads ada
$target_dir = __DIR__ . '/../uploads/galeri/';
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$message = '';
$message_type = '';

// ========== PROSES UPLOAD ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload') {
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        $tampil = isset($_POST['tampil']) ? 1 : 0;
        
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $message = "Error upload file";
            $message_type = "error";
        } else {
            $file = $_FILES['foto'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_file = $target_dir . $file_name;
            
            $check = getimagesize($file['tmp_name']);
            if ($check === false) {
                $message = "File bukan gambar valid.";
                $message_type = "error";
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $message = "Ukuran file terlalu besar (maks 10MB).";
                $message_type = "error";
            } elseif (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $message = "Hanya file JPG, JPEG, PNG, GIF & WEBP yang diperbolehkan.";
                $message_type = "error";
            } elseif (move_uploaded_file($file['tmp_name'], $target_file)) {
                $foto_path = "uploads/galeri/" . $file_name;
                $query = "INSERT INTO galeri (judul, deskripsi, foto, tanggal, tampil) VALUES ('$judul', '$deskripsi', '$foto_path', '$tanggal', '$tampil')";
                if (mysqli_query($conn, $query)) {
                    $message = "✅ Foto berhasil diupload!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menyimpan ke database: " . mysqli_error($conn);
                    $message_type = "error";
                    unlink($target_file);
                }
            } else {
                $message = "Gagal memindahkan file.";
                $message_type = "error";
            }
        }
    }
    // ========== PROSES EDIT ==========
    elseif ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        $tampil = isset($_POST['tampil']) ? 1 : 0;
        
        $query = "UPDATE galeri SET judul='$judul', deskripsi='$deskripsi', tanggal='$tanggal', tampil='$tampil' WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Data berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
    // ========== PROSES HAPUS ==========
    elseif ($_POST['action'] === 'hapus') {
        $id = intval($_POST['id']);
        // Ambil nama file foto untuk dihapus
        $query_foto = "SELECT foto FROM galeri WHERE id = $id";
        $result_foto = mysqli_query($conn, $query_foto);
        if ($result_foto && $row = mysqli_fetch_assoc($result_foto)) {
            $foto_path = __DIR__ . '/../' . $row['foto'];
            if (file_exists($foto_path)) {
                unlink($foto_path);
            }
        }
        $query = "DELETE FROM galeri WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Foto berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
    // ========== PROSES TOGGLE STATUS ==========
    elseif ($_POST['action'] === 'toggle') {
        $id = intval($_POST['id']);
        $query = "UPDATE galeri SET tampil = NOT tampil WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = "✅ Status tampil foto berhasil diubah.";
            $message_type = "success";
        } else {
            $message = "Gagal mengubah status: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Ambil data galeri
$query = "SELECT * FROM galeri ORDER BY tampil DESC, tanggal DESC, id DESC";
$result = mysqli_query($conn, $query);
$galeri = mysqli_fetch_all($result, MYSQLI_ASSOC);

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Admin Galeri - e-RT Digital</title>
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

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .animate-fadeInLeft {
            animation: fadeInLeft 0.5s ease-out forwards;
        }

        .animate-fadeInRight {
            animation: fadeInRight 0.5s ease-out forwards;
        }

        .animate-scaleIn {
            animation: scaleIn 0.4s ease-out forwards;
        }

        .animate-slideIn {
            animation: slideIn 0.3s ease-out forwards;
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
            min-height: 100px;
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

        /* File Upload Style */
        .file-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--gray-50);
        }

        .file-upload-area:hover {
            border-color: var(--secondary);
            background: rgba(101, 148, 177, 0.05);
            transform: translateY(-2px);
        }

        .file-upload-area i {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 12px;
        }

        .file-upload-area p {
            color: var(--gray-500);
            font-size: 14px;
        }

        .file-upload-area .file-info {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 8px;
        }

        .file-upload-area input[type="file"] {
            display: none;
        }

        .file-name-preview {
            margin-top: 12px;
            padding: 8px 12px;
            background: #EFF6FF;
            border-radius: 8px;
            color: var(--secondary);
            font-size: 13px;
            display: none;
        }

        .file-name-preview i {
            margin-right: 8px;
        }

        .btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
        }

        .btn-warning {
            background: #F59E0B;
            color: white;
        }

        .btn-warning:hover {
            background: #D97706;
        }

        .btn-danger {
            background: #FEE2E2;
            color: #DC2626;
        }

        .btn-danger:hover {
            background: #FECACA;
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

        .btn-toggle:hover {
            transform: translateY(-1px);
        }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }

        .gallery-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            animation: scaleIn 0.4s ease-out forwards;
            opacity: 0;
        }

        .gallery-card:nth-child(1) { animation-delay: 0.05s; }
        .gallery-card:nth-child(2) { animation-delay: 0.1s; }
        .gallery-card:nth-child(3) { animation-delay: 0.15s; }
        .gallery-card:nth-child(4) { animation-delay: 0.2s; }
        .gallery-card:nth-child(5) { animation-delay: 0.25s; }
        .gallery-card:nth-child(6) { animation-delay: 0.3s; }

        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }

        .gallery-card.tidak-tampil {
            opacity: 0.7;
            background: var(--gray-50);
            border: 1px dashed var(--gray-300);
        }

        .gallery-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .gallery-card:hover img {
            transform: scale(1.02);
        }

        .card-body {
            padding: 16px;
        }

        .card-body h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 6px;
        }

        .card-body .date {
            font-size: 12px;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        .card-body p {
            font-size: 13px;
            color: var(--gray-600);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .gallery-card:hover .status-badge {
            transform: scale(1.02);
        }

        .status-badge.tampil {
            background: #D1FAE5;
            color: #059669;
        }

        .status-badge.tidak-tampil {
            background: #FEE2E2;
            color: #DC2626;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            animation: fadeInUp 0.3s ease-out;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 550px;
            padding: 28px;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-500);
            transition: 0.3s ease;
        }

        .close-modal:hover {
            color: var(--danger);
            transform: rotate(90deg);
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
            padding: 24px 20px;
            margin-top: 40px;
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
            .gallery-grid {
                grid-template-columns: 1fr;
            }
            .file-upload-area {
                padding: 20px;
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
            <a href="galeri.php" class="active"><i class="fas fa-images"></i> Galeri</a>
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
                    <i class="fas fa-images"></i>
                    <h1>Kelola Galeri</h1>
                </div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Foto dengan status <strong>"Tidak Tampil"</strong> tidak akan muncul di halaman user. Data foto tetap tersimpan dan dapat diubah statusnya kapan saja.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Form Upload -->
            <div class="form-card">
                <h2><i class="fas fa-upload"></i> Tambah Foto Baru</h2>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Masukkan judul foto">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" required placeholder="Deskripsi kegiatan atau keterangan foto"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="tampil" id="tampil" checked>
                        <label for="tampil">Tampilkan di halaman user</label>
                    </div>
                    <div class="form-group">
                        <label>Foto</label>
                        <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik untuk upload gambar</p>
                            <p class="file-info">Format: JPG, PNG, GIF, WEBP (max 10MB)</p>
                            <input type="file" id="fileInput" name="foto" accept="image/*" required style="display:none">
                        </div>
                        <div id="fileNamePreview" class="file-name-preview">
                            <i class="fas fa-image"></i> <span id="fileName"></span>
                        </div>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-upload"></i> Upload Foto</button>
                </form>
            </div>

            <!-- Daftar Foto -->
            <div class="gallery-grid">
                <?php foreach ($galeri as $item): 
                    $tampil_class = $item['tampil'] == 1 ? 'tampil' : 'tidak-tampil';
                    $card_class = $item['tampil'] == 0 ? 'tidak-tampil' : '';
                ?>
                <div class="gallery-card <?php echo $card_class; ?>">
                    <img src="../<?php echo htmlspecialchars($item['foto']); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>">
                    <div class="card-body">
                        <div class="status-badge <?php echo $tampil_class; ?>">
                            <i class="fas fa-<?php echo $item['tampil'] == 1 ? 'eye' : 'eye-slash'; ?>"></i>
                            <?php echo $item['tampil'] == 1 ? 'Tampil' : 'Tidak Tampil'; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($item['judul']); ?></h3>
                        <div class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($item['tanggal'])); ?></div>
                        <p><?php echo htmlspecialchars(substr($item['deskripsi'], 0, 100)); if (strlen($item['deskripsi']) > 100) echo '...'; ?></p>
                        <div class="card-actions">
                            <button class="btn btn-warning btn-small" onclick="openEditModal(<?php echo $item['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-toggle btn-small <?php echo $tampil_class; ?>" onclick="return confirm('Ubah status tampil foto ini?')">
                                    <i class="fas fa-<?php echo $item['tampil'] == 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                    <?php echo $item['tampil'] == 1 ? 'Sembunyikan' : 'Tampilkan'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-small btn-danger" onclick="return confirm('Yakin ingin menghapus foto ini? Tindakan ini tidak dapat dibatalkan.')">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($galeri)): ?>
                    <p style="text-align:center; grid-column:1/-1; color: var(--gray-500); padding: 40px;">Belum ada foto. Upload foto pertama Anda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Foto</h3>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" id="edit_judul" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="tampil" id="edit_tampil">
                    <label for="edit_tampil">Tampilkan di halaman user</label>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn" style="background: var(--gray-200); color: var(--gray-600);" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn">Simpan Perubahan</button>
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

        // File upload preview
        const fileInput = document.getElementById('fileInput');
        const fileNamePreview = document.getElementById('fileNamePreview');
        const fileNameSpan = document.getElementById('fileName');

        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    fileNameSpan.textContent = file.name;
                    fileNamePreview.style.display = 'block';
                } else {
                    fileNamePreview.style.display = 'none';
                }
            });
        }

        // Fungsi untuk mengambil data dari server via AJAX
        function openEditModal(id) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_judul').value = 'Loading...';
            document.getElementById('edit_deskripsi').value = 'Loading...';
            document.getElementById('edit_tanggal').value = '';
            document.getElementById('edit_tampil').checked = false;
            document.getElementById('editModal').classList.add('active');
            
            fetch('get_galeri.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.data.id;
                        document.getElementById('edit_judul').value = data.data.judul;
                        document.getElementById('edit_deskripsi').value = data.data.deskripsi;
                        document.getElementById('edit_tanggal').value = data.data.tanggal;
                        document.getElementById('edit_tampil').checked = (data.data.tampil == 1);
                    } else {
                        alert('Gagal memuat data: ' + data.message);
                        closeEditModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data');
                    closeEditModal();
                });
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>