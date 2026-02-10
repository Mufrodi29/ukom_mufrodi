<?php
session_start();
require 'config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// ============================
// DATA KENDARAAN MASIH PARKIR
// ============================
$sqlParkir = "
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
           a.nama_area, tf.tarif_per_jam
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
    WHERE t.status = 'masuk'
    ORDER BY t.waktu_masuk DESC
";
$query_parkir = mysqli_query($koneksi, $sqlParkir);

// ============================
// PROSES CHECKOUT
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {

    $id_transaksi = (int)$_POST['id_transaksi'];
    $id_user      = (int)$_SESSION['id_user'];

    mysqli_begin_transaction($koneksi);

    try {
        // Ambil data transaksi
        $sqlTransaksi = "
            SELECT t.*, k.plat_nomor, a.id_area, tf.tarif_per_jam
            FROM tb_transaksi t
            JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
            JOIN tb_area_parkir a ON t.id_area = a.id_area
            JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
            WHERE t.id_transaksi = $id_transaksi
              AND t.status = 'masuk'
            LIMIT 1
        ";
        $query = mysqli_query($koneksi, $sqlTransaksi);

        if (!$query || mysqli_num_rows($query) === 0) {
            throw new Exception("Transaksi tidak ditemukan atau sudah checkout");
        }

        $data = mysqli_fetch_assoc($query);

        // ============================
        // HITUNG WAKTU & BIAYA
        // ============================
        $waktu_masuk  = new DateTime($data['waktu_masuk']);
        $waktu_keluar = new DateTime(); // sekarang

        $selisih_detik = $waktu_keluar->getTimestamp() - $waktu_masuk->getTimestamp();

        // Normalisasi jika data lama rusak
        if ($selisih_detik < 0) {
            $selisih_detik = 0;
            $waktu_keluar = clone $waktu_masuk;
        }

        // Pembulatan jam
        $selisih_detik = $waktu_keluar->getTimestamp() - $waktu_masuk->getTimestamp();

        if ($selisih_detik < 0) {
            $selisih_detik = 0;
        }

        // Hitung jam parkir (minimal 1 jam, per jam penuh)
        $durasi_jam = floor($selisih_detik / 3600);
        if ($durasi_jam < 1) {
            $durasi_jam = 1;
        }

        

        $biaya_total = $durasi_jam * (int)$data['tarif_per_jam'];
        $waktu_keluar_format = $waktu_keluar->format('Y-m-d H:i:s');

        // ============================
        // UPDATE TRANSAKSI
        // ============================
        $updateTransaksi = "
            UPDATE tb_transaksi SET
                waktu_keluar = '$waktu_keluar_format',
                durasi_jam   = $durasi_jam,
                biaya_total  = $biaya_total,
                status       = 'keluar'
            WHERE id_transaksi = $id_transaksi
        ";
        if (!mysqli_query($koneksi, $updateTransaksi)) {
            throw new Exception("Gagal update transaksi");
        }

        // ============================
        // UPDATE AREA PARKIR (ANTI MINUS)
        // ============================
        $updateArea = "
            UPDATE tb_area_parkir
            SET terisi = IF(terisi > 0, terisi - 1, 0)
            WHERE id_area = {$data['id_area']}
        ";
        mysqli_query($koneksi, $updateArea);

        // ============================
        // LOG AKTIVITAS
        // ============================
        $aktivitas = "Kendaraan {$data['plat_nomor']} keluar parkir - Durasi: {$durasi_jam} jam, Biaya: Rp "
            . number_format($biaya_total, 0, ',', '.');

        $logSql = "
            INSERT INTO tb_log_aktivitas (id_user, aktivitas)
            VALUES ($id_user, '$aktivitas')
        ";
        mysqli_query($koneksi, $logSql);

        mysqli_commit($koneksi);

        header("Location: cetak_struk.php?id=$id_transaksi");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $error = "Transaksi gagal: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Keluar | Sistem Parkir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root{
            --bg:#070d1c;
            --card:rgba(255,255,255,.06);
            --border:rgba(255,255,255,.10);
            --text:rgba(255,255,255,.92);
            --muted:rgba(255,255,255,.62);
            --radius:18px;
        }

        body{
            min-height:100vh;
            color:var(--text);
            background:
                radial-gradient(900px 520px at 15% 8%, rgba(99,102,241,.30), transparent 60%),
                radial-gradient(800px 480px at 88% 14%, rgba(34,211,238,.20), transparent 58%),
                radial-gradient(900px 600px at 50% 110%, rgba(168,85,247,.18), transparent 60%),
                var(--bg);
        }

        .subtle{ color: var(--muted); }

        .topbar{
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(7,13,28,.55);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .brand-chip{
            width:44px;height:44px;border-radius:16px;
            display:grid;place-items:center;
            background:linear-gradient(135deg,#2563eb,#a855f7);
            box-shadow:0 14px 40px rgba(37,99,235,.30);
        }
        .btn-pill{ border-radius:999px; }
        .btn-soft{
            color:var(--text);
            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.12);
        }
        .btn-soft:hover{ background:rgba(255,255,255,.12); }

        .content-wrap{ padding: 22px; }

        .glass, .card{
            background: var(--card) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            backdrop-filter: blur(14px);
            box-shadow: 0 18px 55px rgba(0,0,0,.32);
        }
        
        .card-header{
            background: rgba(255,255,255,.08) !important;
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
            color: #fff !important;
        }

        .table, .table *{
            background: transparent !important;
        }
        .table th, .table td{
            color: rgba(255,255,255,.88) !important;
            border-color: rgba(255,255,255,.10) !important;
            vertical-align: middle;
        }
        .table thead th{
            background: rgba(255,255,255,.08) !important;
            color: rgba(255,255,255,.70) !important;
            border-bottom: 1px solid rgba(255,255,255,.14) !important;
        }
        .table tbody tr{
            background: rgba(255,255,255,.04) !important;
        }
        .table-striped > tbody > tr:nth-of-type(odd){
            background: rgba(255,255,255,.06) !important;
        }
        .table-hover > tbody > tr:hover{
            background: rgba(255,255,255,.10) !important;
        }

        .badge{
            border-radius: 999px !important;
            padding: .45rem .75rem !important;
            font-weight: 700 !important;
        }
        .badge.bg-success{
            background: rgba(34,197,94,.18) !important;
            color: #22c55e !important;
            border: 1px solid rgba(34,197,94,.40) !important;
        }
        .badge.bg-info{
            background: rgba(34,211,238,.18) !important;
            color: #22d3ee !important;
            border: 1px solid rgba(34,211,238,.40) !important;
        }
        .badge.bg-warning{
            background: rgba(245,158,11,.18) !important;
            color: #fbbf24 !important;
            border: 1px solid rgba(245,158,11,.40) !important;
        }

        .alert{
            border-radius: 16px !important;
            border: 1px solid !important;
        }
        .alert-danger{
            background: rgba(239,68,68,.15) !important;
            border-color: rgba(239,68,68,.4) !important;
            color: #fb7185 !important;
        }

        @media (max-width: 767px){
            .content-wrap{ padding: 16px; }
        }
    </style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="container-fluid px-3 px-md-4 py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="brand-chip">
                <i class="bi bi-p-square-fill fs-5 text-white"></i>
            </div>
            <div>
                <div class="fw-bold">Sistem Parkir</div>
                <div class="subtle small">Admin Panel</div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block">
                <div class="fw-semibold"><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
                <div class="subtle small"><?= ucfirst(htmlspecialchars($_SESSION['role'])); ?></div>
            </div>
            <a href="../logout.php" class="btn btn-outline-danger btn-pill px-3">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row g-0">

        <!-- ===== SIDEBAR ===== -->
        <?php include 'sidebar.php'; ?>
        <style>
          /* ===== SIDEBAR (HTML TIDAK DIUBAH, CSS AJA) ===== */
        .col-md-2.bg-dark{
            background: linear-gradient(180deg, #0b1220, #070d1c) !important;
            border-right: 1px solid rgba(255,255,255,.08);
            box-shadow: 8px 0 40px rgba(0,0,0,.35);
        }
        .col-md-2.bg-dark h5{
            font-weight: 800;
            letter-spacing: .12em;
            font-size: .95rem;
            margin-bottom: 22px !important;
        }
        .col-md-2.bg-dark .nav-link{
            padding: 11px 12px;
            border-radius: 14px;
            color: rgba(255,255,255,.82) !important;
            transition: .18s ease;
            line-height: 1.25;
        }
        .col-md-2.bg-dark .nav-link:hover{
            background: rgba(255,255,255,.07);
            transform: translateX(3px);
        }
        .col-md-2.bg-dark .nav-link.active{
            background: linear-gradient(135deg, rgba(37,99,235,.35), rgba(168,85,247,.25));
            box-shadow: inset 3px 0 0 rgba(96,165,250,1), 0 10px 26px rgba(37,99,235,.16);
            color: #fff !important;
        }  
        </style>

        <!-- CONTENT -->
        <div class="col-md-10">
            <div class="content-wrap">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Transaksi Keluar Parkir</h2>
                        <div class="subtle">Checkout kendaraan yang keluar dari parkir</div>
                    </div>
                    <a href="transaksi_masuk.php" class="btn btn-soft btn-pill px-3">
                        <i class="bi bi-box-arrow-in-left me-1"></i> Transaksi Masuk
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Error!</strong> <?= htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- TABEL KENDARAAN PARKIR -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="bi bi-car-front me-2 text-warning"></i> Kendaraan Sedang Parkir
                        </span>
                        <span class="subtle small">Total: <?= mysqli_num_rows($query_parkir); ?></span>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>Plat Nomor</th>
                                        <th>Jenis</th>
                                        <th>Warna</th>
                                        <th>Pemilik</th>
                                        <th>Area</th>
                                        <th>Waktu Masuk</th>
                                        <th>Durasi</th>
                                        <th>Tarif/Jam</th>
                                        <th>Estimasi Biaya</th>
                                        <th style="width:140px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if (mysqli_num_rows($query_parkir) === 0) {
                                    echo '<tr><td colspan="11" class="text-center subtle">Tidak ada kendaraan yang sedang parkir</td></tr>';
                                } else {
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($query_parkir)) {
                                        // Hitung durasi dengan DateTime untuk akurasi
                                        $waktu_masuk_dt = new DateTime($row['waktu_masuk']);
                                        $waktu_sekarang = new DateTime();
                                        
                                        // Hitung selisih dalam detik
                                        $selisih_detik = $waktu_sekarang->getTimestamp() - $waktu_masuk_dt->getTimestamp();
                                        
                                        // Pastikan tidak negatif
                                        if ($selisih_detik < 0) {
                                            $selisih_detik = 0;
                                        }
                                        
                                        // Durasi dalam jam (per jam penuh, minimal 1 jam)
                                        $durasi_jam = floor($selisih_detik / 3600);
                                        if ($durasi_jam < 1) {
                                            $durasi_jam = 1;
                                        }

                                        // Format durasi yang mudah dibaca (real time)
                                        $jam   = floor($selisih_detik / 3600);
                                        $menit = floor(($selisih_detik % 3600) / 60);
                                        $durasi_text = $jam . "j " . $menit . "m";

                                        // Estimasi biaya
                                        $estimasi_biaya = $durasi_jam * $row['tarif_per_jam'];
                                ?>
                                    <tr>
                                        <td class="subtle"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['plat_nomor']); ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst(htmlspecialchars($row['jenis_kendaraan'])); ?></span></td>
                                        <td><?= htmlspecialchars($row['warna']); ?></td>
                                        <td><?= htmlspecialchars($row['pemilik']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_area']); ?></td>
                                        <td><?= $waktu_masuk_dt->format('d/m/Y H:i'); ?></td>
                                        <td><span class="badge bg-warning"><?= $durasi_text; ?></span></td>
                                        <td>Rp <?= number_format($row['tarif_per_jam'], 0, ',', '.'); ?></td>
                                        <td class="fw-bold">Rp <?= number_format($estimasi_biaya, 0, ',', '.'); ?></td>
                                        <td>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="id_transaksi" value="<?= $row['id_transaksi']; ?>">
                                                <button type="submit" name="checkout" 
                                                        class="btn btn-sm btn-success btn-pill px-3"
                                                        onclick="return confirm('Checkout kendaraan <?= htmlspecialchars($row['plat_nomor']); ?>?\n\nDurasi: <?= $durasi_jam; ?> jam\nTotal Biaya: Rp <?= number_format($estimasi_biaya, 0, ',', '.'); ?>')">
                                                    <i class="bi bi-box-arrow-right me-1"></i> Checkout
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } 
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="subtle small mt-3">
                    © <?= date('Y'); ?> Sistem Parkir — Admin Panel
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // Auto active menu
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href === currentPage) {
            link.classList.add('active');
        }
    });

    // Auto refresh setiap 30 detik untuk update durasi dan estimasi biaya
    setTimeout(function(){
        location.reload();
    }, 30000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>