<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

echo "<h2>Debug Foto Pengaduan</h2>";

// Cek folder uploads
$upload_folder = $_SERVER['DOCUMENT_ROOT'] . '/pengaduan/uploads/pengaduan/';
echo "<h3>1. Cek Folder Uploads</h3>";
echo "Path folder: " . $upload_folder . "<br>";
echo "Folder exists: " . (file_exists($upload_folder) ? 'YES' : 'NO') . "<br>";

if (!file_exists($upload_folder)) {
    mkdir($upload_folder, 0777, true);
    echo "Folder telah dibuat!<br>";
}

// List file di folder
echo "<h3>2. File dalam folder uploads/pengaduan/</h3>";
if (is_dir($upload_folder)) {
    $files = scandir($upload_folder);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "Folder tidak ditemukan!<br>";
}

// Ambil data dari database
echo "<h3>3. Data Foto dari Database</h3>";
$query = "SELECT id, judul, foto FROM pengaduan WHERE foto IS NOT NULL AND foto != ''";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr bgcolor='#CCCCCC'><th>ID</th><th>Judul</th><th>Nama File</th><th>Path Absolut</th><th>File Exists?</th><th>Preview</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $foto = $row['foto'];
        $path_abs = $upload_folder . $foto;
        $path_url = '/pengaduan/uploads/pengaduan/' . $foto;
        $exists = file_exists($path_abs);
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['judul']}</td>";
        echo "<td>$foto</td>";
        echo "<td>$path_abs</td>";
        echo "<td style='color:" . ($exists ? 'green' : 'red') . "'>" . ($exists ? 'YES' : 'NO') . "</td>";
        echo "<td>";
        if ($exists) {
            echo "<img src='$path_url' width='100' style='border-radius:8px;'>";
        } else {
            echo "<span style='color:red'>File tidak ditemukan!</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data foto di database.<br>";
}

// Perbaiki path jika perlu
echo "<h3>4. Perbaikan Path (opsional)</h3>";
$query_fix = "SELECT id, foto FROM pengaduan WHERE foto IS NOT NULL AND foto != ''";
$result_fix = mysqli_query($conn, $query_fix);
$fixed = 0;

while ($row = mysqli_fetch_assoc($result_fix)) {
    $old_foto = $row['foto'];
    // Hanya ambil nama file jika path-nya mengandung folder
    if (strpos($old_foto, '/') !== false || strpos($old_foto, '\\') !== false) {
        $new_foto = basename($old_foto);
        if ($old_foto != $new_foto) {
            $update = "UPDATE pengaduan SET foto = '$new_foto' WHERE id = " . $row['id'];
            if (mysqli_query($conn, $update)) {
                echo "ID {$row['id']}: $old_foto → $new_foto (diperbaiki)<br>";
                $fixed++;
            }
        }
    }
}

if ($fixed == 0) {
    echo "Tidak ada path yang perlu diperbaiki.<br>";
}

echo "<hr>";
echo "<h3>5. Solusi</h3>";
echo "<p>Jika file tidak ditemukan, pastikan:</p>";
echo "<ol>";
echo "<li>File foto sudah diupload oleh user</li>";
echo "<li>File foto berada di folder: <strong>C:\\xampp\\htdocs\\pengaduan\\uploads\\pengaduan\\</strong></li>";
echo "<li>Nama file di database sesuai dengan nama file di folder</li>";
echo "</ol>";
?>