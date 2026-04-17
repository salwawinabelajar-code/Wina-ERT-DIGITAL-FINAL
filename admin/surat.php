<?php
session_start();
require_once(__DIR__ . '/../config/db.php');

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Pastikan kolom catatan_admin ada
$check_catatan = mysqli_query($conn, "SHOW COLUMNS FROM pengajuan_surat LIKE 'catatan_admin'");
if (mysqli_num_rows($check_catatan) == 0) {
    mysqli_query($conn, "ALTER TABLE pengajuan_surat ADD catatan_admin TEXT DEFAULT NULL AFTER keterangan");
}

// Pastikan kolom file_hasil ada
$check_file_hasil = mysqli_query($conn, "SHOW COLUMNS FROM pengajuan_surat LIKE 'file_hasil'");
if (mysqli_num_rows($check_file_hasil) == 0) {
    mysqli_query($conn, "ALTER TABLE pengajuan_surat ADD file_hasil VARCHAR(255) DEFAULT NULL AFTER file_pendukung");
}

// Pastikan kolom nomor_surat ada
$check_nomor_surat = mysqli_query($conn, "SHOW COLUMNS FROM pengajuan_surat LIKE 'nomor_surat'");
if (mysqli_num_rows($check_nomor_surat) == 0) {
    mysqli_query($conn, "ALTER TABLE pengajuan_surat ADD nomor_surat VARCHAR(50) DEFAULT NULL AFTER jenis_surat");
}

// Pastikan kolom tgl_selesai ada
$check_tgl_selesai = mysqli_query($conn, "SHOW COLUMNS FROM pengajuan_surat LIKE 'tgl_selesai'");
if (mysqli_num_rows($check_tgl_selesai) == 0) {
    mysqli_query($conn, "ALTER TABLE pengajuan_surat ADD tgl_selesai DATETIME DEFAULT NULL AFTER tanggal_pengajuan");
}

// Fungsi untuk mendapatkan template surat
function getTemplateSurat($jenis_surat, $data, $nomor_surat, $tanggal_sekarang, $user_nama, $catatan, $keperluan) {
    $tanggal_format = date('d-m-Y', strtotime($tanggal_sekarang));
    $keperluan_text = !empty($keperluan) ? $keperluan : ($data['keperluan'] ?? '-');
    
    $nik = !empty($data['nik_input']) ? $data['nik_input'] : ($data['nik'] ?? '-');
    $tempat_lahir = !empty($data['tempat_lahir_input']) ? $data['tempat_lahir_input'] : '-';
    $tgl_lahir = !empty($data['tgl_lahir_input']) ? $data['tgl_lahir_input'] : '-';
    $jenis_kelamin = !empty($data['jenis_kelamin_input']) ? $data['jenis_kelamin_input'] : '-';
    $agama = !empty($data['agama_input']) ? $data['agama_input'] : '-';
    $pekerjaan = !empty($data['pekerjaan_input']) ? $data['pekerjaan_input'] : '-';
    $alamat = !empty($data['alamat_input']) ? $data['alamat_input'] : ($data['alamat'] ?? '-');
    
    if ($tgl_lahir != '-' && !empty($tgl_lahir)) {
        $tgl_lahir_formatted = date('d-m-Y', strtotime($tgl_lahir));
    } else {
        $tgl_lahir_formatted = '-';
    }
    
    $ttl = $tempat_lahir;
    if ($tempat_lahir != '-' && $tgl_lahir_formatted != '-') {
        $ttl = $tempat_lahir . ', ' . $tgl_lahir_formatted;
    } elseif ($tempat_lahir != '-') {
        $ttl = $tempat_lahir;
    } elseif ($tgl_lahir_formatted != '-') {
        $ttl = $tgl_lahir_formatted;
    } else {
        $ttl = '-';
    }
    
    $template = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Surat - ' . $data['nama'] . '</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: "Times New Roman", Times, serif; background: #fff; padding: 40px 20px; }
            .surat { max-width: 850px; margin: 0 auto; background: white; padding: 40px; border: 1px solid #ddd; }
            .kop { text-align: center; margin-bottom: 20px; }
            .kop h1 { font-size: 18px; font-weight: bold; margin: 0; }
            .kop h2 { font-size: 16px; font-weight: bold; margin: 5px 0; }
            .kop h3 { font-size: 14px; font-weight: normal; margin: 5px 0; }
            .judul { text-align: center; margin: 30px 0 10px 0; }
            .judul h4 { font-size: 16px; font-weight: bold; text-decoration: underline; }
            .nomor { text-align: center; margin-bottom: 25px; }
            .nomor p { font-size: 13px; }
            .isi { line-height: 1.6; font-size: 13px; text-align: justify; margin: 20px 0; }
            .data-diri { width: 100%; margin: 15px 0; border-collapse: collapse; }
            .data-diri td { padding: 4px 8px; font-size: 13px; vertical-align: top; }
            .data-diri td:first-child { width: 140px; }
            .penutup { margin: 20px 0; font-size: 13px; text-align: justify; line-height: 1.6; }
            .ttd { margin-top: 50px; text-align: right; }
            .ttd p { font-size: 13px; margin: 3px 0; }
            .ttd .nama-ketua { margin-top: 40px; font-weight: bold; text-decoration: underline; }
            .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
            @media print { body { padding: 0; } .surat { border: none; padding: 20px; } }
        </style>
    </head>
    <body>
        <div class="surat">
            <div class="kop">
                <h1>PEMERINTAH KELURAHAN SUKAMAJU</h1>
                <h2>KETUA RT. 05 RW. 03</h2>
                <h3>Kelurahan Sukamaju Kecamatan Sukamaju Kabupaten Sukamaju</h3>
            </div>
            <div class="judul"><h4>SURAT PENGANTAR</h4></div>
            <div class="nomor"><p>No. ' . $nomor_surat . '</p></div>
            <div class="isi"><p>Yang bertanda tangan di bawah ini Ketua RT. 05 RW. 03 Kelurahan Sukamaju Kecamatan Sukamaju Kabupaten Sukamaju dengan ini menerangkan bahwa :</p></div>
            <table class="data-diri">
                <tr>.htmlNama</td><td>: ' . $data['nama'] . '</td></tr>
                <tr><td>Tempat/Tgl. Lahir</td><td>: ' . $ttl . '</td></tr>
                <tr><td>NIK</td><td>: ' . $nik . '</td></tr>
                <tr><td>Jenis Kelamin</td><td>: ' . $jenis_kelamin . '</td></tr>
                <tr><td>Pekerjaan</td><td>: ' . $pekerjaan . '</td></tr>
                <tr><td>Agama</td><td>: ' . $agama . '</td></tr>
                <tr><td>Kewarganegaraan</td><td>: Indonesia</td></tr>
                <tr><td>Alamat</td><td>: ' . $alamat . '</td></tr>
            </table>
            <div class="penutup">
                <p>Orang tersebut diatas, adalah benar-benar warga RT. 05 RW. 03 Kelurahan Sukamaju Kec. Sukamaju Kab. Sukamaju. Surat pengantar ini dibuat sebagai kelengkapan pengurusan <strong>' . $keperluan_text . '</strong>.</p>
                <p>Demikian surat pengantar ini kami buat, untuk dapat dipergunakan sebagaimana mestinya.</p>
            </div>
            <div class="ttd">
                <p>Sukamaju, ' . $tanggal_format . '</p>
                <p>Ketua RT. 05 RW. 03</p>
                <p>Kelurahan Sukamaju</p>
                <br><br><br>
                <p class="nama-ketua">' . strtoupper($user_nama) . '</p>
            </div>
            ' . (!empty($catatan) ? '<div class="footer"><p>Catatan: ' . htmlspecialchars($catatan) . '</p></div>' : '') . '
        </div>
    </body>
    </html>';
    
    return $template;
}

// ========== PROSES BUAT SURAT LANGSUNG ==========
if (isset($_POST['buat_surat_langsung'])) {
    $id = (int)$_POST['id'];
    $nomor_surat = mysqli_real_escape_string($conn, $_POST['nomor_surat']);
    $catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : '';
    $jenis_surat_modal = isset($_POST['jenis_surat_modal']) ? mysqli_real_escape_string($conn, $_POST['jenis_surat_modal']) : 'surat pengantar';
    $keperluan_surat = isset($_POST['keperluan_surat']) ? mysqli_real_escape_string($conn, $_POST['keperluan_surat']) : '';
    
    $nik_input = isset($_POST['nik']) ? mysqli_real_escape_string($conn, $_POST['nik']) : '';
    $tempat_lahir_input = isset($_POST['tempat_lahir']) ? mysqli_real_escape_string($conn, $_POST['tempat_lahir']) : '';
    $tgl_lahir_input = isset($_POST['tgl_lahir']) ? mysqli_real_escape_string($conn, $_POST['tgl_lahir']) : '';
    $jenis_kelamin_input = isset($_POST['jenis_kelamin']) ? mysqli_real_escape_string($conn, $_POST['jenis_kelamin']) : '';
    $agama_input = isset($_POST['agama']) ? mysqli_real_escape_string($conn, $_POST['agama']) : '';
    $pekerjaan_input = isset($_POST['pekerjaan']) ? mysqli_real_escape_string($conn, $_POST['pekerjaan']) : '';
    $alamat_input = isset($_POST['alamat']) ? mysqli_real_escape_string($conn, $_POST['alamat']) : '';
    
    $query = "SELECT s.*, u.nama, u.alamat, u.no_hp, u.email, u.nik 
              FROM pengajuan_surat s 
              JOIN users u ON s.user_id = u.id 
              WHERE s.id = $id";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    
    $data['nik_input'] = $nik_input;
    $data['tempat_lahir_input'] = $tempat_lahir_input;
    $data['tgl_lahir_input'] = $tgl_lahir_input;
    $data['jenis_kelamin_input'] = $jenis_kelamin_input;
    $data['agama_input'] = $agama_input;
    $data['pekerjaan_input'] = $pekerjaan_input;
    $data['alamat_input'] = $alamat_input;
    
    if ($data) {
        $tanggal_sekarang = date('d F Y');
        $html_template = getTemplateSurat($jenis_surat_modal, $data, $nomor_surat, $tanggal_sekarang, $user['nama'], $catatan, $keperluan_surat);
        
        $upload_dir = '../uploads/surat_hasil/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = 'surat_' . $id . '_' . time() . '.html';
        $file_path = $upload_dir . $filename;
        $file_path_db = '../uploads/surat_hasil/' . $filename;
        
        file_put_contents($file_path, $html_template);
        
        $update = "UPDATE pengajuan_surat SET 
                   file_hasil = '$file_path_db',
                   nomor_surat = '$nomor_surat',
                   catatan_admin = '$catatan',
                   status = 'selesai',
                   tgl_selesai = NOW()
                   WHERE id = $id";
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['success_message'] = "Surat berhasil dibuat!";
        } else {
            $_SESSION['error_message'] = "Gagal menyimpan: " . mysqli_error($conn);
        }
        
        header("Location: surat.php");
        exit();
    }
}

// ========== PROSES UPLOAD FILE HASIL SURAT ==========
if (isset($_POST['upload_hasil'])) {
    $id = (int)$_POST['id'];
    $nomor_surat = mysqli_real_escape_string($conn, $_POST['nomor_surat']);
    $catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : '';
    
    if (isset($_FILES['file_hasil']) && $_FILES['file_hasil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file_hasil'];
        $allowed_extensions = ['pdf', 'doc', 'docx', 'html'];
        $max_size = 10 * 1024 * 1024;
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_size = $file['size'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error_message'] = "Format file tidak didukung. Gunakan: PDF, DOC, DOCX, HTML";
        } elseif ($file_size > $max_size) {
            $_SESSION['error_message'] = "Ukuran file terlalu besar. Maksimal 10MB";
        } else {
            $file_name = 'hasil_surat_' . time() . '_' . $id . '.' . $file_extension;
            $upload_dir = '../uploads/surat_hasil/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $query = "UPDATE pengajuan_surat SET 
                          file_hasil = '$file_path',
                          nomor_surat = '$nomor_surat',
                          catatan_admin = '$catatan',
                          status = 'selesai',
                          tgl_selesai = NOW()
                          WHERE id = $id";
                if (mysqli_query($conn, $query)) {
                    $_SESSION['success_message'] = "File hasil surat berhasil diupload!";
                } else {
                    $_SESSION['error_message'] = "Gagal menyimpan ke database: " . mysqli_error($conn);
                    unlink($file_path);
                }
            } else {
                $_SESSION['error_message'] = "Gagal mengupload file.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Silakan pilih file hasil surat.";
    }
    header("Location: surat.php");
    exit();
}

// ========== PROSES UPDATE STATUS ==========
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : '';
    
    $query = "UPDATE pengajuan_surat SET status='$status'";
    if (!empty($catatan)) {
        $query .= ", catatan_admin='$catatan'";
    }
    if ($status == 'selesai') {
        $query .= ", tgl_selesai=NOW()";
    }
    $query .= " WHERE id=$id";
    mysqli_query($conn, $query);
    
    header("Location: surat.php?" . $_SERVER['QUERY_STRING']);
    exit();
}

// ========== PROSES HAPUS ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $q = mysqli_query($conn, "SELECT file_pendukung, file_hasil FROM pengajuan_surat WHERE id=$id");
    $data = mysqli_fetch_assoc($q);
    if ($data) {
        if (!empty($data['file_pendukung'])) {
            $file = '../uploads/surat/' . $data['file_pendukung'];
            if (file_exists($file)) unlink($file);
        }
        if (!empty($data['file_hasil'])) {
            $file = $data['file_hasil'];
            if (file_exists($file)) unlink($file);
        }
    }
    mysqli_query($conn, "DELETE FROM pengajuan_surat WHERE id=$id");
    header("Location: surat.php?" . $_SERVER['QUERY_STRING']);
    exit();
}

// ========== PROSES EXPORT EXCEL ==========
if (isset($_GET['export_excel'])) {
    $filter_status_export = isset($_GET['status']) ? $_GET['status'] : '';
    $search_export = isset($_GET['search']) ? $_GET['search'] : '';
    $start_date_export = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date_export = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    $where_export = "1=1";
    if (!empty($filter_status_export)) {
        $where_export .= " AND s.status = '$filter_status_export'";
    }
    if (!empty($search_export)) {
        $search_export = mysqli_real_escape_string($conn, $search_export);
        $where_export .= " AND (s.jenis_surat LIKE '%$search_export%' OR s.keperluan LIKE '%$search_export%')";
    }
    if (!empty($start_date_export)) {
        $where_export .= " AND DATE(s.tanggal_pengajuan) >= '$start_date_export'";
    }
    if (!empty($end_date_export)) {
        $where_export .= " AND DATE(s.tanggal_pengajuan) <= '$end_date_export'";
    }
    
    $query_export = "SELECT s.id, u.nama, s.jenis_surat, s.keperluan, s.tanggal_pengajuan, s.status, s.catatan_admin, s.nomor_surat, s.tgl_selesai
                     FROM pengajuan_surat s 
                     JOIN users u ON s.user_id = u.id 
                     WHERE $where_export 
                     ORDER BY s.tanggal_pengajuan DESC";
    $result_export = mysqli_query($conn, $query_export);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan_surat_' . date('Y-m-d') . '.xls"');
    
    echo '<html><head><meta charset="UTF-8"><title>Laporan Surat</title></head><body>';
    echo '<h2>Laporan Pengajuan Surat</h2>';
    echo '<p>Periode: ' . (!empty($start_date_export) ? $start_date_export : 'Semua') . ' - ' . (!empty($end_date_export) ? $end_date_export : 'Semua') . '</p>';
    echo '<p>Status: ' . (!empty($filter_status_export) ? ucfirst($filter_status_export) : 'Semua') . '</p>';
    echo '<p>Tanggal Cetak: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<table border="1"><thead><tr bgcolor="#CCCCCC"><th>No</th><th>ID</th><th>Warga</th><th>Jenis Surat</th><th>Keperluan</th><th>Tgl Pengajuan</th><th>Status</th><th>No Surat</th><th>Tgl Selesai</th><th>Catatan</th></tr></thead><tbody>';
    $no = 1;
    while ($row = mysqli_fetch_assoc($result_export)) {
        echo "<tr>";
        echo "<td>{$no}</td>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_surat']) . "</td>";
        echo "<td>" . htmlspecialchars($row['keperluan']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($row['tanggal_pengajuan'])) . "</td>";
        echo "<td>" . ucfirst($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nomor_surat'] ?? '-') . "</td>";
        echo "<td>" . ($row['tgl_selesai'] ? date('d/m/Y', strtotime($row['tgl_selesai'])) : '-') . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['catatan_admin'] ?? '', 0, 50)) . "</td>";
        echo "</tr>";
        $no++;
    }
    echo '</tbody><tr><p><strong>Total Data: ' . ($no-1) . ' pengajuan</strong></p></body></html>';
    exit();
}

// ========== PROSES PRINT LAPORAN ==========
if (isset($_GET['print_report'])) {
    $filter_status_print = isset($_GET['status']) ? $_GET['status'] : '';
    $search_print = isset($_GET['search']) ? $_GET['search'] : '';
    $start_date_print = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date_print = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    $where_print = "1=1";
    if (!empty($filter_status_print)) {
        $where_print .= " AND s.status = '$filter_status_print'";
    }
    if (!empty($search_print)) {
        $search_print = mysqli_real_escape_string($conn, $search_print);
        $where_print .= " AND (s.jenis_surat LIKE '%$search_print%' OR s.keperluan LIKE '%$search_print%')";
    }
    if (!empty($start_date_print)) {
        $where_print .= " AND DATE(s.tanggal_pengajuan) >= '$start_date_print'";
    }
    if (!empty($end_date_print)) {
        $where_print .= " AND DATE(s.tanggal_pengajuan) <= '$end_date_print'";
    }
    
    $query_print = "SELECT s.id, u.nama, s.jenis_surat, s.keperluan, s.tanggal_pengajuan, s.status, s.catatan_admin, s.nomor_surat, s.tgl_selesai
                    FROM pengajuan_surat s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE $where_print 
                    ORDER BY s.tanggal_pengajuan DESC";
    $result_print = mysqli_query($conn, $query_print);
    $data_print = [];
    while ($row = mysqli_fetch_assoc($result_print)) $data_print[] = $row;
    $total_data = count($data_print);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Laporan Surat</title><meta charset="UTF-8">
    <style>
        body{font-family:Arial;margin:20px}
        h2{text-align:center;color:#213C51}
        table{width:100%;border-collapse:collapse;margin-top:20px}
        th,td{border:1px solid #ddd;padding:8px;text-align:left}
        th{background:#213C51;color:white}
        .info{margin-bottom:20px;padding:10px;background:#f5f5f5;border-radius:5px}
        .footer{margin-top:30px;text-align:right;font-size:12px}
        @media print{.no-print{display:none}}
    </style>
    </head>
    <body>
        <div class="no-print" style="text-align:center;margin-bottom:20px;">
            <button onclick="window.print()" style="padding:10px 20px;background:#213C51;color:white;border:none;border-radius:5px;cursor:pointer;">Cetak</button>
            <button onclick="window.close()" style="padding:10px 20px;background:#666;color:white;border:none;border-radius:5px;margin-left:10px;">Tutup</button>
        </div>
        <h2>LAPORAN PENGAJUAN SURAT</h2>
        <div class="info">
            <strong>Periode:</strong> <?php echo !empty($start_date_print) ? date('d/m/Y', strtotime($start_date_print)) : 'Semua'; ?> - <?php echo !empty($end_date_print) ? date('d/m/Y', strtotime($end_date_print)) : 'Semua'; ?><br>
            <strong>Status:</strong> <?php echo !empty($filter_status_print) ? ucfirst($filter_status_print) : 'Semua'; ?><br>
            <strong>Tanggal Cetak:</strong> <?php echo date('d/m/Y H:i:s'); ?>
        </div>
        <table>
            <thead>
                <tr><th>No</th><th>Warga</th><th>Jenis Surat</th><th>Keperluan</th><th>Tgl Pengajuan</th><th>Status</th><th>No Surat</th><th>Catatan</th></tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($data_print as $row): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                    <td><?php echo htmlspecialchars($row['jenis_surat']); ?></td>
                    <td><?php echo htmlspecialchars($row['keperluan']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['nomor_surat'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['catatan_admin'] ?? '', 0, 50)); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if ($total_data == 0): ?>
                <tr><td colspan="8" style="text-align:center;">Tidak ada数据</td?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="footer">
            <p>Total Data: <?php echo $total_data; ?> pengajuan</p>
            <p>Dicetak oleh: <?php echo htmlspecialchars($user['nama']); ?></p>
        </div>
    </body>
    </html>
    <?php exit();
}

// Ambil pesan dari session dan hapus
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// ========== FILTER DAN PAGINATION ==========
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// TABEL ATAS: SURAT AKTIF
$where_aktif = "s.status IN ('menunggu', 'diproses')";
if (!empty($filter_status)) $where_aktif .= " AND s.status = '$filter_status'";
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where_aktif .= " AND (s.jenis_surat LIKE '%$search%' OR s.keperluan LIKE '%$search%' OR u.nama LIKE '%$search%')";
}
if (!empty($start_date)) $where_aktif .= " AND DATE(s.tanggal_pengajuan) >= '$start_date'";
if (!empty($end_date)) $where_aktif .= " AND DATE(s.tanggal_pengajuan) <= '$end_date'";

$page_aktif = isset($_GET['page_aktif']) ? max(1, (int)$_GET['page_aktif']) : 1;
$limit_aktif = 10;
$offset_aktif = ($page_aktif - 1) * $limit_aktif;

$total_aktif_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan_surat s JOIN users u ON s.user_id = u.id WHERE $where_aktif");
$total_aktif = mysqli_fetch_assoc($total_aktif_result)['total'];
$total_pages_aktif = ceil($total_aktif / $limit_aktif);

$query_aktif = "SELECT s.*, u.nama FROM pengajuan_surat s JOIN users u ON s.user_id = u.id WHERE $where_aktif ORDER BY FIELD(s.status, 'menunggu', 'diproses'), s.tanggal_pengajuan ASC LIMIT $offset_aktif, $limit_aktif";
$result_aktif = mysqli_query($conn, $query_aktif);

// TABEL BAWAH: RIWAYAT SURAT
$where_riwayat = "s.status IN ('selesai', 'ditolak')";
if (!empty($filter_status)) $where_riwayat .= " AND s.status = '$filter_status'";
if (!empty($search)) $where_riwayat .= " AND (s.jenis_surat LIKE '%$search%' OR s.keperluan LIKE '%$search%' OR u.nama LIKE '%$search%')";
if (!empty($start_date)) $where_riwayat .= " AND DATE(s.tanggal_pengajuan) >= '$start_date'";
if (!empty($end_date)) $where_riwayat .= " AND DATE(s.tanggal_pengajuan) <= '$end_date'";

$page_riwayat = isset($_GET['page_riwayat']) ? max(1, (int)$_GET['page_riwayat']) : 1;
$limit_riwayat = 10;
$offset_riwayat = ($page_riwayat - 1) * $limit_riwayat;

$total_riwayat_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan_surat s JOIN users u ON s.user_id = u.id WHERE $where_riwayat");
$total_riwayat = mysqli_fetch_assoc($total_riwayat_result)['total'];
$total_pages_riwayat = ceil($total_riwayat / $limit_riwayat);

$query_riwayat = "SELECT s.*, u.nama FROM pengajuan_surat s JOIN users u ON s.user_id = u.id WHERE $where_riwayat ORDER BY s.tanggal_pengajuan DESC LIMIT $offset_riwayat, $limit_riwayat";
$result_riwayat = mysqli_query($conn, $query_riwayat);

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#213C51">
    <title>Kelola Surat - Admin e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #213C51; --primary-light: #2A4D67; --secondary: #6594B1;
            --success: #10B981; --danger: #EF4444; --warning: #F59E0B;
            --gray-50: #F9FAFB; --gray-100: #F3F4F6; --gray-200: #E5E7EB;
            --gray-300: #D1D5DB; --gray-400: #9CA3AF; --gray-500: #6B7280;
            --gray-600: #4B5563; --gray-700: #374151; --gray-800: #1F2937;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, var(--gray-100) 0%, #e8edf2 100%); display: flex; min-height: 100vh; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        
        .sidebar { width: 280px; background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%); position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; display: flex; flex-direction: column; overflow-y: auto; box-shadow: var(--shadow-xl); }
        .sidebar .logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar .logo-icon { width: 40px; height: 40px; background: var(--secondary); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
        .sidebar .logo-text h2 { font-size: 18px; font-weight: 700; color: white; }
        .sidebar .logo-text p { font-size: 12px; color: rgba(255,255,255,0.6); }
        .sidebar .nav-menu { flex: 1; padding: 0 12px; }
        .sidebar .nav-menu a { display: flex; align-items: center; gap: 12px; padding: 12px; margin-bottom: 4px; color: rgba(255,255,255,0.7); text-decoration: none; border-radius: 10px; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .sidebar .nav-menu a:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .sidebar .nav-menu a.active { background: var(--secondary); color: white; }
        .sidebar .user-profile { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto; }
        .sidebar .user-profile a { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .user-profile .avatar { width: 40px; height: 40px; background: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .sidebar .user-profile .info h4 { font-size: 14px; font-weight: 600; color: white; }
        .sidebar .user-profile .info p { font-size: 12px; color: rgba(255,255,255,0.6); }
        .sidebar .logout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; background: rgba(239,68,68,0.2); color: #FCA5A5; padding: 10px; border-radius: 8px; text-decoration: none; margin: 12px; }
        .sidebar .logout-btn:hover { background: var(--danger); color: white; }
        
        .main-content { flex: 1; margin-left: 280px; padding: 24px 32px; width: calc(100% - 280px); }
        .container { max-width: 1600px; width: 100%; margin: 0 auto; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; animation: fadeInLeft 0.5s ease-out; }
        .page-header-left { display: flex; align-items: center; gap: 15px; }
        .page-header-left i { font-size: 32px; color: var(--secondary); animation: pulse 2s infinite; }
        .page-header-left h1 { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .back-btn { background: var(--secondary); color: white; padding: 10px 24px; border-radius: 40px; text-decoration: none; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .back-btn:hover { background: var(--primary); transform: translateY(-3px); }
        
        .card-section { background: white; border-radius: 24px; padding: 28px; margin-bottom: 32px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-md); transition: all 0.3s; animation: fadeInUp 0.6s ease-out; animation-fill-mode: both; }
        .card-section:nth-child(1) { animation-delay: 0.1s; }
        .card-section:nth-child(2) { animation-delay: 0.2s; }
        .card-section:hover { transform: translateY(-5px); box-shadow: var(--shadow-xl); }
        
        .section-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--gray-200); }
        .section-title h2 { font-size: 22px; font-weight: 700; color: var(--gray-800); display: flex; align-items: center; gap: 10px; }
        .badge-count { background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: white; font-size: 13px; font-weight: 600; padding: 4px 12px; border-radius: 30px; }
        
        .filter-section { background: linear-gradient(135deg, var(--gray-50) 0%, white 100%); border-radius: 20px; padding: 24px; margin-bottom: 24px; border: 1px solid var(--gray-200); }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { flex: 1 1 180px; min-width: 150px; }
        .filter-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 12px; color: var(--gray-600); text-transform: uppercase; }
        .filter-group input, .filter-group select { width: 100%; padding: 10px 14px; border: 1px solid var(--gray-300); border-radius: 12px; font-size: 14px; }
        .btn-filter { background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: white; padding: 10px 14px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn-reset { background: var(--gray-100); color: var(--gray-600); border: 1px solid var(--gray-300); padding: 10px 14px; border-radius: 12px; text-decoration: none; display: inline-block; text-align: center; }
        .export-section { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--gray-200); display: flex; gap: 15px; justify-content: flex-end; }
        .btn-excel { background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; border: none; padding: 10px 24px; border-radius: 40px; font-weight: 600; cursor: pointer; }
        .btn-print { background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%); color: white; border: none; padding: 10px 24px; border-radius: 40px; font-weight: 600; cursor: pointer; }
        
        .table-container { background: white; border-radius: 20px; overflow-x: auto; border: 1px solid var(--gray-200); }
        table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1100px; }
        th { background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%); padding: 18px 16px; text-align: left; font-weight: 700; font-size: 14px; border-bottom: 2px solid var(--gray-200); }
        td { padding: 16px; border-bottom: 1px solid var(--gray-100); font-size: 14px; vertical-align: middle; }
        tr:hover td { background: linear-gradient(90deg, rgba(101,148,177,0.05) 0%, rgba(101,148,177,0.1) 100%); }
        
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 700; }
        .status-menunggu { background: #FEF3C7; color: #D97706; }
        .status-diproses { background: #DBEAFE; color: #2563EB; }
        .status-selesai { background: #D1FAE5; color: #059669; }
        .status-ditolak { background: #FEE2E2; color: #DC2626; }
        
        .action-form { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .status-select { padding: 6px 10px; border: 1px solid var(--gray-300); border-radius: 8px; font-size: 12px; cursor: pointer; }
        .btn-delete, .btn-download, .btn-note, .btn-success, .btn-create { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 500; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; }
        .btn-delete { background: #FEE2E2; color: #DC2626; }
        .btn-download { background: #D1FAE5; color: #059669; }
        .btn-note { background: #E5E7EB; color: #4B5563; }
        .btn-success { background: #D1FAE5; color: #059669; }
        .btn-create { background: linear-gradient(135deg, #8B5CF6, #6D28D9); color: white; }
        .btn-delete:hover, .btn-download:hover, .btn-note:hover, .btn-success:hover, .btn-create:hover { transform: translateY(-2px); }
        
        .keperluan-cell { max-width: 320px; white-space: normal; word-wrap: break-word; }
        .catatan-preview { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
        
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap; }
        .page-link { background: white; color: var(--gray-600); text-decoration: none; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--gray-200); }
        .page-link:hover { background: var(--secondary); color: white; }
        .page-link.active { background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: white; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray-500); }
        .empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 28px; padding: 32px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-content h3 { font-size: 22px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .modal-content .form-group { margin-bottom: 24px; }
        .modal-content label { display: block; margin-bottom: 10px; font-weight: 600; font-size: 14px; }
        .modal-content input, .modal-content textarea, .modal-content select { width: 100%; padding: 12px 14px; border: 1px solid var(--gray-300); border-radius: 12px; font-size: 14px; }
        .modal-content textarea { resize: vertical; font-family: monospace; }
        .modal-buttons { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; }
        
        .footer { background: white; border-top: 1px solid var(--gray-200); padding: 24px; margin-top: 40px; text-align: center; border-radius: 20px; }
        
        .menu-toggle { display: none; position: fixed; top: 16px; left: 16px; z-index: 1001; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); border: none; color: white; width: 48px; height: 48px; border-radius: 12px; font-size: 22px; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 99; }
        
        .alert-success { background: #D1FAE5; color: #059669; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #FEE2E2; color: #DC2626; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        @media (max-width: 768px) {
            .menu-toggle { display: flex; align-items: center; justify-content: center; }
            .sidebar { transform: translateX(-100%); z-index: 200; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; width: 100%; padding: 80px 16px 20px 16px; }
            .filter-form { flex-direction: column; }
            .filter-group { width: 100%; }
            .page-header { flex-direction: column; text-align: center; }
            .export-section { justify-content: flex-start; flex-wrap: wrap; }
            .modal-content { width: 95%; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <div class="logo-text"><h2>e-RT Digital</h2><p>Panel Admin</p></div>
        </div>
        <div class="nav-menu">
            <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
            <a href="pengaduan.php"><i class="fas fa-comment-medical"></i> Pengaduan</a>
            <a href="surat.php" class="active"><i class="fas fa-envelope-open-text"></i> Layanan Surat</a>
            <a href="iuran.php"><i class="fas fa-money-bill-wave"></i> Iuran</a>
            <a href="pengumuman.php"><i class="fas fa-bullhorn"></i> Pengumuman</a>
            <a href="kk.php"><i class="fas fa-address-card"></i> Data KK</a>            
            <a href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a>
        </div>
        <div class="user-profile">
            <a href="profil.php">
                <div class="avatar"><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></div>
                <div class="info"><h4><?php echo htmlspecialchars($user['nama']); ?></h4><p><?php echo htmlspecialchars($user['email']); ?></p></div>
            </a>
        </div>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <div class="page-header-left"><i class="fas fa-envelope-open-text"></i><h1>Kelola Pengajuan Surat</h1></div>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- ========== SURAT AKTIF - TABEL INI JANGAN DIHAPUS ========== -->
            <div class="card-section">
                <div class="section-title">
                    <h2><i class="fas fa-spinner fa-pulse"></i> Surat Dalam Proses</h2>
                    <span class="badge-count"><?php echo $total_aktif; ?> Antrean</span>
                </div>

                <div class="filter-section">
                    <form method="GET" class="filter-form" id="filterForm">
                        <input type="hidden" name="page_aktif" value="1">
                        <div class="filter-group"><label>Cari</label><input type="text" name="search" placeholder="Warga / Jenis / Keperluan" value="<?php echo htmlspecialchars($search); ?>"></div>
                        <div class="filter-group"><label>Dari Tanggal</label><input type="date" name="start_date" value="<?php echo $start_date; ?>"></div>
                        <div class="filter-group"><label>Sampai Tanggal</label><input type="date" name="end_date" value="<?php echo $end_date; ?>"></div>
                        <div class="filter-group"><label>Status</label>
                            <select name="status">
                                <option value="">Semua Status</option>
                                <option value="menunggu" <?php if ($filter_status == 'menunggu') echo 'selected'; ?>>Menunggu</option>
                                <option value="diproses" <?php if ($filter_status == 'diproses') echo 'selected'; ?>>Diproses</option>
                                <option value="selesai" <?php if ($filter_status == 'selesai') echo 'selected'; ?>>Selesai</option>
                                <option value="ditolak" <?php if ($filter_status == 'ditolak') echo 'selected'; ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="filter-group"><button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Terapkan</button></div>
                        <div class="filter-group"><a href="surat.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a></div>
                    </form>
                    <div class="export-section">
                        <button type="button" class="btn-excel" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Ekspor ke Excel</button>
                        <button type="button" class="btn-print" onclick="printReport()"><i class="fas fa-print"></i> Cetak Laporan</button>
                    </div>
                </div>

                <div class="table-container">
                    <?php if (mysqli_num_rows($result_aktif) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Warga</th>
                                    <th>Jenis Surat</th>
                                    <th>Keperluan</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>File</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_aktif = $offset_aktif + 1; while ($row = mysqli_fetch_assoc($result_aktif)): ?>
                                <tr>
                                    <td><?php echo $no_aktif++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong><br><small>ID: <?php echo $row['id']; ?></small></td>
                                    <td><?php echo htmlspecialchars($row['jenis_surat']); ?></td>
                                    <td class="keperluan-cell"><?php echo htmlspecialchars(substr($row['keperluan'], 0, 80)); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php if (!empty($row['file_pendukung'])): ?><a href="../uploads/surat/<?php echo $row['file_pendukung']; ?>" target="_blank" class="btn-download"><i class="fas fa-download"></i> Unduh</a><?php else: ?>-<?php endif; ?></td>
                                    <td>
                                        <div class="action-form">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                    <option value="menunggu" <?php if ($row['status'] == 'menunggu') echo 'selected'; ?>>Menunggu</option>
                                                    <option value="diproses" <?php if ($row['status'] == 'diproses') echo 'selected'; ?>>Diproses</option>
                                                    <option value="selesai" <?php if ($row['status'] == 'selesai') echo 'selected'; ?>>Selesai</option>
                                                    <option value="ditolak" <?php if ($row['status'] == 'ditolak') echo 'selected'; ?>>Ditolak</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            <button class="btn-success" onclick="openUploadModal(<?php echo $row['id']; ?>)"><i class="fas fa-upload"></i> Upload</button>
                                            <button class="btn-create" onclick="openBuatSuratModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama']); ?>', '<?php echo htmlspecialchars($row['jenis_surat']); ?>')"><i class="fas fa-file-alt"></i> Buat Surat</button>
                                            <button class="btn-note" onclick="openCatatanModal(<?php echo $row['id']; ?>)"><i class="fas fa-sticky-note"></i> Catatan</button>
                                            <a href="?hapus=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i> Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state"><i class="fas fa-check-circle"></i><p>Tidak ada surat dalam proses.</p></div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages_aktif > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages_aktif; $i++): ?>
                        <a href="?page_aktif=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&status=<?php echo urlencode($filter_status); ?>" class="page-link <?php if ($i == $page_aktif) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ========== RIWAYAT SURAT ========== -->
            <div class="card-section">
                <div class="section-title">
                    <h2><i class="fas fa-history"></i> Riwayat Surat</h2>
                    <span class="badge-count"><?php echo $total_riwayat; ?> Arsip</span>
                </div>

                <div class="table-container">
                    <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Warga</th>
                                    <th>Jenis Surat</th>
                                    <th>Keperluan</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>No. Surat</th>
                                    <th>File</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_riwayat = $offset_riwayat + 1; while ($row = mysqli_fetch_assoc($result_riwayat)): ?>
                                <tr>
                                    <td><?php echo $no_riwayat++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong><br><small>ID: <?php echo $row['id']; ?></small></td>
                                    <td><?php echo htmlspecialchars($row['jenis_surat']); ?></td>
                                    <td class="keperluan-cell"><?php echo htmlspecialchars(substr($row['keperluan'], 0, 80)); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['nomor_surat'] ?? '-'); ?></td>
                                    <td><?php if (!empty($row['file_hasil'])): ?><a href="<?php echo $row['file_hasil']; ?>" target="_blank" class="btn-download"><i class="fas fa-download"></i> Lihat</a><?php else: ?>-<?php endif; ?></td>
                                    <td class="catatan-preview" title="<?php echo htmlspecialchars($row['catatan_admin'] ?? ''); ?>"><?php echo htmlspecialchars(substr($row['catatan_admin'] ?? '', 0, 40)); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state"><i class="fas fa-archive"></i><p>Belum ada riwayat surat.</p></div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages_riwayat > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages_riwayat; $i++): ?>
                        <a href="?page_riwayat=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&status=<?php echo urlencode($filter_status); ?>" class="page-link <?php if ($i == $page_riwayat) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- Modal Upload Hasil -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-file-pdf"></i> Upload Hasil Surat</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="upload_id">
                <div class="form-group"><label>Nomor Surat</label><input type="text" name="nomor_surat" id="nomor_surat" placeholder="Contoh: 470/RT05/III/2025" required></div>
                <div class="form-group"><label>File Hasil (PDF/DOC/DOCX/HTML)</label><input type="file" name="file_hasil" accept=".pdf,.doc,.docx,.html" required></div>
                <div class="form-group"><label>Catatan Admin</label><textarea name="catatan" id="upload_catatan" rows="3"></textarea></div>
                <div class="modal-buttons"><button type="button" class="btn-reset" onclick="closeUploadModal()">Batal</button><button type="submit" name="upload_hasil" class="btn-filter">Upload & Selesai</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Catatan -->
    <div id="catatanModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-sticky-note"></i> Tambah Catatan</h3>
            <form method="POST">
                <input type="hidden" name="id" id="catatan_id">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="status" id="catatan_status" value="diproses">
                <div class="form-group"><label>Catatan</label><textarea name="catatan" id="catatan_text" rows="4"></textarea></div>
                <div class="modal-buttons"><button type="button" class="btn-reset" onclick="closeCatatanModal()">Batal</button><button type="submit" class="btn-filter">Simpan</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Buat Surat Langsung -->
    <div id="buatSuratModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-file-alt"></i> Buat Surat Langsung</h3>
            <form method="POST">
                <input type="hidden" name="id" id="buat_id">
                <input type="hidden" name="buat_surat_langsung" value="1">
                
                <div class="form-row">
                    <div class="form-group"><label>Nama Warga</label><input type="text" id="buat_nama_warga" class="form-control" readonly style="background:#f5f5f5;"></div>
                    <div class="form-group"><label>Nomor Surat</label><input type="text" name="nomor_surat" id="buat_nomor_surat" placeholder="065/RT.05/RW.03/X/2025" required></div>
                </div>

                <div class="form-group">
                    <label>Jenis Surat</label>
                    <select name="jenis_surat_modal" id="buat_jenis_surat" class="form-control">
                        <option value="surat pengantar">Surat Pengantar</option>
                        <option value="surat keterangan">Surat Keterangan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Keperluan (akan muncul di surat)</label>
                    <input type="text" name="keperluan_surat" id="buat_keperluan" class="form-control" placeholder="Contoh: pembuatan KTP baru karena hilang">
                </div>

                <div class="form-row">
                    <div class="form-group"><label>NIK</label><input type="text" name="nik" id="buat_nik" class="form-control" placeholder="Masukkan NIK warga"></div>
                    <div class="form-group"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" id="buat_tempat_lahir" class="form-control" placeholder="Contoh: Sukamaju"></div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Tanggal Lahir</label><input type="date" name="tgl_lahir" id="buat_tgl_lahir" class="form-control"></div>
                    <div class="form-group"><label>Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="buat_jenis_kelamin" class="form-control">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Agama</label>
                        <select name="agama" id="buat_agama" class="form-control">
                            <option value="">Pilih Agama</option>
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Konghucu">Konghucu</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Pekerjaan</label><input type="text" name="pekerjaan" id="buat_pekerjaan" class="form-control" placeholder="Contoh: Pelajar, Wiraswasta, dll"></div>
                </div>

                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" id="buat_alamat" class="form-control" rows="2" placeholder="Masukkan alamat lengkap warga"></textarea>
                </div>

                <div class="form-group"><label>Catatan Admin</label><textarea name="catatan" id="buat_catatan" rows="2"></textarea></div>

                <div class="modal-buttons">
                    <button type="button" class="btn-reset" onclick="closeBuatSuratModal()">Batal</button>
                    <button type="submit" class="btn-filter" style="background:linear-gradient(135deg, #8B5CF6, #6D28D9);"><i class="fas fa-save"></i> Buat & Kirim Surat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() { sidebar.classList.add('active'); sidebarOverlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
        function closeSidebar() { sidebar.classList.remove('active'); sidebarOverlay.classList.remove('active'); document.body.style.overflow = ''; }
        if (menuToggle) menuToggle.addEventListener('click', openSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
        window.addEventListener('resize', function() { if (window.innerWidth > 768) closeSidebar(); });

        function openUploadModal(id) {
            document.getElementById('upload_id').value = id;
            document.getElementById('uploadModal').style.display = 'flex';
        }
        function closeUploadModal() { document.getElementById('uploadModal').style.display = 'none'; }

        function openCatatanModal(id) {
            document.getElementById('catatan_id').value = id;
            document.getElementById('catatanModal').style.display = 'flex';
        }
        function closeCatatanModal() { document.getElementById('catatanModal').style.display = 'none'; }

        function openBuatSuratModal(id, nama, jenisSurat) {
            document.getElementById('buat_id').value = id;
            document.getElementById('buat_nama_warga').value = nama;
            document.getElementById('buat_jenis_surat').value = jenisSurat;
            
            const now = new Date();
            const monthNames = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            document.getElementById('buat_nomor_surat').value = `065/RT.05/RW.03/${monthNames[now.getMonth()]}/${now.getFullYear()}`;
            
            document.getElementById('buat_keperluan').value = '';
            document.getElementById('buat_nik').value = '';
            document.getElementById('buat_tempat_lahir').value = '';
            document.getElementById('buat_tgl_lahir').value = '';
            document.getElementById('buat_jenis_kelamin').value = '';
            document.getElementById('buat_agama').value = '';
            document.getElementById('buat_pekerjaan').value = '';
            document.getElementById('buat_alamat').value = '';
            document.getElementById('buat_catatan').value = '';
            
            document.getElementById('buatSuratModal').style.display = 'flex';
        }
        function closeBuatSuratModal() { document.getElementById('buatSuratModal').style.display = 'none'; }

        window.onclick = function(event) {
            if (event.target == document.getElementById('uploadModal')) closeUploadModal();
            if (event.target == document.getElementById('catatanModal')) closeCatatanModal();
            if (event.target == document.getElementById('buatSuratModal')) closeBuatSuratModal();
        }

        function exportToExcel() {
            const form = document.getElementById('filterForm');
            const params = new URLSearchParams();
            params.append('export_excel', '1');
            for (let [key, value] of new FormData(form).entries()) if (value) params.append(key, value);
            window.location.href = '?' + params.toString();
        }

        function printReport() {
            const form = document.getElementById('filterForm');
            const params = new URLSearchParams();
            params.append('print_report', '1');
            for (let [key, value] of new FormData(form).entries()) if (value) params.append(key, value);
            window.open('?' + params.toString(), '_blank', 'width=1000,height=800');
        }
    </script>
</body>
</html>
<?php
if (isset($result_aktif) && is_object($result_aktif)) mysqli_free_result($result_aktif);
if (isset($result_riwayat) && is_object($result_riwayat)) mysqli_free_result($result_riwayat);
mysqli_close($conn);
?>