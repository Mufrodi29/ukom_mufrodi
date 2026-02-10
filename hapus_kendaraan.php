<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// Cek ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: kendaraan.php?pesan=id_tidak_valid");
    exit;
}

$id_kendaraan = mysqli_real_escape_string($koneksi, $_GET['id']);

// Cek apakah data ada
$query_cek = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan WHERE id_kendaraan = '$id_kendaraan'");
if (mysqli_num_rows($query_cek) == 0) {
    header("Location: kendaraan.php?pesan=data_tidak_ditemukan");
    exit;
}

$data = mysqli_fetch_assoc($query_cek);

// PROSES HAPUS
$hapus = mysqli_query($koneksi, "DELETE FROM tb_kendaraan WHERE id_kendaraan = '$id_kendaraan'");

if ($hapus) {

    // ===== LOG AKTIVITAS =====
    $id_user = $_SESSION['id_user']; // petugas login
    $plat = $data['plat_nomor'];
    $aktivitas = "Menghapus data kendaraan dengan plat $plat";

    mysqli_query($koneksi, "
        INSERT INTO tb_log_aktivitas (id_user, aktivitas)
        VALUES ('$id_user', '$aktivitas')
    ");

    header("Location: kendaraan.php?pesan=hapus_sukses");
    exit;

} else {
    header("Location: kendaraan.php?pesan=hapus_gagal");
    exit;
}
?>
