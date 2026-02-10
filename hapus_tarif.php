<?php
session_start();
include "config/koneksi.php";

// CEK LOGIN
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

// AMBIL ID USER LOGIN
$id_user   = $_SESSION['id_user'];
$nama_user = $_SESSION['nama_user'];
$role      = $_SESSION['role'];

// AMBIL ID TARIF
$id_tarif = isset($_GET['id']) ? intval($_GET['id']) : 0;

// CEK DATA TARIF
$cek = mysqli_query($koneksi, "SELECT * FROM tb_tarif WHERE id_tarif='$id_tarif'");
$data = mysqli_fetch_assoc($cek);

if (!$data) {
    $_SESSION['error'] = "Data tarif tidak ditemukan!";
    header("Location: tarif.php");
    exit;
}

// SIMPAN INFO UNTUK LOG
$jenis_kendaraan = $data['jenis_kendaraan'];
$tarif = number_format($data['tarif_per_jam'], 0, ',', '.');


// PROSES HAPUS
$hapus = mysqli_query($koneksi, "DELETE FROM tb_tarif WHERE id_tarif='$id_tarif'");

if ($hapus) {

    // LOG AKTIVITAS
    $aktivitas = "Menghapus tarif parkir untuk $jenis_kendaraan sebesar Rp $tarif";

    mysqli_query($koneksi, "
        INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas)
        VALUES ('$id_user', '$aktivitas', NOW())
    ");

    $_SESSION['success'] = "Tarif berhasil dihapus!";
} else {
    $_SESSION['error'] = "Gagal menghapus tarif: " . mysqli_error($koneksi);
}

header("Location: tarif.php");
exit;
?>
