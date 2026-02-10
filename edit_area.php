<?php
session_start();
require 'config/koneksi.php';
require 'config/log_helper.php'; // <-- TAMBAHAN

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

// Ambil data area (data lama)
$query = mysqli_query($koneksi, "SELECT * FROM tb_area_parkir WHERE id_area = '$id_area'");
if (mysqli_num_rows($query) == 0) {
    header("Location: area.php?pesan=data_tidak_ditemukan");
    exit;
}
$data_lama = mysqli_fetch_assoc($query);

// PROSES UPDATE DATA
if (isset($_POST['update'])) {
    $nama_area = mysqli_real_escape_string($koneksi, $_POST['nama_area']);
    $kapasitas = mysqli_real_escape_string($koneksi, $_POST['kapasitas']);
    $terisi    = mysqli_real_escape_string($koneksi, $_POST['terisi']);
    
    // Validasi
    if (empty($nama_area) || empty($kapasitas)) {
        $error = "Nama area dan kapasitas wajib diisi!";
    } elseif (!is_numeric($kapasitas) || $kapasitas < 1) {
        $error = "Kapasitas harus berupa angka lebih dari 0!";
    } elseif (!is_numeric($terisi) || $terisi < 0) {
        $error = "Terisi harus berupa angka 0 atau lebih!";
    } elseif ($terisi > $kapasitas) {
        $error = "Terisi tidak boleh lebih dari kapasitas!";
    } else {

        $query_update = "UPDATE tb_area_parkir SET 
                         nama_area = '$nama_area',
                         kapasitas = '$kapasitas',
                         terisi = '$terisi'
                         WHERE id_area = '$id_area'";
        
        if (mysqli_query($koneksi, $query_update)) {

            // ==============================
            // LOG AKTIVITAS
            // ==============================
            logAktivitas(
                $koneksi,
                $_SESSION['id_user'],
                "Mengedit area parkir (" . $data_lama['nama_area'] . ") → ($nama_area), kapasitas: " . $data_lama['kapasitas'] . " → $kapasitas"
            );

            header("Location: area.php?pesan=update_sukses");
            exit;

        } else {
            $error = "Gagal mengupdate data: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Area Parkir | Sistem Parkir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100">

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

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10 col-12 p-4">

            <h3>Edit Area Parkir</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Edit Area Parkir</li>
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
                    Form Edit Area Parkir
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="nama_area" class="form-label">Nama Area <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_area" name="nama_area" 
                                   placeholder="Contoh: Area A, Area VIP, Basement 1" 
                                   value="<?= isset($_POST['nama_area']) ? $_POST['nama_area'] : $data_lama['nama_area']; ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="kapasitas" class="form-label">Kapasitas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="kapasitas" name="kapasitas" 
                                   placeholder="Jumlah slot parkir tersedia" min="1"
                                   value="<?= isset($_POST['kapasitas']) ? $_POST['kapasitas'] : $data_lama['kapasitas']; ?>"
                                   required>
                            <small class="text-muted">Jumlah maksimal kendaraan yang dapat parkir di area ini</small>
                        </div>

                        <div class="mb-3">
                            <label for="terisi" class="form-label">Terisi</label>
                            <input type="number" class="form-control" id="terisi" name="terisi" 
                                   placeholder="Jumlah slot yang sudah terisi" min="0"
                                   value="<?= isset($_POST['terisi']) ? $_POST['terisi'] : $data_lama['terisi']; ?>">
                            <small class="text-muted">Jumlah kendaraan yang saat ini parkir</small>
                        </div>

                        <div class="alert alert-info">
                            <strong>Info:</strong> Sisa slot tersedia: 
                            <strong><?= $data_lama['kapasitas'] - $data_lama['terisi']; ?></strong> slot
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Update
                            </button>
                            <a href="area.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
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