<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$plat_nomor = isset($_GET['plat']) ? mysqli_real_escape_string($koneksi, strtoupper(trim($_GET['plat']))) : '';

if (empty($plat_nomor)) {
    echo json_encode(['success' => false, 'data' => null]);
    exit;
}

// Cari data kendaraan berdasarkan plat nomor
$query = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan WHERE plat_nomor = '$plat_nomor' LIMIT 1");

if (mysqli_num_rows($query) > 0) {
    $data = mysqli_fetch_assoc($query);
    echo json_encode([
        'success' => true,
        'data' => [
            'plat_nomor' => $data['plat_nomor'],
            'jenis_kendaraan' => $data['jenis_kendaraan'],
            'warna' => $data['warna'],
            'pemilik' => $data['pemilik']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'data' => null]);
}
?>