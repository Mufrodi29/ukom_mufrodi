<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

$id_transaksi = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_transaksi === 0) {
    header("Location: riwayat_transaksi.php");
    exit;
}

// Ambil data transaksi
$query = mysqli_query($koneksi, "
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
           a.nama_area, tf.tarif_per_jam, u.nama_lengkap AS petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
    JOIN tb_user u ON t.id_user = u.id_user
    WHERE t.id_transaksi = $id_transaksi
");

if (mysqli_num_rows($query) === 0) {
    header("Location: riwayat_transaksi.php?pesan=not_found");
    exit;
}

$data = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Struk Parkir #<?= $id_transaksi; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
body {
    background:#f3f4f6;
    padding:20px;
}

.struk-container {
    max-width:420px;
    margin:auto;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
}

.struk-header {
    text-align:center;
    padding:12px;
    border-bottom:1px dashed #e5e7eb;
}

.struk-body { padding:12px; }

.section-title {
    font-weight:700;
    font-size:.9rem;
    border-bottom:1px solid #e5e7eb;
    margin-bottom:6px;
}

.info-row {
    display:flex;
    justify-content:space-between;
    font-size:.85rem;
    padding:3px 0;
}

.total-section {
    border-top:1px dashed #e5e7eb;
    margin-top:8px;
    padding-top:8px;
}

.total-row {
    display:flex;
    justify-content:space-between;
    font-size:.9rem;
    margin-bottom:4px;
}

.total-value {
    font-size:1.2rem;
    font-weight:800;
}

.struk-footer {
    text-align:center;
    padding:10px;
    font-size:.8rem;
    border-top:1px dashed #e5e7eb;
}

.barcode {
    font-family:monospace;
    letter-spacing:3px;
    margin:6px 0;
}

.no-print { margin-top:20px; }

@media print {
    @page { size:80mm auto; margin:5mm; }
    body { background:white; padding:0; }
    .no-print { display:none !important; }
    .struk-container {
        box-shadow:none;
        border-radius:0;
        margin:0;
    }
}
</style>
</head>

<body>

<div class="struk-container">

    <div class="struk-header">
        <strong>STRUK PARKIR</strong><br>
        <small>Sistem Parkir Otomatis</small>
    </div>

    <div class="struk-body">

        <div class="section-title">Informasi Transaksi</div>
        <div class="info-row">
            <span>No. Transaksi</span>
            <span>#<?= str_pad($data['id_transaksi'], 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="info-row">
            <span>Tanggal</span>
            <span><?= date('d/m/Y', strtotime($data['waktu_keluar'] ?? $data['waktu_masuk'])); ?></span>
        </div>
        <div class="info-row">
            <span>Petugas</span>
            <span><?= htmlspecialchars($data['petugas']); ?></span>
        </div>

        <div class="section-title mt-2">Kendaraan</div>
        <div class="info-row"><span>Plat</span><span><?= htmlspecialchars($data['plat_nomor']); ?></span></div>
        <div class="info-row"><span>Jenis</span><span><?= ucfirst($data['jenis_kendaraan']); ?></span></div>
        <div class="info-row"><span>Warna</span><span><?= htmlspecialchars($data['warna']); ?></span></div>
        <div class="info-row"><span>Area</span><span><?= htmlspecialchars($data['nama_area']); ?></span></div>

        <div class="section-title mt-2">Waktu</div>
        <div class="info-row">
            <span>Masuk</span>
            <span><?= date('d/m/Y H:i', strtotime($data['waktu_masuk'])); ?></span>
        </div>
        <?php if ($data['waktu_keluar']): ?>
        <div class="info-row">
            <span>Keluar</span>
            <span><?= date('d/m/Y H:i', strtotime($data['waktu_keluar'])); ?></span>
        </div>
        <?php endif; ?>

        <div class="total-section">
        <?php if ($data['status'] === 'keluar'): ?>
            <div class="total-row">
                <span>Durasi</span>
                <span><?= (int)$data['durasi_jam']; ?> Jam</span>
            </div>
            <div class="total-row">
                <span>Tarif/Jam</span>
                <span>Rp <?= number_format($data['tarif_per_jam'],0,',','.'); ?></span>
            </div>
            <div class="total-row">
                <strong>TOTAL</strong>
                <span class="total-value">Rp <?= number_format($data['biaya_total'],0,',','.'); ?></span>
            </div>
        <?php else:
            $masuk = new DateTime($data['waktu_masuk']);
            $now   = new DateTime();
            $selisih_detik = max(0, $now->getTimestamp() - $masuk->getTimestamp());
            $durasi_estimasi = floor($selisih_detik / 3600);
            if ($durasi_estimasi < 1) $durasi_estimasi = 1;
            $estimasi = $durasi_estimasi * $data['tarif_per_jam'];
        ?>
            <div class="total-row">
                <span>Status</span>
                <span style="color:#059669;">Sedang Parkir</span>
            </div>
            <div class="total-row">
                <span>Estimasi</span>
                <span><?= $durasi_estimasi; ?> Jam</span>
            </div>
            <div class="total-row">
                <strong>Perkiraan</strong>
                <span class="total-value">Rp <?= number_format($estimasi,0,',','.'); ?></span>
            </div>
        <?php endif; ?>
        </div>

    </div>

    <div class="struk-footer">
        <div class="barcode">|| <?= str_pad($data['id_transaksi'], 6, '0', STR_PAD_LEFT); ?> ||</div>
        Terima kasih 🙏<br>
        <strong>Parkir Aman & Nyaman</strong>
    </div>

</div>

<div class="struk-container no-print">
    <div class="p-2 d-flex gap-2">
        <button class="btn btn-primary w-100" onclick="window.print()">Cetak</button>
        <button class="btn btn-secondary w-100" onclick="location.href='riwayat_transaksi.php'">Kembali</button>
    </div>
</div>

</body>
</html>
