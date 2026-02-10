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
    header("Location: area.php?pesan=id_tidak_valid");
    exit;
}

$id_area = mysqli_real_escape_string($koneksi, $_GET['id']);

// Cek apakah data ada
$query_cek = mysqli_query($koneksi, "SELECT * FROM tb_area_parkir WHERE id_area = '$id_area'");
if (mysqli_num_rows($query_cek) == 0) {
    header("Location: area.php?pesan=data_tidak_ditemukan");
    exit;
}

$data = mysqli_fetch_assoc($query_cek);

// Cek apakah area masih terisi
if ($data['terisi'] > 0) {
    header("Location: area.php?pesan=area_masih_terisi");
    exit;
}

// PROSES HAPUS
$hapus = mysqli_query($koneksi, "DELETE FROM tb_area_parkir WHERE id_area = '$id_area'");

if ($hapus) {

    // ===== LOG AKTIVITAS =====
    $id_user = $_SESSION['id_user']; // petugas login
    $nama_area = $data['nama_area'];
    $aktivitas = "Menghapus area parkir: $nama_area";

    $log = mysqli_query($koneksi, "
    INSERT INTO tb_log_aktivitas (id_user, aktivitas)
    VALUES ('$id_user', '$aktivitas')
");


    // Optional debug kalau log gagal
    // if (!$log) { die(mysqli_error($koneksi)); }

    header("Location: area.php?pesan=hapus_sukses");
    exit;

} else {
    header("Location: area.php?pesan=hapus_gagal");
    exit;
}
?>
