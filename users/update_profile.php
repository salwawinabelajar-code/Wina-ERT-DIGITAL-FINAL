<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $pekerjaan = trim($_POST['pekerjaan'] ?? '');
    
    // Validasi
    if (empty($nama) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Nama dan email wajib diisi']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
        exit();
    }
    
    // Cek email duplikat
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email sudah digunakan oleh pengguna lain']);
        mysqli_stmt_close($stmt);
        exit();
    }
    mysqli_stmt_close($stmt);
    
    // Update profil
    $update_query = "UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ?, tempat_lahir = ?, tanggal_lahir = ?, pekerjaan = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sssssssi", $nama, $email, $no_hp, $alamat, $tempat_lahir, $tanggal_lahir, $pekerjaan, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
}

mysqli_close($conn);
?>