<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// VALIDASI ID
$id = $_GET['id'] ?? '';
if ($id == '') {
    header("Location: index.php?pesan=id_tidak_valid");
    exit;
}

// ===== AMBIL DATA USER (UNTUK FORM) =====
$cek = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE id_user='$id'");
if (mysqli_num_rows($cek) == 0) {
    header("Location: index.php?pesan=data_tidak_ditemukan");
    exit;
}

$user = mysqli_fetch_assoc($cek); // ⬅️ INI PENTING (BUAT FORM)

// ===== PROSES UPDATE =====
if (isset($_POST['update'])) {

    // SIMPAN DATA LAMA UNTUK LOG
    $user_lama = $user;

    $nama     = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $status   = $_POST['status_aktif'];

    $update = mysqli_query($koneksi, "
        UPDATE tb_user SET
            nama_lengkap='$nama',
            username='$username',
            password='$password',
            role='$role',
            status_aktif='$status'
        WHERE id_user='$id'
    ");

    if ($update) {

        // ===== SUSUN LOG =====
        $perubahan = [];

        if ($user_lama['nama_lengkap'] != $nama)
            $perubahan[] = "nama: {$user_lama['nama_lengkap']} → $nama";

        if ($user_lama['username'] != $username)
            $perubahan[] = "username: {$user_lama['username']} → $username";

        if ($user_lama['role'] != $role)
            $perubahan[] = "role: {$user_lama['role']} → $role";

        if ($user_lama['status_aktif'] != $status)
            $perubahan[] = "status: {$user_lama['status_aktif']} → $status";

        $detail = $perubahan ? implode(', ', $perubahan) : 'tanpa perubahan data';

        // ===== INSERT LOG =====
        $id_admin = $_SESSION['id_user'];
        $aktivitas = "Mengubah data user {$user_lama['nama_lengkap']} ({$user_lama['username']}): $detail";

        mysqli_query($koneksi, "
            INSERT INTO tb_log_aktivitas (id_user, aktivitas)
            VALUES ('$id_admin', '$aktivitas')
        ");

        header("Location: index.php?pesan=update_sukses");
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
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

<div class="container mt-4">
    <h3>Edit User</h3>

    <form method="post">
        <div class="mb-3">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap"
                   value="<?= $user['nama_lengkap']; ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username"
                   value="<?= $user['username']; ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="text" name="password"
                   value="<?= $user['password']; ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <option value="admin" <?= $user['role']=='admin'?'selected':''; ?>>Admin</option>
                <option value="tugas" <?= $user['role']=='tugas'?'selected':''; ?>>Petugas</option>
                <option value="owner" <?= $user['role']=='owner'?'selected':''; ?>>Owner</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status_aktif" class="form-control">
                <option value="1" <?= $user['status_aktif']==1?'selected':''; ?>>Aktif</option>
                <option value="0" <?= $user['status_aktif']==0?'selected':''; ?>>Nonaktif</option>
            </select>
        </div>

        <button name="update" class="btn btn-warning">Update</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>

</body>
</html>
