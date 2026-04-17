<?php
// terms.php - Halaman Syarat & Ketentuan
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

$current_year = date('Y');
$last_updated = "15 Januari 2025";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#5A7863">
    <title>Syarat & Ketentuan - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        /* Premium Color Palette */
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

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #EBF4DD 0%, #A8BF9A 40%, #90AB8B 70%, #5A7863 100%);
            min-height: 100vh;
            color: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 40%, rgba(235, 244, 221, 0.2) 0%, rgba(90, 120, 99, 0.15) 100%);
            pointer-events: none;
            z-index: -1;
            animation: softPulse 8s ease-in-out infinite;
        }

        @keyframes softPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.95), rgba(59, 73, 83, 0.95));
            backdrop-filter: blur(16px);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-md);
            border-bottom: 1px solid rgba(235, 244, 221, 0.3);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .logo-icon {
            background: linear-gradient(135deg, #EBF4DD, #90AB8B);
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3B4953;
            font-size: 22px;
            box-shadow: var(--shadow-sm);
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
            padding: 8px 18px;
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
            background: #90AB8B;
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background: #90AB8B;
            color: white;
            border-color: transparent;
            font-weight: 600;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(235, 244, 221, 0.15);
            padding: 6px 16px 6px 10px;
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
            width: 40px;
            height: 40px;
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
            line-height: 1.2;
        }

        .user-info small {
            font-size: 0.65rem;
            color: rgba(235, 244, 221, 0.85);
        }

        .logout-btn {
            background: rgba(217, 138, 108, 0.35);
            border: 1px solid rgba(235, 244, 221, 0.3);
            color: white;
            padding: 8px 18px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #D98A6C;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            background: rgba(235, 244, 221, 0.15);
            border: 1px solid rgba(235, 244, 221, 0.3);
            border-radius: 40px;
            padding: 8px 16px;
            color: white;
            font-size: 1.2rem;
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
            top: 70px;
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
            background: #90AB8B;
            color: white;
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

        .mobile-user-info .avatar {
            width: 45px;
            height: 45px;
            font-size: 1.1rem;
        }

        .dropdown-overlay {
            position: fixed;
            top: 70px;
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

        /* Container */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
            flex: 1;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #3B4953;
            border-radius: 60px;
            padding: 16px 32px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-md);
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .page-header-left i {
            font-size: 32px;
            color: #EBF4DD;
        }

        .page-header-left h1 {
            font-size: 28px;
            font-weight: 800;
            color: white;
        }

        .back-btn {
            background: #90AB8B;
            border: 1px solid #5A7863;
            color: white;
            padding: 10px 24px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #5A7863;
            transform: translateY(-2px);
        }

        /* Content Card */
        .content-card {
            background: #EBF4DD;
            border-radius: 30px;
            padding: 40px;
            border: 1px solid #90AB8B;
            box-shadow: var(--shadow-lg);
        }

        .last-updated {
            background: #90AB8B;
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            display: inline-block;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .terms-section {
            margin-bottom: 30px;
        }

        .terms-section h2 {
            color: #3B4953;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .terms-section h2 i {
            color: #90AB8B;
            font-size: 20px;
        }

        .terms-section p {
            color: #5A7863;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .terms-section ul {
            margin-left: 25px;
            margin-bottom: 15px;
        }

        .terms-section li {
            color: #5A7863;
            line-height: 1.8;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .terms-section li strong {
            color: #3B4953;
        }

        .highlight-box {
            background: white;
            border-left: 4px solid #90AB8B;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }

        .footer {
            background: #3B4953;
            border-radius: 50px 50px 0 0;
            padding: 30px 20px;
            margin-top: 50px;
            text-align: center;
            color: white;
            border-top: 1px solid #90AB8B;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer p {
            opacity: 0.9;
            font-size: 14px;
        }

        .footer-links {
            display: flex;
            gap: 20px;
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
            font-size: 14px;
        }

        .footer-links a:hover {
            opacity: 1;
            color: #EBF4DD;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 1rem;
            }
            .nav-menu {
                justify-content: center;
                width: 100%;
            }
            .user-section {
                width: 100%;
                justify-content: center;
            }
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .content-card {
                padding: 25px;
            }
            .terms-section h2 {
                font-size: 18px;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
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
    
    <div class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </div>
</nav>

<!-- Mobile Dropdown -->
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
    <a href="surat.php"><i class="fas fa-envelope-open-text"></i> Surat</a>
    <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>
    <a href="galeri.php"><i class="fas fa-images"></i> Galeri</a>
    <a href="bantuan.php"><i class="fas fa-question-circle"></i> Bantuan</a>
    <a href="../auth/logout.php" style="margin-top: 10px; background: rgba(217, 138, 108, 0.3);">
        <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
</div>

<div class="dropdown-overlay" id="dropdownOverlay"></div>

<!-- Main Content -->
<div class="container">
    <div class="page-header">
        <div class="page-header-left">
            <i class="fas fa-file-alt"></i>
            <h1>Syarat & Ketentuan</h1>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <div class="content-card">
        <div class="last-updated">
            <i class="fas fa-calendar-alt"></i> Terakhir diperbarui: <?php echo $last_updated; ?>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-check-circle"></i> 1. Penerimaan Syarat</h2>
            <p>Dengan mengakses dan menggunakan sistem e-RT Digital, Anda menyatakan bahwa Anda telah membaca, memahami, dan menyetujui semua syarat dan ketentuan yang tercantum dalam halaman ini. Jika Anda tidak setuju dengan syarat dan ketentuan ini, mohon untuk tidak menggunakan sistem.</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-user-check"></i> 2. Pengguna yang Berwenang</h2>
            <p>Sistem e-RT Digital hanya dapat digunakan oleh:</p>
            <ul>
                <li>Warga RT 05 yang telah terdaftar sebagai pengguna.</li>
                <li>Pengurus RT yang memiliki wewenang administratif.</li>
                <li>Setiap pengguna bertanggung jawab penuh atas keamanan akun masing-masing.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-gavel"></i> 3. Kewajiban Pengguna</h2>
            <p>Sebagai pengguna sistem, Anda wajib:</p>
            <ul>
                <li>Memberikan informasi yang benar, akurat, dan terkini saat mengisi data.</li>
                <li>Menjaga kerahasiaan kata sandi dan tidak membagikannya kepada pihak lain.</li>
                <li>Menggunakan sistem sesuai dengan ketentuan yang berlaku.</li>
                <li>Bertanggung jawab atas semua aktivitas yang terjadi di akun Anda.</li>
                <li>Melaporkan segera kepada pengurus jika terjadi penyalahgunaan akun.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-ban"></i> 4. Larangan Penggunaan</h2>
            <p>Pengguna DILARANG untuk:</p>
            <ul>
                <li>Mengirimkan pengaduan palsu atau tidak berdasar.</li>
                <li>Menggunakan kata-kata kasar, menghina, atau mengandung SARA dalam pengaduan.</li>
                <li>Mengunggah file yang mengandung virus atau malware.</li>
                <li>Mengakses data pengguna lain tanpa izin.</li>
                <li>Melakukan tindakan yang dapat merusak sistem atau mengganggu pengguna lain.</li>
                <li>Menyalahgunakan fitur sistem untuk kepentingan pribadi yang merugikan pihak lain.</li>
            </ul>
            <div class="highlight-box">
                <i class="fas fa-exclamation-triangle" style="color: #90AB8B; margin-right: 10px;"></i>
                <strong>Sanksi:</strong> Pelanggaran terhadap larangan di atas dapat mengakibatkan penonaktifan akun dan tindakan hukum sesuai peraturan yang berlaku.
            </div>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-clock"></i> 5. Proses Pengaduan</h2>
            <p>Setiap pengaduan yang masuk akan diproses sesuai dengan prosedur berikut:</p>
            <ul>
                <li>Pengaduan akan diverifikasi oleh pengurus RT.</li>
                <li>Status pengaduan dapat berupa: Baru, Diproses, Selesai, atau Ditolak.</li>
                <li>Proses penanganan membutuhkan waktu maksimal 3 hari kerja.</li>
                <li>Pengaduan yang tidak sesuai ketentuan dapat ditolak.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-envelope-open-text"></i> 6. Proses Pengajuan Surat</h2>
            <p>Pengajuan surat akan diproses dengan ketentuan:</p>
            <ul>
                <li>Waktu pemrosesan surat adalah 1-3 hari kerja.</li>
                <li>Surat yang telah selesai dapat diambil di sekretariat RT.</li>
                <li>Pengajuan surat yang tidak melengkapi dokumen pendukung dapat ditunda.</li>
                <li>Biaya pembuatan surat (jika ada) akan diinformasikan oleh pengurus RT.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-money-bill-wave"></i> 7. Iuran Warga</h2>
            <p>Ketentuan terkait iuran warga:</p>
            <ul>
                <li>Besaran iuran ditetapkan oleh musyawarah RT.</li>
                <li>Pembayaran iuran dilakukan setiap periode yang telah ditentukan.</li>
                <li>Warga yang terlambat membayar akan dikenakan sanksi sesuai kesepakatan.</li>
                <li>Bukti pembayaran akan diverifikasi oleh pengurus RT.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-database"></i> 8. Hak Kekayaan Intelektual</h2>
            <p>Seluruh konten, fitur, dan kode sistem e-RT Digital adalah hak milik pengembang dan RT 05. Anda tidak diperkenankan untuk menyalin, memodifikasi, atau mendistribusikan sistem tanpa izin tertulis.</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-sync-alt"></i> 9. Perubahan Syarat dan Ketentuan</h2>
            <p>Kami dapat memperbarui Syarat dan Ketentuan ini sewaktu-waktu. Perubahan akan diumumkan melalui sistem atau pengumuman RT. Penggunaan sistem setelah perubahan berlaku menandakan persetujuan Anda terhadap perubahan tersebut.</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-gavel"></i> 10. Hukum yang Berlaku</h2>
            <p>Syarat dan ketentuan ini diatur oleh hukum Negara Republik Indonesia. Segala sengketa yang timbul akan diselesaikan secara musyawarah atau melalui jalur hukum yang berlaku.</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-phone-alt"></i> 11. Hubungi Kami</h2>
            <p>Jika Anda memiliki pertanyaan tentang Syarat dan Ketentuan ini, silakan hubungi:</p>
            <ul>
                <li><strong>Pengurus RT 05</strong></li>
                <li><strong>Alamat:</strong> Jl. Mawar No.5, Sukamaju</li>
                <li><strong>Telepon:</strong> 0812-3456-7890</li>
                <li><strong>Email:</strong> rt05@sukamaju.id</li>
            </ul>
        </div>
    </div>
</div>

<!-- Footer -->
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
</script>

</body>
</html>