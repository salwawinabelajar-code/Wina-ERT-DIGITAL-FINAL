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
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Semua field password harus diisi']);
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi password baru tidak cocok']);
        exit();
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
        exit();
    }
    
    // Ambil password lama dari database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password lama salah']);
        exit();
    }
    
    // Update password
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $hash, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah password: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
}

mysqli_close($conn);
?>