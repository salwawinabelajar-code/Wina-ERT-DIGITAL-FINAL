<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die("Akses ditolak! Silakan login terlebih dahulu.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID tidak valid");
}

// Ambil data file dari database
$query = "SELECT foto FROM galeri WHERE id = ? AND tampil = 1";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Error prepare statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data || empty($data['foto'])) {
    die("File tidak ditemukan di database");
}

// Cari file di beberapa kemungkinan lokasi
$base_path = dirname(__DIR__);
$possible_paths = [
    $base_path . '/' . $data['foto'],
    $base_path . '/uploads/galeri/' . basename($data['foto']),
    $base_path . '/uploads/' . basename($data['foto']),
    __DIR__ . '/../' . $data['foto']
];

$file_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $file_path = $path;
        break;
    }
}

if (!$file_path) {
    die("File fisik tidak ditemukan. Path yang dicari: " . implode(', ', $possible_paths));
}

// Dapatkan nama file
$file_name = basename($file_path);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Set header berdasarkan tipe file
switch($file_extension) {
    case 'jpg':
    case 'jpeg':
        $content_type = 'image/jpeg';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
    case 'gif':
        $content_type = 'image/gif';
        break;
    case 'webp':
        $content_type = 'image/webp';
        break;
    default:
        $content_type = 'application/octet-stream';
}

// Kirim file untuk download
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Bersihkan output buffer
while (ob_get_level()) ob_end_clean();

// Baca dan kirim file
readfile($file_path);
exit();
?>