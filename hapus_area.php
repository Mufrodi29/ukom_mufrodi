<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// AMBIL SESSION USER
$id_user      = $_SESSION['id_user'] ?? 0;
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$role         = $_SESSION['role'] ?? '';

if ($id_user <= 0) {
    $_SESSION['error'] = "Session user tidak valid. Silakan login ulang.";
    header("Location: " . (($_SERVER['HTTP_REFERER'] ?? './area_parkir.php')));
    exit;
}

// URL BALIK (BIAR GAK 404)
$back_url = $_SERVER['HTTP_REFERER'] ?? './area_parkir.php';

// AMBIL ID AREA
$id_area = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_area <= 0) {
    $_SESSION['error'] = "ID area tidak valid!";
    header("Location: $back_url");
    exit;
}

// CEK DATA AREA PARKIR
$qArea = mysqli_query($koneksi, "SELECT * FROM tb_area_parkir WHERE id_area='$id_area'");
$dataArea = mysqli_fetch_assoc($qArea);

if (!$dataArea) {
    $_SESSION['error'] = "Data area parkir tidak ditemukan!";
    header("Location: $back_url");
    exit;
}

// SIMPAN INFO AREA UNTUK PESAN & LOG
$nama_area = $dataArea['nama_area'] ?? '-';
$kapasitas = (int)($dataArea['kapasitas'] ?? 0);
$terisi    = (int)($dataArea['terisi'] ?? 0);

// HITUNG TRANSAKSI TERKAIT (opsional buat info)
$qCount = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tb_transaksi WHERE id_area='$id_area'");
$rCount = mysqli_fetch_assoc($qCount);
$total_transaksi = (int)($rCount['total'] ?? 0);

// ================================
// MULAI TRANSAKSI BIAR AMAN
// ================================
mysqli_begin_transaction($koneksi);

try {
    // 1) HAPUS DULU DATA ANAK (tb_transaksi) agar FK tidak menolak
    $hapus_transaksi = mysqli_query($koneksi, "DELETE FROM tb_transaksi WHERE id_area='$id_area'");
    if (!$hapus_transaksi) {
        throw new Exception("Gagal menghapus transaksi terkait: " . mysqli_error($koneksi));
    }

    // 2) BARU HAPUS AREA
    $hapus_area = mysqli_query($koneksi, "DELETE FROM tb_area_parkir WHERE id_area='$id_area'");
    if (!$hapus_area) {
        throw new Exception("Gagal menghapus area: " . mysqli_error($koneksi));
    }

    // 3) LOG AKTIVITAS
    $aktivitas = "Menghapus area parkir: $nama_area (kapasitas $kapasitas, terisi $terisi) | Menghapus transaksi terkait: $total_transaksi data";

    $log = mysqli_query($koneksi, "
        INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas)
        VALUES ('$id_user', '$aktivitas', NOW())
    ");

    if (!$log) {
        throw new Exception("Area terhapus, tapi gagal menyimpan log: " . mysqli_error($koneksi));
    }

    mysqli_commit($koneksi);

    $_SESSION['success'] = "Area <b>$nama_area</b> berhasil dihapus! (Transaksi terkait: <b>$total_transaksi</b> ikut terhapus)";
    header("Location: $back_url");
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['error'] = $e->getMessage();
    header("Location: $back_url");
    exit;
}
?>
