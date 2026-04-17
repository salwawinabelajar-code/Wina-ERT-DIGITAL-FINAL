<?php
session_start();
require_once(__DIR__ . '/../config/db.php');
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data surat
$query = "SELECT s.*, u.nama, u.alamat, u.no_kk, u.no_hp, u.email 
          FROM pengajuan_surat s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = $id";

$result = mysqli_query($conn, $query);
$surat = mysqli_fetch_assoc($result);

if (!$surat) {
    die("Data surat tidak ditemukan!");
}

// Setup DOMPDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Template surat berdasarkan jenis
$html = '';

if ($surat['jenis_surat'] == 'surat pengantar') {
    $html = suratPengantar($surat);
} elseif ($surat['jenis_surat'] == 'surat keterangan tidak mampu') {
    $html = suratKeteranganTidakMampu($surat);
} else {
    $html = suratKeteranganUmum($surat);
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Simpan file PDF
$pdf_dir = '../uploads/surat_hasil/';
if (!file_exists($pdf_dir)) {
    mkdir($pdf_dir, 0777, true);
}

$pdf_filename = 'surat_' . $surat['id'] . '_' . time() . '.pdf';
$pdf_path = $pdf_dir . $pdf_filename;
$pdf_db_path = 'uploads/surat_hasil/' . $pdf_filename;

file_put_contents($pdf_path, $dompdf->output());

// Update database dengan file hasil
$update_query = "UPDATE pengajuan_surat SET 
                 file_hasil = '$pdf_db_path',
                 status = 'selesai',
                 tgl_selesai = NOW()
                 WHERE id = $id";

mysqli_query($conn, $update_query);

// Redirect ke halaman admin dengan pesan sukses
if ($_SESSION['role'] === 'admin') {
    header("Location: surat.php?success_generate=1&id=$id");
} else {
    header("Location: riwayat.php?tab=surat");
}
exit();

function suratPengantar($data) {
    $tanggal = date('d F Y');
    $nomor_surat = $data['nomor_surat'] ?? '470/' . date('Y') . '/RT.05/' . date('m') . '/' . $data['id'];
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Surat Pengantar</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                margin: 0;
                padding: 20px;
                line-height: 1.6;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                font-size: 18px;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 16px;
            }
            .header p {
                margin: 5px 0;
                font-size: 12px;
            }
            .title {
                text-align: center;
                margin: 20px 0;
            }
            .title h3 {
                text-decoration: underline;
                margin: 0;
            }
            .content {
                margin: 20px 0;
            }
            .signature {
                margin-top: 40px;
                text-align: right;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            table {
                width: 100%;
                margin: 10px 0;
            }
            td {
                padding: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>PEMERINTAH KOTA</h1>
                <h2>KECAMATAN SUKAMAJU</h2>
                <h3>KELURAHAN SUKAMAJU</h3>
                <p>Jl. Raya Sukamaju No. 123, Telp. (021) 1234567</p>
                <p>Website: www.sukamaju.go.id, Email: kelurahan@sukamaju.go.id</p>
            </div>
            
            <div class='title'>
                <h3>SURAT PENGANTAR</h3>
                <p>Nomor: {$nomor_surat}</p>
            </div>
            
            <div class='content'>
                <p>Yang bertanda tangan di bawah ini, Ketua RT 05 RW 03 Kelurahan Sukamaju, Kecamatan Sukamaju, menerangkan bahwa:</p>
                
                <table>
                    <tr>
                        <td width='30%'>Nama</td>
                        <td width='5%'>:</td>
                        <td>{$data['nama']}</td>
                    </tr>
                    <tr>
                        <td>NIK</td>
                        <td>:</td>
                        <td>{$data['user_id']}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td>{$data['alamat']}</td>
                    </tr>
                    <tr>
                        <td>No. KK</td>
                        <td>:</td>
                        <td>{$data['no_kk']}</td>
                    </tr>
                    <tr>
                        <td>No. HP</td>
                        <td>:</td>
                        <td>{$data['no_hp']}</td>
                    </tr>
                </table>
                
                <p>Adalah benar warga RT 05 yang sedang mengurus <strong>{$data['keperluan']}</strong>.</p>
                <p>Surat pengantar ini dibuat untuk keperluan pengurusan dokumen di instansi terkait.</p>
                <p>Demikian surat ini dibuat, agar dapat dipergunakan sebagaimana mestinya.</p>
            </div>
            
            <div class='signature'>
                <p>Sukamaju, {$tanggal}</p>
                <p>Ketua RT 05,</p>
                <br><br><br>
                <p><u>_________________________</u></p>
                <p>Nama Ketua RT</p>
            </div>
            
            <div class='footer'>
                <p>Catatan: {$data['catatan_admin']}</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function suratKeteranganTidakMampu($data) {
    $tanggal = date('d F Y');
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Surat Keterangan Tidak Mampu</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                margin: 0;
                padding: 20px;
                line-height: 1.6;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            .title {
                text-align: center;
                margin: 20px 0;
            }
            .title h3 {
                text-decoration: underline;
                margin: 0;
            }
            .content {
                margin: 20px 0;
            }
            .signature {
                margin-top: 40px;
                text-align: right;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            table {
                width: 100%;
                margin: 10px 0;
            }
            td {
                padding: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>PEMERINTAH KOTA</h1>
                <h2>KECAMATAN SUKAMAJU</h2>
                <h3>KELURAHAN SUKAMAJU</h3>
                <p>Jl. Raya Sukamaju No. 123, Telp. (021) 1234567</p>
            </div>
            
            <div class='title'>
                <h3>SURAT KETERANGAN TIDAK MAMPU</h3>
                <p>Nomor: 470/RT.05/SKTM/{$data['id']}/" . date('Y') . "</p>
            </div>
            
            <div class='content'>
                <p>Yang bertanda tangan di bawah ini, Ketua RT 05 RW 03 Kelurahan Sukamaju, dengan ini menerangkan bahwa:</p>
                
                <table>
                    <tr>
                        <td width='30%'>Nama</td>
                        <td width='5%'>:</td>
                        <td>{$data['nama']}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td>{$data['alamat']}</td>
                    </tr>
                    <tr>
                        <td>Pekerjaan</td>
                        <td>:</td>
                        <td>-</td>
                    </tr>
                </table>
                
                <p>Berdasarkan pengamatan dan informasi yang ada, benar bahwa yang bersangkutan termasuk dalam kategori <strong>TIDAK MAMPU</strong> secara ekonomi dan membutuhkan bantuan sosial.</p>
                <p>Surat ini dibuat untuk keperluan: <strong>{$data['keperluan']}</strong>.</p>
                <p>Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
            </div>
            
            <div class='signature'>
                <p>Sukamaju, {$tanggal}</p>
                <p>Ketua RT 05,</p>
                <br><br><br>
                <p><u>_________________________</u></p>
            </div>
            
            <div class='footer'>
                <p>Catatan: {$data['catatan_admin']}</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function suratKeteranganUmum($data) {
    $tanggal = date('d F Y');
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Surat Keterangan</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                margin: 0;
                padding: 20px;
                line-height: 1.6;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            .title {
                text-align: center;
                margin: 20px 0;
            }
            .title h3 {
                text-decoration: underline;
                margin: 0;
            }
            .content {
                margin: 20px 0;
            }
            .signature {
                margin-top: 40px;
                text-align: right;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            table {
                width: 100%;
                margin: 10px 0;
            }
            td {
                padding: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>PEMERINTAH KOTA</h1>
                <h2>KECAMATAN SUKAMAJU</h2>
                <h3>KELURAHAN SUKAMAJU</h3>
                <p>Jl. Raya Sukamaju No. 123, Telp. (021) 1234567</p>
            </div>
            
            <div class='title'>
                <h3>SURAT KETERANGAN</h3>
                <p>Nomor: 470/RT.05/SK/{$data['id']}/" . date('Y') . "</p>
            </div>
            
            <div class='content'>
                <p>Yang bertanda tangan di bawah ini, Ketua RT 05 RW 03 Kelurahan Sukamaju, dengan ini menerangkan bahwa:</p>
                
                <table>
                    <tr>
                        <td width='30%'>Nama</td>
                        <td width='5%'>:</td>
                        <td>{$data['nama']}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td>{$data['alamat']}</td>
                    </tr>
                </table>
                
                <p>Bahwa yang bersangkutan adalah benar warga RT 05 dan surat ini dibuat untuk keperluan: <strong>{$data['keperluan']}</strong>.</p>
                <p>Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
            </div>
            
            <div class='signature'>
                <p>Sukamaju, {$tanggal}</p>
                <p>Ketua RT 05,</p>
                <br><br><br>
                <p><u>_________________________</u></p>
            </div>
            
            <div class='footer'>
                <p>Catatan: {$data['catatan_admin']}</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>