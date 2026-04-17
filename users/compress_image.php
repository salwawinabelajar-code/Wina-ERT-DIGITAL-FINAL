<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'warga';

// Hanya admin yang bisa mengakses kompresi gambar
if ($user_role !== 'admin') {
    die("Akses ditolak!");
}

// Fungsi kompres gambar tanpa GD Library (copy file as-is)
function compressImage($source, $destination, $quality = 80) {
    // Cek apakah GD Library tersedia
    if (extension_loaded('gd') && function_exists('imagecreatefromjpeg')) {
        // Gunakan GD Library jika tersedia
        $info = getimagesize($source);
        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            imagedestroy($image);
            return true;
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
            imagepng($image, $destination, 9);
            imagedestroy($image);
            return true;
        }
    }
    
    // Jika GD Library tidak tersedia, copy file as-is
    return copy($source, $destination);
}

// Cek apakah ada file yang diupload
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambar'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $file_type = mime_content_type($file['tmp_name']);
    $file_size = $file['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error_message'] = "Format file tidak didukung. Gunakan: JPG, PNG, GIF, WEBP";
        header("Location: galeri.php");
        exit();
    }
    
    if ($file_size > $max_size) {
        $_SESSION['error_message'] = "Ukuran file terlalu besar. Maksimal 5MB";
        header("Location: galeri.php");
        exit();
    }
    
    // Buat nama file unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'galeri_' . time() . '_' . uniqid() . '.' . $extension;
    
    // Path penyimpanan
    $upload_dir = '../uploads/galeri/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $target_file = $upload_dir . $filename;
    
    // Kompres atau copy file
    if (compressImage($file['tmp_name'], $target_file, 80)) {
        // Simpan ke database
        $judul = isset($_POST['judul']) ? mysqli_real_escape_string($conn, $_POST['judul']) : '';
        $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($conn, $_POST['deskripsi']) : '';
        
        $query = "INSERT INTO galeri (user_id, judul, deskripsi, filename, file_path, created_at) 
                  VALUES ('$user_id', '$judul', '$deskripsi', '$filename', 'uploads/galeri/$filename', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success_message'] = "Gambar berhasil diupload!";
        } else {
            $_SESSION['error_message'] = "Gagal menyimpan ke database: " . mysqli_error($conn);
            unlink($target_file);
        }
    } else {
        $_SESSION['error_message'] = "Gagal mengupload gambar.";
    }
    
    header("Location: galeri.php");
    exit();
}

// Jika tidak ada file yang diupload
$_SESSION['error_message'] = "Silakan pilih file gambar terlebih dahulu.";
header("Location: galeri.php");
exit();
?>