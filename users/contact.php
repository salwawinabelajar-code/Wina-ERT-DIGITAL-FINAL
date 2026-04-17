<?php
// contact.php - Halaman Kontak
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="description" content="Hubungi kami - e-RT Digital">
    <title>Kontak Kami - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .contact-card {
            background: rgba(235, 244, 221, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(144, 171, 139, 0.4);
            transition: all 0.3s;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            background: rgba(255, 255, 255, 0.95);
        }

        .contact-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #90AB8B, #5A7863);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .contact-icon i {
            font-size: 30px;
            color: white;
        }

        .contact-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }

        .contact-card p {
            color: var(--primary);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .contact-card a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.2s;
        }

        .contact-card a:hover {
            color: var(--primary-light);
        }

        /* Map Section */
        .map-section {
            background: rgba(235, 244, 221, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 50px;
            border: 1px solid rgba(144, 171, 139, 0.4);
        }

        .map-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .map-container {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(144, 171, 139, 0.3);
        }

        .map-container iframe {
            width: 100%;
            height: 300px;
            border: 0;
        }

        .address-detail {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
        }

        .address-detail p {
            color: var(--primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Office Hours */
        .hours-section {
            background: rgba(235, 244, 221, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 50px;
            border: 1px solid rgba(144, 171, 139, 0.4);
        }

        .hours-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .hours-item {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
        }

        .hours-item h4 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }

        .hours-item p {
            color: var(--primary);
            font-size: 0.9rem;
        }

        /* Form Section */
        .form-section {
            background: rgba(235, 244, 221, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid rgba(144, 171, 139, 0.4);
        }

        .form-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(144, 171, 139, 0.4);
            border-radius: 12px;
            background: white;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(144, 171, 139, 0.2);
        }

        .btn-submit {
            background: linear-gradient(135deg, #5A7863, #3B4953);
            color: white;
            padding: 12px 30px;
            border-radius: 40px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

        /* Alert */
        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
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
            .hours-grid {
                grid-template-columns: 1fr;
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
        <a href="about.php">Tentang</a>
        <a href="contact.php" class="active">Kontak</a>
        <a href="auth/login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Masuk</a>
        <a href="auth/register.php" class="btn-register"><i class="fas fa-user-plus"></i> Daftar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Hubungi Kami</h1>
        <p>Ada pertanyaan atau saran? Silakan hubungi kami melalui informasi di bawah ini.</p>
    </div>

    <!-- Contact Cards -->
    <div class="contact-grid">
        <div class="contact-card">
            <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
            <h3>Alamat Kantor</h3>
            <p>Jl. Mawar No. 5, RT 05/RW 03<br>Sukamaju, Kec. Sukajaya<br>Kabupaten Sukamaju</p>
        </div>
        <div class="contact-card">
            <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
            <h3>Telepon</h3>
            <p><a href="tel:081234567890">0812-3456-7890</a><br><a href="tel:02112345678">(021)