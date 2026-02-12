<?php
session_start();
include "config/koneksi.php";

/* =========================
   CEK LOGIN
========================= */
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

/* =========================
   AMBIL SESSION USER (AMAN)
========================= */
$id_user   = $_SESSION['id_user'] ?? null;
$nama_user = $_SESSION['nama_user'] ?? ''; // biar gak warning kalau belum diset
$role      = $_SESSION['role'] ?? '';

if (!$id_user) {
    $_SESSION['error'] = "Session user tidak valid. Silakan login ulang.";
    header("Location: index.php");
    exit;
}

/* =========================
   AMBIL ID TARIF DARI GET
========================= */
$id_tarif = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_tarif <= 0) {
    $_SESSION['error'] = "ID tarif tidak valid!";
    header("Location: tarif.php");
    exit;
}

/* =========================
   CEK DATA TARIF
========================= */
$cek = mysqli_query($koneksi, "SELECT * FROM tb_tarif WHERE id_tarif='$id_tarif'");
$data = mysqli_fetch_assoc($cek);

if (!$data) {
    $_SESSION['error'] = "Data tarif tidak ditemukan!";
    header("Location: tarif.php");
    exit;
}

/* =========================
   CEK FK: APAKAH TARIF DIPAKAI DI TRANSAKSI?
   (Agar tidak error CONSTRAINT)
========================= */
$qcek_fk = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tb_transaksi WHERE id_tarif='$id_tarif'");
$row_fk  = mysqli_fetch_assoc($qcek_fk);
$total_pakai = (int)($row_fk['total'] ?? 0);

if ($total_pakai > 0) {
    $_SESSION['error'] = "Tarif tidak bisa dihapus karena masih digunakan oleh $total_pakai transaksi!";
    header("Location: tarif.php");
    exit;
}

/* =========================
   INFO UNTUK LOG
========================= */
$jenis_kendaraan = $data['jenis_kendaraan'];
$tarif_rp = number_format((float)$data['tarif_per_jam'], 0, ',', '.');

/* =========================
   PROSES HAPUS
========================= */
$hapus = mysqli_query($koneksi, "DELETE FROM tb_tarif WHERE id_tarif='$id_tarif'");

if ($hapus) {

    /* =========================
       LOG AKTIVITAS
    ========================= */
    $aktivitas = "Menghapus tarif parkir untuk $jenis_kendaraan sebesar Rp $tarif_rp";

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
