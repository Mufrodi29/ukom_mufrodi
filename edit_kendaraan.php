<?php
session_start();
require 'config/koneksi.php';
require 'config/log_helper.php';

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

// Ambil data kendaraan lama
$query = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan WHERE id_kendaraan = '$id_kendaraan'");
if (mysqli_num_rows($query) == 0) {
    header("Location: kendaraan.php?pesan=data_tidak_ditemukan");
    exit;
}
$data_lama = mysqli_fetch_assoc($query);

// PROSES UPDATE DATA
if (isset($_POST['update'])) {
    $plat_nomor = mysqli_real_escape_string($koneksi, strtoupper($_POST['plat_nomor']));
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    $warna = mysqli_real_escape_string($koneksi, $_POST['warna']);
    $pemilik = mysqli_real_escape_string($koneksi, $_POST['pemilik']);
    
    // Validasi
    if (empty($plat_nomor) || empty($jenis_kendaraan)) {
        $error = "Plat nomor dan jenis kendaraan wajib diisi!";
    } else {
        $cek_plat = mysqli_query($koneksi, "
            SELECT * FROM tb_kendaraan 
            WHERE plat_nomor = '$plat_nomor' 
            AND id_kendaraan != '$id_kendaraan'
        ");

        if (mysqli_num_rows($cek_plat) > 0) {
            $error = "Plat nomor sudah digunakan kendaraan lain!";
        } else {
            $update = mysqli_query($koneksi, "
                UPDATE tb_kendaraan SET 
                    plat_nomor = '$plat_nomor',
                    jenis_kendaraan = '$jenis_kendaraan',
                    warna = '$warna',
                    pemilik = '$pemilik'
                WHERE id_kendaraan = '$id_kendaraan'
            ");

            if ($update) {

                // ==============================
                // LOG AKTIVITAS
                // ==============================
                $log_detail = "Mengedit kendaraan: "
                    . "Plat (" . $data_lama['plat_nomor'] . " → $plat_nomor), "
                    . "Jenis (" . $data_lama['jenis_kendaraan'] . " → $jenis_kendaraan), "
                    . "Warna (" . $data_lama['warna'] . " → $warna), "
                    . "Pemilik (" . $data_lama['pemilik'] . " → $pemilik)";

                logAktivitas(
                    $koneksi,
                    $_SESSION['id_user'],
                    $log_detail
                );

                header("Location: kendaraan.php?pesan=update_sukses");
                exit;
            } else {
                $error = "Gagal mengupdate data: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kendaraan | Sistem Parkir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

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

<div class="container-fluid">
    <div class="row">

        <?php include 'sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="col-md-10 p-4">

            <h3>Edit Kendaraan</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="kendaraan.php">Data Kendaraan</a></li>
                    <li class="breadcrumb-item active">Edit Kendaraan</li>
                </ol>
            </nav>

            <?php if (isset($error)) { ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <div class="card">
                <div class="card-header bg-warning text-dark">
                    Form Edit Kendaraan
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="plat_nomor" class="form-label">Plat Nomor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="plat_nomor" name="plat_nomor" 
                                   placeholder="Contoh: B 1234 XYZ" 
                                   value="<?= isset($_POST['plat_nomor']) ? $_POST['plat_nomor'] : $data_lama['plat_nomor']; ?>"
                                   required maxlength="15">
                            <small class="text-muted">Nomor plat kendaraan</small>
                        </div>

                        <div class="mb-3">
                            <label for="jenis_kendaraan" class="form-label">Jenis Kendaraan <span class="text-danger">*</span></label>
                            <select class="form-select" id="jenis_kendaraan" name="jenis_kendaraan" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="motor" <?= (isset($_POST['jenis_kendaraan']) ? $_POST['jenis_kendaraan'] : $data_lama['jenis_kendaraan']) == 'motor' ? 'selected' : ''; ?>>Motor</option>
                                <option value="mobil" <?= (isset($_POST['jenis_kendaraan']) ? $_POST['jenis_kendaraan'] : $data_lama['jenis_kendaraan']) == 'mobil' ? 'selected' : ''; ?>>Mobil</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="warna" class="form-label">Warna</label>
                            <input type="text" class="form-control" id="warna" name="warna" 
                                   placeholder="Contoh: Hitam, Putih, Merah"
                                   value="<?= isset($_POST['warna']) ? $_POST['warna'] : $data_lama['warna']; ?>"
                                   maxlength="20">
                        </div>

                        <div class="mb-3">
                            <label for="pemilik" class="form-label">Nama Pemilik</label>
                            <textarea class="form-control" id="pemilik" name="pemilik" rows="2" 
                                      placeholder="Nama pemilik kendaraan"
                                      maxlength="100"><?= isset($_POST['pemilik']) ? $_POST['pemilik'] : $data_lama['pemilik']; ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update" class="btn btn-warning">
                                Update
                            </button>
                            <a href="kendaraan.php" class="btn btn-secondary">
                                Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto uppercase plat nomor
document.getElementById('plat_nomor').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>
</body>
</html>