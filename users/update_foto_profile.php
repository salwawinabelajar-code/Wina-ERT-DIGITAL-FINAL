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
    if (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== 0) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload']);
        exit();
    }
    
    $maxFileSize = 2 * 1024 * 1024; // 2MB
    
    if ($_FILES['foto_profil']['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB']);
        exit();
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $file_type = mime_content_type($_FILES['foto_profil']['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF']);
        exit();
    }
    
    // Ambil foto profil lama
    $query = "SELECT foto_profil FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $file_extension = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
    $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
    $upload_dir = '../uploads/avatars/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $target_file = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
        // Hapus foto lama jika ada
        if (!empty($user['foto_profil']) && file_exists('../' . $user['foto_profil'])) {
            unlink('../' . $user['foto_profil']);
        }
        
        $foto_path = 'uploads/avatars/' . $new_filename;
        $update_query = "UPDATE users SET foto_profil = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $foto_path, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Foto profil berhasil diupload', 'foto_path' => $foto_path]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan foto profil']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
}

mysqli_close($conn);
?>