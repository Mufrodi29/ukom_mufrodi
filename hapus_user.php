<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// hanya admin yang boleh hapus
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?pesan=akses_ditolak");
    exit;
}

$id = $_GET['id'];

// cegah hapus akun sendiri
if ($id == $_SESSION['id_user']) {
    header("Location: index.php?pesan=tidak_bisa_hapus_diri_sendiri");
    exit;
}

// ===== AMBIL DATA USER =====
$cek = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE id_user='$id'");
if (mysqli_num_rows($cek) == 0) {
    header("Location: index.php?pesan=data_tidak_ditemukan");
    exit;
}

$data = mysqli_fetch_assoc($cek);
$nama_user = $data['nama_lengkap'];
$username  = $data['username'];

// ===== PROSES HAPUS =====
$hapus = mysqli_query($koneksi, "DELETE FROM tb_user WHERE id_user='$id'");

if ($hapus) {

    // ===== LOG AKTIVITAS =====
    $id_admin = $_SESSION['id_user'];
    $aktivitas = "Menghapus user: $nama_user ($username)";

    mysqli_query($koneksi, "
        INSERT INTO tb_log_aktivitas (id_user, aktivitas)
        VALUES ('$id_admin', '$aktivitas')
    ");

    header("Location: index.php?pesan=hapus_berhasil");
    exit;

} else {
    header("Location: index.php?pesan=hapus_gagal");
    exit;
}
