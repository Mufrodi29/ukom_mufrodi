<?php
session_start();
include "config/koneksi.php";

// Cek login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

// Ambil ID dari URL
$id_tarif = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data tarif (DATA LAMA)
$query = mysqli_query($koneksi, "SELECT * FROM tb_tarif WHERE id_tarif='$id_tarif'");

if (mysqli_num_rows($query) == 0) {
    $_SESSION['error'] = "Data tarif tidak ditemukan!";
    header("Location: tarif.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

// Proses update tarif
if (isset($_POST['update'])) {
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    $tarif_per_jam   = mysqli_real_escape_string($koneksi, $_POST['tarif_per_jam']);

    // Cek apakah jenis kendaraan sudah ada (kecuali data sendiri)
    $cek = mysqli_query($koneksi, "
        SELECT * FROM tb_tarif 
        WHERE jenis_kendaraan='$jenis_kendaraan' 
        AND id_tarif != '$id_tarif'
    ");
    
    if (mysqli_num_rows($cek) > 0) {
        $error = "Tarif untuk jenis kendaraan ini sudah ada!";
    } else {

        $query_update = mysqli_query($koneksi, "
            UPDATE tb_tarif SET 
                jenis_kendaraan='$jenis_kendaraan',
                tarif_per_jam='$tarif_per_jam'
            WHERE id_tarif='$id_tarif'
        ");

        if ($query_update) {

            // ===== LOG AKTIVITAS =====
            $id_user = $_SESSION['id_user'];

            $aktivitas = "Mengubah tarif parkir {$data['jenis_kendaraan']} 
            dari Rp {$data['tarif_per_jam']} 
            menjadi Rp $tarif_per_jam";

            mysqli_query($koneksi, "
                INSERT INTO tb_log_aktivitas (id_user, aktivitas)
                VALUES ('$id_user', '$aktivitas')
            ");

            $_SESSION['success'] = "Tarif berhasil diupdate!";
            header("Location: tarif.php");
            exit;

        } else {
            $error = "Gagal mengupdate tarif: " . mysqli_error($koneksi);
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tarif - Sistem Parkir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-primary">
    <div class="container-fluid">
        <span class="navbar-brand">Sistem Parkir</span>
        <div class="text-white">
            <?= $_SESSION['nama_lengkap']; ?> (<?= $_SESSION['role']; ?>)
            <a href="../logout.php" class="btn btn-sm btn-light ms-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">✏️ Edit Tarif Parkir</h4>
                </div>
                <div class="card-body">

                    <?php if (isset($error)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <strong>Error!</strong> <?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jenis Kendaraan <span class="text-danger">*</span></label>
                            <select name="jenis_kendaraan" class="form-select" required>
                                <option value="">-- Pilih Jenis Kendaraan --</option>
                                <option value="motor" <?= $data['jenis_kendaraan'] == 'motor' ? 'selected' : ''; ?>>
                                    🏍️ Motor
                                </option>
                                <option value="mobil" <?= $data['jenis_kendaraan'] == 'mobil' ? 'selected' : ''; ?>>
                                    🚗 Mobil
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tarif per Jam (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="tarif_per_jam" class="form-control" 
                                   value="<?= $data['tarif_per_jam']; ?>"
                                   min="0" step="500" required>
                            <small class="text-muted">Masukkan nominal tanpa titik atau koma</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="update" class="btn btn-warning">
                                💾 Update Tarif
                            </button>
                            <a href="tarif.php" class="btn btn-secondary">
                                ← Kembali
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>