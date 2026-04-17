<?php
// about.php - Halaman Tentang Kami
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="description" content="Tentang e-RT Digital - Sistem informasi terpadu untuk pengelolaan administrasi RT">
    <title>Tentang Kami - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Premium Color Palette */
        :root {
            --primary: #5A7863;
            --primary-dark: #3B4953;
            --primary-light: #90AB8B;
            --secondary: #90AB8B;
            --accent: #A8BF9A;
            --bg-soft: #EBF4DD;
            --white: #FFFFFF;
            --dark: #3B4953;
            --gray: #7A8E7A;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 12px 28px rgba(0, 0, 0, 0.12);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #EBF4DD 0%, #90AB8B 50%, #5A7863 100%);
            color: var(--dark);
            overflow-x: hidden;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 40%, rgba(235, 244, 221, 0.25) 0%, rgba(90, 120, 99, 0.15) 100%);
            pointer-events: none;
            z-index: -1;
            animation: softPulse 12s ease-in-out infinite;
        }

        @keyframes softPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(95deg, rgba(90, 120, 99, 0.95), rgba(59, 73, 83, 0.95));
            backdrop-filter: blur(16px);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: var(--shadow-md);
            border-bottom: 1px solid rgba(235, 244, 221, 0.3);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            background: linear-gradient(135deg, #EBF4DD, #90AB8B);
            width: 45px;
            height: 45px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 22px;
            box-shadow: var(--shadow-sm);
        }

        .logo-text h1 {
            font-size: 1.5rem;
            color: #FFFFFF;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .logo-text p {
            font-size: 0.7rem;
            color: rgba(235, 244, 221, 0.85);
        }

        .nav-menu {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
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
        }

        .nav-menu a:hover {
            background: #EBF4DD;
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-login {
            background: rgba(235, 244, 221, 0.25) !important;
        }

        .btn-login:hover {
            background: #EBF4DD !important;
        }

        .btn-register {
            background: #90AB8B !important;
        }

        .btn-register:hover {
            background: #5A7863 !important;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header p {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            max-width: 600px;
            margin: 0 auto;
        }

        /* About Cards */
        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .about-card {
            background: rgba(235, 244, 221, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid rgba(144, 171, 139, 0.4);
            transition: all 0.3s;
            text-align: center;
        }

        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            background: rgba(255, 255, 255, 0.95);
        }

        .about-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #90AB8B, #5A7863);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .about-icon i {
            font-size: 32px;
            color: white;
        }

        .about-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 15px;
        }

        .about-card p {
            color: var(--primary);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Visi Misi */
        .visi-misi {
            background: rgba(235, 244, 221, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 50px;
            border: 1px solid rgba(144, 171, 139, 0.4);
        }

        .visi-misi h2 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 30px;
        }

        .visi-box, .misi-box {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .visi-box h3, .misi-box h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .visi-box p {
            color: var(--primary);
            line-height: 1.6;
        }

        .misi-list {
            list-style: none;
            padding-left: 0;
        }

        .misi-list li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--primary);
        }

        .misi-list i {
            color: var(--success);
            font-size: 1rem;
            width: 20px;
        }

        /* Team Section */
        .team-section {
            margin-bottom: 50px;
        }

        .team-section h2 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 30px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .team-card {
            background: rgba(235, 244, 221, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(144, 171, 139, 0.4);
            transition: all 0.3s;
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #90AB8B, #5A7863);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .team-avatar i {
            font-size: 48px;
            color: white;
        }

        .team-card h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .team-card p {
            font-size: 0.85rem;
            color: var(--primary);
        }

        /* CTA */
        .cta-box {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            margin-top: 30px;
        }

        .cta-box h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }

        .cta-box p {
            color: rgba(235, 244, 221, 0.9);
            margin-bottom: 25px;
        }

        .btn-cta {
            background: #EBF4DD;
            color: var(--primary-dark);
            padding: 12px 30px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: white;
        }

        /* Footer */
        .footer {
            background: #3B4953;
            padding: 30px 5% 20px;
            text-align: center;
            color: rgba(235, 244, 221, 0.8);
            margin-top: 40px;
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

        .footer-links {
            display: flex;
            gap: 20px;
        }

        .footer-links a {
            color: rgba(235, 244, 221, 0.7);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .footer-links a:hover {
            color: #EBF4DD;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            .page-header h1 {
                font-size: 2rem;
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
        <a href="landingpage.php">Beranda</a>
        <a href="about.php" class="active">Tentang</a>
        <a href="contact.php">Kontak</a>
        <a href="auth/login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Masuk</a>
        <a href="auth/register.php" class="btn-register"><i class="fas fa-user-plus"></i> Daftar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Tentang e-RT Digital</h1>
        <p>Sistem informasi terpadu untuk memudahkan pengelolaan administrasi RT secara modern, cepat, dan transparan.</p>
    </div>

    <!-- About Grid -->
    <div class="about-grid">
        <div class="about-card">
            <div class="about-icon"><i class="fas fa-bullseye"></i></div>
            <h3>Misi Kami</h3>
            <p>Menciptakan lingkungan RT yang lebih baik melalui digitalisasi layanan administrasi dan komunikasi warga.</p>
        </div>
        <div class="about-card">
            <div class="about-icon"><i class="fas fa-eye"></i></div>
            <h3>Visi Kami</h3>
            <p>Menjadi platform digital terdepan untuk pengelolaan RT yang transparan, efisien, dan partisipatif.</p>
        </div>
        <div class="about-card">
            <div class="about-icon"><i class="fas fa-heart"></i></div>
            <h3>Nilai Kami</h3>
            <p>Transparansi, kemudahan akses, kecepatan layanan, dan kepedulian terhadap lingkungan warga.</p>
        </div>
    </div>

    <!-- Visi Misi Detail -->
    <div class="visi-misi">
        <h2>Visi & Misi</h2>
        <div class="visi-box">
            <h3><i class="fas fa-quote-left"></i> Visi</h3>
            <p>Terwujudnya lingkungan RT yang modern, terintegrasi, dan sejahtera melalui pemanfaatan teknologi digital yang mudah diakses oleh seluruh warga.</p>
        </div>
        <div class="misi-box">
            <h3><i class="fas fa-list-check"></i> Misi</h3>
            <ul class="misi-list">
                <li><i class="fas fa-check-circle"></i> Menyediakan layanan pengaduan online yang cepat dan responsif</li>
                <li><i class="fas fa-check-circle"></i> Mempermudah warga dalam mengajukan surat keterangan</li>
                <li><i class="fas fa-check-circle"></i> Meningkatkan transparansi pengelolaan iuran warga</li>
                <li><i class="fas fa-check-circle"></i> Membangun komunikasi yang efektif antara warga dan pengurus RT</li>
                <li><i class="fas fa-check-circle"></i> Mendokumentasikan kegiatan warga dalam galeri digital</li>
            </ul>
        </div>
    </div>

    <!-- Team Section -->
    <div class="team-section">
        <h2>Pengurus RT 05</h2>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-avatar"><i class="fas fa-user-tie"></i></div>
                <h4>Bapak RT</h4>
                <p>Ketua RT 05</p>
            </div>
            <div class="team-card">
                <div class="team-avatar"><i class="fas fa-user"></i></div>
                <h4>Sekretaris</h4>
                <p>Pengelola Administrasi</p>
            </div>
            <div class="team-card">
                <div class="team-avatar"><i class="fas fa-calculator"></i></div>
                <h4>Bendahara</h4>
                <p>Pengelola Keuangan</p>
            </div>
            <div class="team-card">
                <div class="team-avatar"><i class="fas fa-laptop-code"></i></div>
                <h4>Admin Sistem</h4>
                <p>Pengelola Teknis</p>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="cta-box">
        <h3>Siap Bergabung?</h3>
        <p>Daftarkan diri Anda sekarang dan nikmati kemudahan layanan digital RT.</p>
        <a href="auth/register.php" class="btn-cta"><i class="fas fa-user-plus"></i> Daftar Sekarang</a>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> e-RT Digital - Sistem Informasi RT 05 Sukamaju</p>
        <div class="footer-links">
            <a href="about.php">Tentang</a>
            <a href="contact.php">Kontak</a>
            <a href="privacy.php">Privasi</a>
            <a href="terms.php">Syarat & Ketentuan</a>
        </div>
    </div>
</footer>

</body>
</html>