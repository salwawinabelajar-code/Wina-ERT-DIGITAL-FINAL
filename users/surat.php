<?php
session_start();

// Koneksi database
$host = 'localhost';
$dbname = 'pengaduan_rt';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Cek login - HARUS LOGIN TERLEBIH DAHULU
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

$error_msg = '';
$success = false;

// Proses form pengajuan surat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jenis_surat'])) {
    $jenis_surat = htmlspecialchars($_POST['jenis_surat']);
    $keperluan = htmlspecialchars($_POST['keperluan']);
    $keterangan = htmlspecialchars($_POST['keterangan'] ?? '');
    
    if (empty($jenis_surat) || empty($keperluan)) {
        $error_msg = 'Jenis surat dan keperluan harus diisi!';
    } else {
        $file_name = null;
        if (isset($_FILES['file_pendukung']) && $_FILES['file_pendukung']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file_pendukung'];
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error_msg = 'Format file tidak didukung. Gunakan: PDF, DOC, DOCX, JPG, PNG';
            } elseif ($file_size > $max_size) {
                $error_msg = 'Ukuran file terlalu besar. Maksimal 5MB';
            } else {
                $file_name = 'surat_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_dir = '../uploads/surat/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_path = $upload_dir . $file_name;
                if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                    $error_msg = 'Gagal mengupload file.';
                }
            }
        } elseif ($_FILES['file_pendukung']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error_msg = 'Terjadi error saat upload file.';
        }
        
        if (empty($error_msg)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO pengajuan_surat 
                    (user_id, nama_user, jenis_surat, keperluan, keterangan, file_pendukung, status, tanggal_pengajuan) 
                    VALUES (:user_id, :nama_user, :jenis_surat, :keperluan, :keterangan, :file_pendukung, 'menunggu', NOW())");
                
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':nama_user' => $user['nama'],
                    ':jenis_surat' => $jenis_surat,
                    ':keperluan' => $keperluan,
                    ':keterangan' => $keterangan,
                    ':file_pendukung' => $file_name
                ]);
                
                // Redirect ke halaman riwayat tab surat
                header('Location: riwayat.php?tab=surat');
                exit();
            } catch(PDOException $e) {
                $error_msg = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
}

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Pengajuan Surat - e-RT Digital</title>
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
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        /* Page Header - RESPONSIVE */
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

        /* Alert messages */
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

        /* Form container - RESPONSIVE */
        .form-container {
            background: #EBF4DD;
            border-radius: 32px;
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
            animation-delay: 0.1s;
            animation-fill-mode: both;
        }

        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 2px solid #90AB8B;
            flex-wrap: wrap;
        }

        .card-icon {
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

        .form-container:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .card-title {
            font-size: 24px;
            font-weight: 700;
            color: #3B4953;
        }

        .card-subtitle {
            color: #5A7863;
            font-size: 14px;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #3B4953;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .required {
            color: #D98A6C;
            font-size: 12px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #90AB8B;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
            color: #3B4953;
        }

        .form-control:focus {
            outline: none;
            border-color: #5A7863;
            background: white;
            box-shadow: 0 0 0 3px rgba(90, 120, 99, 0.2);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #A8BFA0;
        }

        textarea.form-control {
            min-height: 100px;
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

        /* Surat options - RESPONSIVE */
        .surat-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 8px;
        }

        .surat-option {
            border: 2px solid #90AB8B;
            border-radius: 18px;
            padding: 20px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            min-height: 180px;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            animation: scaleIn 0.4s ease-out forwards;
            opacity: 0;
        }

        .surat-option:nth-child(1) { animation-delay: 0.15s; }
        .surat-option:nth-child(2) { animation-delay: 0.2s; }
        .surat-option:nth-child(3) { animation-delay: 0.25s; }

        .surat-option:hover {
            border-color: #5A7863;
            background: #F5F9EF;
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .surat-option.selected {
            border-color: #5A7863;
            background: #F0F5E8;
            box-shadow: var(--shadow-md);
        }

        .surat-icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #90AB8B;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            transition: transform 0.3s ease;
        }

        .surat-option:hover .surat-icon {
            transform: scale(1.1);
        }

        .surat-label {
            font-weight: 700;
            color: #3B4953;
            font-size: 16px;
        }

        .surat-desc {
            font-size: 12px;
            color: #5A7863;
            line-height: 1.4;
        }

        /* File upload */
        .file-upload {
            position: relative;
            border: 2px dashed #90AB8B;
            border-radius: 18px;
            padding: 28px;
            text-align: center;
            transition: all 0.3s;
            background: white;
            cursor: pointer;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.3s;
            animation-fill-mode: both;
        }

        .file-upload:hover {
            border-color: #5A7863;
            background: #F5F9EF;
            transform: translateY(-2px);
        }

        .file-upload input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload .icon {
            font-size: 40px;
            color: #90AB8B;
            margin-bottom: 12px;
            transition: transform 0.3s ease;
        }

        .file-upload:hover .icon {
            transform: scale(1.1);
        }

        .file-upload p {
            color: #5A7863;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .file-upload .file-info {
            color: #7A8E7A;
            font-size: 11px;
        }

        #file_name {
            color: #7DA06E;
            font-weight: 600;
            margin-top: 12px;
            font-size: 12px;
        }

        /* Submit button */
        .btn {
            padding: 14px 24px;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.35s;
            animation-fill-mode: both;
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

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Tips box */
        .tips-box {
            background: #EBF4DD;
            border-left: 5px solid #90AB8B;
            border-radius: 24px;
            padding: 20px 24px;
            margin-top: 32px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.4s;
            animation-fill-mode: both;
        }

        .tips-box:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .tips-title {
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #3B4953;
        }

        .tips-title i {
            transition: transform 0.3s ease;
        }

        .tips-box:hover .tips-title i {
            transform: scale(1.1);
        }

        .tips-list {
            list-style-type: none;
        }

        .tips-list li {
            margin-bottom: 10px;
            color: #5A7863;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            line-height: 1.5;
            transition: transform 0.3s ease;
        }

        .tips-list li:hover {
            transform: translateX(5px);
        }

        .tips-list li:before {
            content: "✓";
            color: #90AB8B;
            font-weight: bold;
            font-size: 14px;
        }

        /* Footer */
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

        /* ========== RESPONSIVE BREAKPOINTS ========== */
        @media (max-width: 900px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                border-radius: 30px;
            }
            .page-header-left {
                justify-content: center;
            }
            .surat-options {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }
            .container {
                padding: 0 16px;
                margin: 20px auto;
            }
        }

        @media (max-width: 600px) {
            .surat-options {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .surat-option {
                min-height: 140px;
                padding: 16px;
                flex-direction: row;
                text-align: left;
                gap: 16px;
                justify-content: flex-start;
            }
            .surat-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            .surat-label {
                font-size: 14px;
            }
            .surat-desc {
                font-size: 11px;
            }
            .form-container {
                padding: 20px;
            }
            .card-title {
                font-size: 20px;
            }
            .card-icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            .btn {
                font-size: 14px;
                padding: 12px 20px;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            .footer-links {
                justify-content: center;
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
            .form-container {
                padding: 16px;
            }
            .card-title {
                font-size: 18px;
            }
            .surat-option {
                padding: 12px;
            }
            .surat-icon {
                width: 45px;
                height: 45px;
                font-size: 18px;
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
        <a href="surat.php" class="active"><i class="fas fa-envelope-open-text"></i> Surat</a>
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
    <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a>
    <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
    <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
    <a href="surat.php" class="active"><i class="fas fa-envelope-open-text"></i> Surat</a>
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
            <i class="fas fa-envelope-open-text"></i>
            <h1>Pengajuan Surat</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>Berhasil! Pengajuan surat Anda telah dikirim. Anda dapat melacak statusnya di halaman Riwayat.</div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>Gagal! <?php echo $error_msg; ?></div>
        </div>
    <?php endif; ?>

    <!-- Form Container -->
    <div class="form-container">
        <div class="card-header">
            <div class="card-icon">
                <i class="fas fa-file-signature"></i>
            </div>
            <div>
                <h2 class="card-title">Form Pengajuan Surat</h2>
                <p class="card-subtitle">Isi form untuk mengajukan pembuatan surat</p>
            </div>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="suratForm">
            <!-- Jenis Surat -->
            <div class="form-group">
                <label>
                    <i class="fas fa-envelope"></i> Jenis Surat <span class="required">*</span>
                </label>
                <div class="surat-options">
                    <div class="surat-option" data-value="surat pengantar">
                        <div class="surat-icon"><i class="fas fa-file-export"></i></div>
                        <div>
                            <div class="surat-label">Surat Pengantar</div>
                            <div class="surat-desc">Untuk pengurusan KTP, KK, BPJS, dan dokumen resmi lainnya</div>
                        </div>
                    </div>
                    <div class="surat-option" data-value="surat keterangan tidak mampu">
                        <div class="surat-icon"><i class="fas fa-hand-holding-heart"></i></div>
                        <div>
                            <div class="surat-label">Surat Keterangan Tidak Mampu</div>
                            <div class="surat-desc">Untuk bantuan sosial, beasiswa, atau program pemerintah</div>
                        </div>
                    </div>
                    <div class="surat-option" data-value="surat keterangan">
                        <div class="surat-icon"><i class="fas fa-file-certificate"></i></div>
                        <div>
                            <div class="surat-label">Surat Keterangan</div>
                            <div class="surat-desc">Untuk administrasi kerja, studi, atau keperluan umum</div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="jenis_surat" name="jenis_surat" value="" required>
            </div>

            <!-- Keperluan -->
            <div class="form-group">
                <label for="keperluan"><i class="fas fa-list-alt"></i> Keperluan Surat <span class="required">*</span></label>
                <textarea id="keperluan" name="keperluan" class="form-control" placeholder="Jelaskan secara detail untuk keperluan apa surat ini dibutuhkan." required></textarea>
            </div>

            <!-- Keterangan Tambahan -->
            <div class="form-group">
                <label for="keterangan"><i class="fas fa-info-circle"></i> Keterangan Tambahan (Opsional)</label>
                <textarea id="keterangan" name="keterangan" class="form-control" placeholder="Tambahkan keterangan lain jika diperlukan."></textarea>
            </div>

            <!-- File Pendukung -->
            <div class="form-group">
                <label><i class="fas fa-paperclip"></i> File Pendukung (Opsional)</label>
                <div class="file-upload">
                    <input type="file" name="file_pendukung" id="file_pendukung" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <p>Klik atau drag file untuk upload</p>
                    <p class="file-info">Maksimal ukuran file: 5MB - Format: PDF, DOC, DOCX, JPG, PNG</p>
                    <p id="file_name"></p>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-submit"><i class="fas fa-paper-plane"></i> Kirim Pengajuan Surat</button>
        </form>

        <!-- Tips Box -->
        <div class="tips-box">
            <div class="tips-title"><i class="fas fa-lightbulb"></i> Informasi Penting</div>
            <ul class="tips-list">
                <li>Surat akan diproses dalam 1-3 hari kerja setelah pengajuan diterima.</li>
                <li>Lampirkan dokumen pendukung (KTP, KK, dll) jika diperlukan untuk mempercepat verifikasi.</li>
                <li>Status pengajuan dapat dilacak di halaman Riwayat > Tab Surat.</li>
                <li>Pastikan informasi yang diberikan lengkap dan jelas.</li>
                <li>Surat yang sudah selesai dapat diambil di sekretariat RT.</li>
            </ul>
        </div>
    </div>
</div>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo $current_year; ?> e-RT Digital - Sistem Informasi RT 05 Sukamaju</p>
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

    // Surat options
    const suratOptions = document.querySelectorAll('.surat-option');
    const jenisSuratInput = document.getElementById('jenis_surat');

    suratOptions.forEach(option => {
        option.addEventListener('click', function() {
            suratOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            jenisSuratInput.value = this.dataset.value;
            this.style.transform = 'translateY(-2px)';
            setTimeout(() => this.style.transform = '', 150);
        });
    });

    if (suratOptions.length > 0 && !jenisSuratInput.value) {
        suratOptions[0].click();
    }

    // File upload
    const fileInput = document.getElementById('file_pendukung');
    const fileNameDisplay = document.getElementById('file_name');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileUploadArea = document.querySelector('.file-upload');
            
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                const fileName = file.name.length > 30 ? file.name.substring(0,30)+'...' : file.name;
                fileNameDisplay.textContent = `File terpilih: ${fileName} (${fileSize} MB)`;
                fileNameDisplay.style.color = '#7DA06E';
                fileUploadArea.style.borderColor = '#7DA06E';
                fileUploadArea.style.backgroundColor = '#F0F5E8';
                setTimeout(() => {
                    fileUploadArea.style.borderColor = '';
                    fileUploadArea.style.backgroundColor = '';
                }, 2000);
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    }

    // Form validation
    const suratForm = document.getElementById('suratForm');
    if (suratForm) {
        suratForm.addEventListener('submit', function(e) {
            const jenisSurat = document.getElementById('jenis_surat').value;
            const keperluan = document.getElementById('keperluan').value.trim();
            
            if (!jenisSurat) {
                e.preventDefault();
                alert('Mohon pilih jenis surat yang dibutuhkan!');
                suratOptions.forEach(opt => opt.style.borderColor = '#D98A6C');
                setTimeout(() => {
                    suratOptions.forEach(opt => opt.style.borderColor = '');
                }, 2000);
                return false;
            }
            if (!keperluan) {
                e.preventDefault();
                alert('Mohon isi keperluan surat!');
                document.getElementById('keperluan').focus();
                return false;
            }
            
            const fileInput = document.getElementById('file_pendukung');
            if (fileInput.files.length > 0) {
                const fileSize = fileInput.files[0].size;
                if (fileSize > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('Ukuran file terlalu besar! Maksimal 5MB.');
                    fileInput.value = '';
                    document.getElementById('file_name').textContent = 'File terlalu besar, pilih file lain';
                    return false;
                }
            }
            
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim Pengajuan...';
            submitBtn.disabled = true;
        });
    }

    // Auto-resize textarea
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    // Drag and drop
    const fileUpload = document.querySelector('.file-upload');
    if (fileUpload) {
        fileUpload.addEventListener('dragover', e => {
            e.preventDefault();
            fileUpload.style.borderColor = '#5A7863';
            fileUpload.style.backgroundColor = '#F0F5E8';
        });
        fileUpload.addEventListener('dragleave', e => {
            e.preventDefault();
            fileUpload.style.borderColor = '';
            fileUpload.style.backgroundColor = '';
        });
        fileUpload.addEventListener('drop', e => {
            e.preventDefault();
            fileUpload.style.borderColor = '';
            fileUpload.style.backgroundColor = '';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('file_pendukung');
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }
</script>

</body>
</html>